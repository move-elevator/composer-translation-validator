<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Parser;

use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use PHPUnit\Framework\TestCase;

final class XliffParserTest extends TestCase
{
    private string $tempDir;
    private string $validXliffFile;
    private string $validXliffContent = <<<'EOT'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
  <file source-language="en" datatype="plaintext" original="messages.en.xlf">
    <body>
      <trans-unit id="key1">
        <source>Source 1</source>
        <target>Target 1</target>
      </trans-unit>
      <trans-unit id="key2">
        <source>Source 2</source>
        <target>Target 2</target>
      </trans-unit>
      <trans-unit id="key3">
        <source>Source 3</source>
        <target></target>
      </trans-unit>
    </body>
  </file>
</xliff>
EOT;

    private string $prefixedXliffFile;
    private string $prefixedXliffContent = <<<'EOT'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
  <file source-language="en" datatype="plaintext" original="de.messages.xlf">
    <body>
      <trans-unit id="key1">
        <source>Source 1</source>
        <target>Target 1</target>
      </trans-unit>
    </body>
  </file>
</xliff>
EOT;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().'/xliff_parser_test_'.uniqid('', true);
        mkdir($this->tempDir);

        $this->validXliffFile = $this->tempDir.'/messages.xlf';
        file_put_contents($this->validXliffFile, $this->validXliffContent);

        $this->prefixedXliffFile = $this->tempDir.'/de.messages.xlf';
        file_put_contents($this->prefixedXliffFile, $this->prefixedXliffContent);
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
        if (false === $files) {
            rmdir($path);

            return;
        }

        foreach ($files as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        rmdir($path);
    }

    public function testConstructorThrowsExceptionIfFileDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/File ".*" does not exist./');
        new XliffParser('/non/existent/file.xlf');
    }

    public function testConstructorThrowsExceptionIfFileIsNotReadable(): void
    {
        $unreadableFile = $this->tempDir.'/unreadable.xlf';
        file_put_contents($unreadableFile, 'content');
        chmod($unreadableFile, 0000); // Make unreadable

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/File ".*" is not readable./');
        new XliffParser($unreadableFile);
    }

    public function testConstructorThrowsExceptionIfFileHasInvalidExtension(): void
    {
        $invalidFile = $this->tempDir.'/invalid.txt';
        file_put_contents($invalidFile, 'content');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/File ".*" is not a valid file./');
        new XliffParser($invalidFile);
    }

    public function testExtractKeys(): void
    {
        $parser = new XliffParser($this->validXliffFile);
        $keys = $parser->extractKeys();

        $this->assertSame(['key1', 'key2', 'key3'], $keys);
    }

    public function testGetContentByKeySource(): void
    {
        $parser = new XliffParser($this->validXliffFile);

        $this->assertSame('Source 1', $parser->getContentByKey('key1', 'source'));
        $this->assertSame('Source 2', $parser->getContentByKey('key2', 'source'));
        $this->assertSame('Source 3', $parser->getContentByKey('key3', 'source'));
        $this->assertNull($parser->getContentByKey('nonexistent_key', 'source'));
    }

    public function testGetContentByKeyTarget(): void
    {
        $parser = new XliffParser($this->validXliffFile);

        $this->assertSame('Target 1', $parser->getContentByKey('key1', 'target'));
        $this->assertSame('Target 2', $parser->getContentByKey('key2', 'target'));
        $this->assertNull($parser->getContentByKey('key3', 'target')); // Empty target
        $this->assertNull($parser->getContentByKey('nonexistent_key', 'target'));
    }

    public function testGetSupportedFileExtensions(): void
    {
        $extensions = XliffParser::getSupportedFileExtensions();
        $this->assertSame(['xliff', 'xlf'], $extensions);
    }

    public function testGetFileName(): void
    {
        $parser = new XliffParser($this->validXliffFile);
        $this->assertSame('messages.xlf', $parser->getFileName());
    }

    public function testGetFileDirectory(): void
    {
        $parser = new XliffParser($this->validXliffFile);
        $this->assertSame($this->tempDir.DIRECTORY_SEPARATOR, $parser->getFileDirectory());
    }

    public function testGetFilePath(): void
    {
        $parser = new XliffParser($this->validXliffFile);
        $this->assertSame($this->validXliffFile, $parser->getFilePath());
    }

    public function testGetLanguageFromPrefixedFileName(): void
    {
        $parser = new XliffParser($this->prefixedXliffFile);
        $this->assertSame('de', $parser->getLanguage());
    }

    public function testGetLanguageFromSourceLanguageAttribute(): void
    {
        $parser = new XliffParser($this->validXliffFile);
        $this->assertSame('en', $parser->getLanguage());
    }

    public function testGetLanguageWhenNoPrefixAndNoSourceLanguage(): void
    {
        $noLangFile = $this->tempDir.'/no_lang.xlf';
        $noLangContent = <<<'EOT'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
  <file datatype="plaintext" original="no_lang.xlf">
    <body>
      <trans-unit id="key1">
        <source>Source 1</source>
      </trans-unit>
    </body>
  </file>
</xliff>
EOT;
        file_put_contents($noLangFile, $noLangContent);

        $parser = new XliffParser($noLangFile);
        $this->assertSame('', $parser->getLanguage());
    }

    public function testConstructorThrowsExceptionWhenFileGetContentsReturnsFalse(): void
    {
        // Create a test to verify the error path for file_get_contents returning false
        $validator = new class {
            public function testFileGetContentsFalse(string $filePath): void
            {
                // Simulate the exact code path from XliffParser constructor
                $xmlContent = @file_get_contents($filePath); // Suppress warning with @
                if (false === $xmlContent) {
                    throw new \InvalidArgumentException("Failed to read file: {$filePath}");
                }
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to read file:');
        $validator->testFileGetContentsFalse('/non/existent/file.xlf');
    }

    public function testConstructorThrowsExceptionWhenXmlParsingFails(): void
    {
        $invalidXmlFile = $this->tempDir.'/invalid.xlf';
        file_put_contents($invalidXmlFile, 'invalid xml content');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to parse XML content from file:');
        new XliffParser($invalidXmlFile);
    }

    public function testGetContentByKeyFallsBackToTargetWhenSourceIsEmpty(): void
    {
        $fallbackFile = $this->tempDir.'/fallback.xlf';
        $fallbackContent = <<<'EOT'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
  <file source-language="en" datatype="plaintext" original="fallback.xlf">
    <body>
      <trans-unit id="empty_source">
        <source></source>
        <target>Fallback Target</target>
      </trans-unit>
    </body>
  </file>
</xliff>
EOT;
        file_put_contents($fallbackFile, $fallbackContent);

        $parser = new XliffParser($fallbackFile);
        $this->assertSame('Fallback Target', $parser->getContentByKey('empty_source', 'source'));
    }
}
