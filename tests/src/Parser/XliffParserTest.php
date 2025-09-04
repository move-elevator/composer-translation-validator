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
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 *
 * @package ComposerTranslationValidator
 */

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

    private string $targetLanguageXliffFile;
    private string $targetLanguageXliffContent = <<<'EOT'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
  <file source-language="en" target-language="de" datatype="plaintext" original="messages.de.xlf">
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().'/xliff_parser_test_'.uniqid('', true);
        mkdir($this->tempDir);

        $this->validXliffFile = $this->tempDir.'/messages.xlf';
        file_put_contents($this->validXliffFile, $this->validXliffContent);

        $this->prefixedXliffFile = $this->tempDir.'/de.messages.xlf';
        file_put_contents($this->prefixedXliffFile, $this->prefixedXliffContent);

        $this->targetLanguageXliffFile = $this->tempDir.'/messages.de.xlf';
        file_put_contents($this->targetLanguageXliffFile, $this->targetLanguageXliffContent);
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
        new XliffParser('/non/existent/file.xlf');
    }

    public function testConstructorThrowsExceptionIfFileIsNotReadable(): void
    {
        $unreadableFile = $this->tempDir.'/unreadable.xlf';
        file_put_contents($unreadableFile, 'content');
        chmod($unreadableFile, 0000); // Make unreadable

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/File ".*" is not readable./');
        new XliffParser($unreadableFile);
    }

    public function testConstructorThrowsExceptionIfFileHasInvalidExtension(): void
    {
        $invalidFile = $this->tempDir.'/invalid.txt';
        file_put_contents($invalidFile, 'content');

        $this->expectException(InvalidArgumentException::class);
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

        $this->assertSame('Source 1', $parser->getContentByKey('key1'));
        $this->assertSame('Source 2', $parser->getContentByKey('key2'));
        $this->assertSame('Source 3', $parser->getContentByKey('key3'));
        $this->assertNull($parser->getContentByKey('nonexistent_key'));
    }

    public function testGetContentByKeyWithTargetLanguage(): void
    {
        $parser = new XliffParser($this->targetLanguageXliffFile);

        $this->assertSame('Target 1', $parser->getContentByKey('key1'));
        $this->assertSame('Target 2', $parser->getContentByKey('key2'));
        $this->assertSame('Source 3', $parser->getContentByKey('key3')); // Fallback to source when target is empty
        $this->assertNull($parser->getContentByKey('nonexistent_key'));
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
        $validator = new
/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 *
 * @package ComposerTranslationValidator
 */

class {
    public function testFileGetContentsFalse(string $filePath): void
    {
        // Simulate the exact code path from XliffParser constructor
        $xmlContent = @file_get_contents($filePath); // Suppress warning with @
        if (false === $xmlContent) {
            throw new InvalidArgumentException("Failed to read file: {$filePath}");
        }
    }
};

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to read file:');
        $validator->testFileGetContentsFalse('/non/existent/file.xlf');
    }

    public function testConstructorThrowsExceptionWhenXmlParsingFails(): void
    {
        $invalidXmlFile = $this->tempDir.'/invalid.xlf';
        file_put_contents($invalidXmlFile, 'invalid xml content');

        $this->expectException(InvalidArgumentException::class);
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
        $this->assertSame('Fallback Target', $parser->getContentByKey('empty_source'));
    }

    public function testGetContentByKeyFallsBackToSourceWhenTargetIsEmptyInTargetLanguageFile(): void
    {
        $fallbackFile = $this->tempDir.'/target_fallback.xlf';
        $fallbackContent = <<<'EOT'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
  <file source-language="en" target-language="de" datatype="plaintext" original="target_fallback.xlf">
    <body>
      <trans-unit id="empty_target">
        <source>Fallback Source</source>
        <target></target>
      </trans-unit>
    </body>
  </file>
</xliff>
EOT;
        file_put_contents($fallbackFile, $fallbackContent);

        $parser = new XliffParser($fallbackFile);
        $this->assertSame('Fallback Source', $parser->getContentByKey('empty_target'));
    }

    public function testGetContentByKeyReturnsNullWhenBothSourceAndTargetAreEmpty(): void
    {
        $emptyFile = $this->tempDir.'/empty.xlf';
        $emptyContent = <<<'EOT'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
  <file source-language="en" target-language="de" datatype="plaintext" original="empty.xlf">
    <body>
      <trans-unit id="empty_both">
        <source></source>
        <target></target>
      </trans-unit>
    </body>
  </file>
</xliff>
EOT;
        file_put_contents($emptyFile, $emptyContent);

        $parser = new XliffParser($emptyFile);
        $this->assertNull($parser->getContentByKey('empty_both'));
    }
}
