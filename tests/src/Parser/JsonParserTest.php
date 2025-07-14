<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Parser;

use MoveElevator\ComposerTranslationValidator\Parser\JsonParser;
use PHPUnit\Framework\TestCase;

final class JsonParserTest extends TestCase
{
    private string $tempDir;
    private string $validJsonFile;
    private string $invalidJsonFile;
    private string $nestedJsonFile;
    private string $languageJsonFile;
    private string $nonObjectJsonFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().'/json_parser_test_'.uniqid('', true);
        mkdir($this->tempDir);

        $this->validJsonFile = $this->tempDir.'/messages.json';
        file_put_contents($this->validJsonFile, json_encode([
            'key1' => 'value1',
            'key2' => 'value2',
        ], JSON_PRETTY_PRINT));

        $this->invalidJsonFile = $this->tempDir.'/invalid.json';
        file_put_contents($this->invalidJsonFile, '{"key1": "value1", "key2":}'); // Invalid JSON syntax

        $this->nestedJsonFile = $this->tempDir.'/nested.json';
        file_put_contents($this->nestedJsonFile, json_encode([
            'parent' => [
                'child1' => 'value1',
                'child2' => 'value2',
            ],
        ], JSON_PRETTY_PRINT));

        $this->languageJsonFile = $this->tempDir.'/messages.de.json';
        file_put_contents($this->languageJsonFile, json_encode([
            'key1' => 'value1',
        ], JSON_PRETTY_PRINT));

        $this->nonObjectJsonFile = $this->tempDir.'/array.json';
        file_put_contents($this->nonObjectJsonFile, '["value1", "value2"]'); // JSON array instead of object
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    private function removeDirectory(string $path): void
    {
        $files = glob($path.'/*');
        foreach ($files as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        rmdir($path);
    }

    public function testConstructorThrowsExceptionIfFileDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/File ".*" does not exist./');
        new JsonParser('/non/existent/file.json');
    }

    public function testConstructorThrowsExceptionIfFileIsNotReadable(): void
    {
        $unreadableFile = $this->tempDir.'/unreadable.json';
        file_put_contents($unreadableFile, '{"key": "value"}');
        chmod($unreadableFile, 0000); // Make unreadable

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/File ".*" is not readable./');
        new JsonParser($unreadableFile);
    }

    public function testConstructorThrowsExceptionIfFileHasInvalidExtension(): void
    {
        $invalidFile = $this->tempDir.'/invalid.txt';
        file_put_contents($invalidFile, '{"key": "value"}');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/File ".*" is not a valid file./');
        new JsonParser($invalidFile);
    }

    public function testConstructorThrowsExceptionIfJsonIsInvalid(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Failed to parse JSON file/');
        new JsonParser($this->invalidJsonFile);
    }

    public function testConstructorThrowsExceptionIfJsonIsNotObject(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/JSON file does not contain an object/');
        new JsonParser($this->nonObjectJsonFile);
    }

    public function testConstructorThrowsExceptionOnEmptyJsonFile(): void
    {
        $emptyFile = $this->tempDir.'/empty.json';
        file_put_contents($emptyFile, ''); // Empty file causes JSON syntax error

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Failed to parse JSON file/');
        new JsonParser($emptyFile);
    }

    public function testExtractKeys(): void
    {
        $parser = new JsonParser($this->validJsonFile);
        $keys = $parser->extractKeys();
        $this->assertSame(['key1', 'key2'], $keys);

        $nestedParser = new JsonParser($this->nestedJsonFile);
        $nestedKeys = $nestedParser->extractKeys();
        $this->assertSame(['parent.child1', 'parent.child2'], $nestedKeys);
    }

    public function testGetContentByKey(): void
    {
        $parser = new JsonParser($this->validJsonFile);
        $this->assertSame('value1', $parser->getContentByKey('key1'));
        $this->assertNull($parser->getContentByKey('nonexistent_key'));

        $nestedParser = new JsonParser($this->nestedJsonFile);
        $this->assertSame('value1', $nestedParser->getContentByKey('parent.child1'));
        $this->assertNull($nestedParser->getContentByKey('parent.nonexistent'));
    }

    public function testGetContentByKeyReturnsNullForNonStringValues(): void
    {
        $arrayValueFile = $this->tempDir.'/array_value.json';
        file_put_contents($arrayValueFile, json_encode([
            'key_with_array' => ['value1', 'value2'],
            'key_with_object' => ['nested' => 'value'],
            'key_with_null' => null,
            'key_with_number' => 42,
        ], JSON_PRETTY_PRINT));

        $parser = new JsonParser($arrayValueFile);
        $this->assertNull($parser->getContentByKey('key_with_array'));
        $this->assertNull($parser->getContentByKey('key_with_object'));
        $this->assertNull($parser->getContentByKey('key_with_null'));
        $this->assertNull($parser->getContentByKey('key_with_number'));
    }

    public function testGetSupportedFileExtensions(): void
    {
        $extensions = JsonParser::getSupportedFileExtensions();
        $this->assertSame(['json'], $extensions);
    }

    public function testGetLanguage(): void
    {
        $parser = new JsonParser($this->languageJsonFile);
        $this->assertSame('de', $parser->getLanguage());

        $parser = new JsonParser($this->validJsonFile);
        $this->assertSame('', $parser->getLanguage());
    }

    public function testGetLanguageWithDifferentPatterns(): void
    {
        // Test various language patterns
        $patterns = [
            'messages.en.json' => 'en',
            'validation.fr.json' => 'fr',
            'app.en_US.json' => 'en',
            'form.de_DE.json' => 'de',
            'simple.json' => '',
            'no.language.here.json' => '',
        ];

        foreach ($patterns as $filename => $expectedLanguage) {
            $file = $this->tempDir.'/'.$filename;
            file_put_contents($file, '{"key": "value"}');

            $parser = new JsonParser($file);
            $this->assertSame($expectedLanguage, $parser->getLanguage(), "Failed for filename: $filename");

            unlink($file);
        }
    }

    public function testExtractKeysWithComplexNesting(): void
    {
        $complexFile = $this->tempDir.'/complex.json';
        file_put_contents($complexFile, json_encode([
            'level1' => [
                'level2' => [
                    'level3' => 'deep_value',
                    'another' => 'value',
                ],
                'simple' => 'value',
            ],
            'root' => 'root_value',
        ], JSON_PRETTY_PRINT));

        $parser = new JsonParser($complexFile);
        $keys = $parser->extractKeys();

        $expectedKeys = [
            'level1.level2.level3',
            'level1.level2.another',
            'level1.simple',
            'root',
        ];

        sort($keys);
        sort($expectedKeys);

        $this->assertSame($expectedKeys, $keys);
    }

    public function testGetContentByKeyWithComplexNesting(): void
    {
        $complexFile = $this->tempDir.'/complex.json';
        file_put_contents($complexFile, json_encode([
            'level1' => [
                'level2' => [
                    'level3' => 'deep_value',
                ],
            ],
        ], JSON_PRETTY_PRINT));

        $parser = new JsonParser($complexFile);
        $this->assertSame('deep_value', $parser->getContentByKey('level1.level2.level3'));
        $this->assertNull($parser->getContentByKey('level1.level2.level4'));
        $this->assertNull($parser->getContentByKey('level1.level2.level3.too_deep'));
    }
}
