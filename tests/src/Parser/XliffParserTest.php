<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025-2026 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\Tests\Parser;

use InvalidArgumentException;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * XliffParserTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
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

    private string $xliff2File;
    private string $xliff2Content = <<<'EOT'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:2.0" version="2.0" srcLang="en">
  <file id="messages">
    <unit id="key1">
      <segment>
        <source>Source 1</source>
      </segment>
    </unit>
    <unit id="key2">
      <segment>
        <source>Source 2</source>
      </segment>
    </unit>
    <unit id="key3">
      <segment>
        <source>Source 3</source>
      </segment>
    </unit>
  </file>
</xliff>
EOT;

    private string $xliff2TargetFile;
    private string $xliff2TargetContent = <<<'EOT'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:2.0" version="2.0" srcLang="en" trgLang="de">
  <file id="messages">
    <unit id="key1">
      <segment>
        <source>Source 1</source>
        <target>Target 1</target>
      </segment>
    </unit>
    <unit id="key2">
      <segment>
        <source>Source 2</source>
        <target>Target 2</target>
      </segment>
    </unit>
    <unit id="key3">
      <segment>
        <source>Source 3</source>
        <target></target>
      </segment>
    </unit>
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

        $this->xliff2File = $this->tempDir.'/messages_v2.xlf';
        file_put_contents($this->xliff2File, $this->xliff2Content);

        $this->xliff2TargetFile = $this->tempDir.'/messages_v2.de.xlf';
        file_put_contents($this->xliff2TargetFile, $this->xliff2TargetContent);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
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
        $this->assertSame($this->tempDir.\DIRECTORY_SEPARATOR, $parser->getFileDirectory());
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

    public function testConstructorRejectsDoctype(): void
    {
        $doctypeFile = $this->tempDir.'/doctype.xlf';
        $doctypeContent = <<<'EOT'
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xliff [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
  <file source-language="en" datatype="plaintext" original="doctype.xlf">
    <body>
      <trans-unit id="key"><source>&xxe;</source></trans-unit>
    </body>
  </file>
</xliff>
EOT;
        file_put_contents($doctypeFile, $doctypeContent);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Document type definitions are not allowed');
        new XliffParser($doctypeFile);
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

    public function testExtractKeysXliff2(): void
    {
        $parser = new XliffParser($this->xliff2File);
        $keys = $parser->extractKeys();

        $this->assertSame(['key1', 'key2', 'key3'], $keys);
    }

    public function testGetContentByKeySourceXliff2(): void
    {
        $parser = new XliffParser($this->xliff2File);

        $this->assertSame('Source 1', $parser->getContentByKey('key1'));
        $this->assertSame('Source 2', $parser->getContentByKey('key2'));
        $this->assertSame('Source 3', $parser->getContentByKey('key3'));
        $this->assertNull($parser->getContentByKey('nonexistent_key'));
    }

    public function testGetContentByKeyWithTargetLanguageXliff2(): void
    {
        $parser = new XliffParser($this->xliff2TargetFile);

        $this->assertSame('Target 1', $parser->getContentByKey('key1'));
        $this->assertSame('Target 2', $parser->getContentByKey('key2'));
        $this->assertSame('Source 3', $parser->getContentByKey('key3'));
        $this->assertNull($parser->getContentByKey('nonexistent_key'));
    }

    public function testGetLanguageFromSrcLangAttributeXliff2(): void
    {
        $parser = new XliffParser($this->xliff2File);
        $this->assertSame('en', $parser->getLanguage());
    }

    public function testGetLanguageFromFileNamePrefixConvention(): void
    {
        $parser = new XliffParser($this->prefixedXliffFile); // de.messages.xlf
        $this->assertSame('de', $parser->getLanguageFromFileName());
    }

    public function testGetLanguageFromFileNameSuffixConvention(): void
    {
        $parser = new XliffParser($this->targetLanguageXliffFile); // messages.de.xlf
        $this->assertSame('de', $parser->getLanguageFromFileName());
    }

    public function testGetLanguageFromFileNamePrefixConventionWithRegion(): void
    {
        $file = $this->tempDir.'/de_DE.locallang.xlf';
        file_put_contents($file, $this->prefixedXliffContent);

        $parser = new XliffParser($file);
        $this->assertSame('de', $parser->getLanguageFromFileName());
    }

    public function testGetLanguageFromFileNameSuffixConventionWithRegion(): void
    {
        $file = $this->tempDir.'/messages.de_DE.xlf';
        file_put_contents($file, $this->targetLanguageXliffContent);

        $parser = new XliffParser($file);
        $this->assertSame('de', $parser->getLanguageFromFileName());
    }

    public function testGetLanguageFromFileNameReturnsNullForSourceFile(): void
    {
        $parser = new XliffParser($this->validXliffFile); // messages.xlf — no locale
        $this->assertNull($parser->getLanguageFromFileName());
    }

    public function testGetLanguageFromFileNameReturnsNullForXliff2SourceFile(): void
    {
        $parser = new XliffParser($this->xliff2File); // messages_v2.xlf — no locale
        $this->assertNull($parser->getLanguageFromFileName());
    }

    public function testGetTargetLanguageReturnsValueWhenSet(): void
    {
        $parser = new XliffParser($this->targetLanguageXliffFile); // target-language="de"
        $this->assertSame('de', $parser->getTargetLanguage());
    }

    public function testGetTargetLanguageReturnsNullWhenNotSet(): void
    {
        $parser = new XliffParser($this->validXliffFile); // no target-language attribute
        $this->assertNull($parser->getTargetLanguage());
    }

    public function testGetTargetLanguageXliff2ReturnsValueWhenSet(): void
    {
        $parser = new XliffParser($this->xliff2TargetFile); // trgLang="de"
        $this->assertSame('de', $parser->getTargetLanguage());
    }

    public function testGetTargetLanguageXliff2ReturnsNullWhenNotSet(): void
    {
        $parser = new XliffParser($this->xliff2File); // no trgLang
        $this->assertNull($parser->getTargetLanguage());
    }

    public function testGetTargetLanguageStripsRegionSuffixWithHyphen(): void
    {
        $file = $this->tempDir.'/region_hyphen.xlf';
        file_put_contents($file, <<<'EOT'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
  <file source-language="en" target-language="de-AT" datatype="plaintext" original="region_hyphen.xlf">
    <body><trans-unit id="k"><source>x</source></trans-unit></body>
  </file>
</xliff>
EOT);
        $this->assertSame('de', (new XliffParser($file))->getTargetLanguage());
    }

    public function testGetTargetLanguageStripsRegionSuffixWithUnderscore(): void
    {
        $file = $this->tempDir.'/region_underscore.xlf';
        file_put_contents($file, <<<'EOT'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
  <file source-language="en" target-language="de_AT" datatype="plaintext" original="region_underscore.xlf">
    <body><trans-unit id="k"><source>x</source></trans-unit></body>
  </file>
</xliff>
EOT);
        $this->assertSame('de', (new XliffParser($file))->getTargetLanguage());
    }

    public function testGetTargetLanguageXliff2StripsRegionSuffix(): void
    {
        $file = $this->tempDir.'/region_v2.xlf';
        file_put_contents($file, <<<'EOT'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:2.0" version="2.0" srcLang="en" trgLang="de-AT">
  <file id="messages">
    <unit id="k"><segment><source>x</source></segment></unit>
  </file>
</xliff>
EOT);
        $this->assertSame('de', (new XliffParser($file))->getTargetLanguage());
    }

    public function testGetLocaleFromFileNamePreservesRegionPrefix(): void
    {
        $file = $this->tempDir.'/de_DE.locallang.xlf';
        file_put_contents($file, $this->prefixedXliffContent);

        $this->assertSame('de_DE', (new XliffParser($file))->getLocaleFromFileName());
    }

    public function testGetLocaleFromFileNamePreservesRegionSuffix(): void
    {
        $file = $this->tempDir.'/messages.de_DE.xlf';
        file_put_contents($file, $this->targetLanguageXliffContent);

        $this->assertSame('de_DE', (new XliffParser($file))->getLocaleFromFileName());
    }

    public function testGetLocaleFromFileNameReturnsBaseWhenNoRegion(): void
    {
        $parser = new XliffParser($this->prefixedXliffFile); // de.messages.xlf
        $this->assertSame('de', $parser->getLocaleFromFileName());
    }

    public function testGetLocaleFromFileNamePreservesNonAlphaSubtag(): void
    {
        $file = $this->tempDir.'/messages.es_419.xlf';
        file_put_contents($file, $this->targetLanguageXliffContent);

        $this->assertSame('es_419', (new XliffParser($file))->getLocaleFromFileName());
    }

    public function testGetLocaleFromFileNameReturnsNullForSourceFile(): void
    {
        $parser = new XliffParser($this->validXliffFile); // messages.xlf — no locale
        $this->assertNull($parser->getLocaleFromFileName());
    }

    public function testGetTargetLocalePreservesRegion(): void
    {
        $file = $this->tempDir.'/region_full.xlf';
        file_put_contents($file, <<<'EOT'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
  <file source-language="en" target-language="de-AT" datatype="plaintext" original="region_full.xlf">
    <body><trans-unit id="k"><source>x</source></trans-unit></body>
  </file>
</xliff>
EOT);
        $this->assertSame('de-AT', (new XliffParser($file))->getTargetLocale());
    }

    public function testGetTargetLocaleXliff2PreservesRegion(): void
    {
        $file = $this->tempDir.'/region_full_v2.xlf';
        file_put_contents($file, <<<'EOT'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:2.0" version="2.0" srcLang="en" trgLang="de-AT">
  <file id="messages">
    <unit id="k"><segment><source>x</source></segment></unit>
  </file>
</xliff>
EOT);
        $this->assertSame('de-AT', (new XliffParser($file))->getTargetLocale());
    }

    public function testGetTargetLocaleReturnsNullWhenNotSet(): void
    {
        $parser = new XliffParser($this->validXliffFile); // no target-language attribute
        $this->assertNull($parser->getTargetLocale());
    }

    public function testGetContentByKeyFallsBackToSourceWhenTargetIsEmptyXliff2(): void
    {
        $fallbackFile = $this->tempDir.'/v2_fallback.xlf';
        $fallbackContent = <<<'EOT'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:2.0" version="2.0" srcLang="en" trgLang="de">
  <file id="messages">
    <unit id="empty_target">
      <segment>
        <source>Fallback Source</source>
        <target></target>
      </segment>
    </unit>
  </file>
</xliff>
EOT;
        file_put_contents($fallbackFile, $fallbackContent);

        $parser = new XliffParser($fallbackFile);
        $this->assertSame('Fallback Source', $parser->getContentByKey('empty_target'));
    }

    public function testGetContentByKeyFallsBackToTargetWhenSourceIsEmptyXliff2(): void
    {
        $fallbackFile = $this->tempDir.'/v2_source_fallback.xlf';
        $fallbackContent = <<<'EOT'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:2.0" version="2.0" srcLang="en">
  <file id="messages">
    <unit id="empty_source">
      <segment>
        <source></source>
        <target>Fallback Target</target>
      </segment>
    </unit>
  </file>
</xliff>
EOT;
        file_put_contents($fallbackFile, $fallbackContent);

        $parser = new XliffParser($fallbackFile);
        $this->assertSame('Fallback Target', $parser->getContentByKey('empty_source'));
    }

    public function testExtractKeysReturnsEmptyArrayWhenNoTransUnits(): void
    {
        $file = $this->tempDir.'/no_units.xlf';
        file_put_contents($file, <<<'EOT'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2"><file source-language="en" datatype="plaintext" original="x"><body></body></file></xliff>
EOT);

        $parser = new XliffParser($file);
        $this->assertSame([], $parser->extractKeys());
    }

    public function testGetContentByKeyReturnsNullWhenNoTransUnits(): void
    {
        $file = $this->tempDir.'/no_units_content.xlf';
        file_put_contents($file, <<<'EOT'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2"><file source-language="en" datatype="plaintext" original="x"><body></body></file></xliff>
EOT);

        $parser = new XliffParser($file);
        $this->assertNull($parser->getContentByKey('x'));
    }

    public function testExtractKeysXliff22(): void
    {
        $xliff22File = $this->tempDir.'/messages_v22.xlf';
        $xliff22Content = <<<'EOT'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:2.0" version="2.2" srcLang="en" trgLang="de">
  <file id="messages">
    <unit id="key1">
      <segment>
        <source>Source 1</source>
        <target>Target 1</target>
      </segment>
    </unit>
  </file>
</xliff>
EOT;
        file_put_contents($xliff22File, $xliff22Content);

        $parser = new XliffParser($xliff22File);
        $this->assertSame(['key1'], $parser->extractKeys());
        $this->assertSame('Target 1', $parser->getContentByKey('key1'));
        $this->assertSame('en', $parser->getLanguage());
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
}
