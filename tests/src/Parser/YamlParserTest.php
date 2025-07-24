<?php

declare(strict_types=1);

/*
 * This file is part of the Composer plugin "composer-translation-validator".
 *
 * Copyright (C) 2025 Konrad Michalik <km@move-elevator.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace MoveElevator\ComposerTranslationValidator\Tests\Parser;

use InvalidArgumentException;
use MoveElevator\ComposerTranslationValidator\Parser\YamlParser;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class YamlParserTest extends TestCase
{
    private string $tempDir;
    private string $validYamlFile;
    private string $invalidYamlFile;
    private string $nestedYamlFile;
    private string $languageYamlFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().'/yaml_parser_test_'.uniqid('', true);
        mkdir($this->tempDir);

        $this->validYamlFile = $this->tempDir.'/messages.yaml';
        file_put_contents($this->validYamlFile, <<<'EOT'
key1: value1
key2: value2
EOT
        );

        $this->invalidYamlFile = $this->tempDir.'/invalid.yaml';
        file_put_contents($this->invalidYamlFile, <<<'EOT'
key1: value1
  key2: value2
EOT
        ); // Invalid YAML syntax

        $this->nestedYamlFile = $this->tempDir.'/nested.yaml';
        file_put_contents($this->nestedYamlFile, <<<'EOT'
parent:
  child1: value1
  child2: value2
EOT
        );

        $this->languageYamlFile = $this->tempDir.'/messages.de.yaml';
        file_put_contents($this->languageYamlFile, <<<'EOT'
key1: value1
EOT
        );
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
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/File ".*" does not exist./');
        new YamlParser('/non/existent/file.yaml');
    }

    public function testConstructorThrowsExceptionIfFileIsNotReadable(): void
    {
        $unreadableFile = $this->tempDir.'/unreadable.yaml';
        file_put_contents($unreadableFile, 'content');
        chmod($unreadableFile, 0000); // Make unreadable

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/File ".*" is not readable./');
        new YamlParser($unreadableFile);
    }

    public function testConstructorThrowsExceptionIfFileHasInvalidExtension(): void
    {
        $invalidFile = $this->tempDir.'/invalid.txt';
        file_put_contents($invalidFile, 'content');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/File ".*" is not a valid file./');
        new YamlParser($invalidFile);
    }

    public function testConstructorThrowsExceptionIfYamlIsInvalid(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Failed to parse YAML file/');
        new YamlParser($this->invalidYamlFile);
    }

    public function testExtractKeys(): void
    {
        $parser = new YamlParser($this->validYamlFile);
        $keys = $parser->extractKeys();
        $this->assertSame(['key1', 'key2'], $keys);

        $nestedParser = new YamlParser($this->nestedYamlFile);
        $nestedKeys = $nestedParser->extractKeys();
        $this->assertSame(['parent.child1', 'parent.child2'], $nestedKeys);
    }

    public function testGetContentByKey(): void
    {
        $parser = new YamlParser($this->validYamlFile);
        $this->assertSame('value1', $parser->getContentByKey('key1'));
        $this->assertNull($parser->getContentByKey('nonexistent_key'));

        $nestedParser = new YamlParser($this->nestedYamlFile);
        $this->assertSame('value1', $nestedParser->getContentByKey('parent.child1'));
        $this->assertNull($nestedParser->getContentByKey('parent.nonexistent'));
    }

    public function testGetSupportedFileExtensions(): void
    {
        $extensions = YamlParser::getSupportedFileExtensions();
        $this->assertSame(['yaml', 'yml'], $extensions);
    }

    public function testGetLanguage(): void
    {
        $parser = new YamlParser($this->languageYamlFile);
        $this->assertSame('de', $parser->getLanguage());

        $parser = new YamlParser($this->validYamlFile);
        $this->assertSame('', $parser->getLanguage());
    }
}
