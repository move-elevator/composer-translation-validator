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

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use MoveElevator\ComposerTranslationValidator\Validator\XliffSchemaValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * XliffSchemaValidatorTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class XliffSchemaValidatorTest extends TestCase
{
    private XliffSchemaValidator $validator;

    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createStub(LoggerInterface::class);
        $this->validator = new XliffSchemaValidator($this->logger);
    }

    public function testClassCanBeInstantiatedWithoutLogger(): void
    {
        $validator = new XliffSchemaValidator();
        $this->assertSame([XliffParser::class], $validator->supportsParser());
    }

    public function testProcessFileWithValidXliff(): void
    {
        $validXliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file source-language="en" target-language="de" original="test.xlf" datatype="plaintext">
        <body>
            <trans-unit id="test">
                <source>Hello</source>
                <target>Hallo</target>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $tempFile = tempnam(sys_get_temp_dir(), 'xliff_test_');
        file_put_contents($tempFile, $validXliff);

        $parser = $this->createStub(XliffParser::class);
        $parser->method('getFilePath')->willReturn($tempFile);
        $parser->method('getFileName')->willReturn('test.xlf');

        $result = $this->validator->processFile($parser);

        unlink($tempFile);

        $this->assertSame([], $result);
    }

    public function testProcessFileReportsSchemaErrorsViaCachedSchema(): void
    {
        // Well-formed XML but schema-invalid: <trans-unit> requires an "id".
        $invalidXliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file source-language="en" original="test.xlf" datatype="plaintext">
        <body>
            <trans-unit>
                <source>Hello</source>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $tempFile = tempnam(sys_get_temp_dir(), 'xliff_test_');
        file_put_contents($tempFile, $invalidXliff);

        $parser = $this->createStub(XliffParser::class);
        $parser->method('getFilePath')->willReturn($tempFile);
        $parser->method('getFileName')->willReturn('test.xlf');

        $result = $this->validator->processFile($parser);

        unlink($tempFile);

        $this->assertNotEmpty($result);
        $this->assertSame('ERROR', $result[0]['level']);
    }

    public function testProcessFileReusesCachedSchemaAcrossFiles(): void
    {
        $validXliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file source-language="en" original="reuse.xlf" datatype="plaintext">
        <body>
            <trans-unit id="a"><source>Hello</source></trans-unit>
        </body>
    </file>
</xliff>
XML;

        $tempFile = tempnam(sys_get_temp_dir(), 'xliff_test_');
        file_put_contents($tempFile, $validXliff);

        $parser = $this->createStub(XliffParser::class);
        $parser->method('getFilePath')->willReturn($tempFile);
        $parser->method('getFileName')->willReturn('reuse.xlf');

        // Two runs exercise the per-version schema-source cache.
        $this->assertSame([], $this->validator->processFile($parser));
        $this->assertSame([], $this->validator->processFile($parser));

        unlink($tempFile);
    }

    public function testProcessFileWithNonExistentFile(): void
    {
        $parser = $this->createStub(XliffParser::class);
        $parser->method('getFilePath')->willReturn('/non/existent/file.xlf');
        $parser->method('getFileName')->willReturn('non_existent.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('File does not exist: non_existent.xlf');

        $validator = new XliffSchemaValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertSame([], $result);
    }

    public function testProcessFileWithInvalidXml(): void
    {
        $invalidXml = '<invalid xml content';

        $tempFile = tempnam(sys_get_temp_dir(), 'xliff_test_');
        file_put_contents($tempFile, $invalidXml);

        $parser = $this->createStub(XliffParser::class);
        $parser->method('getFilePath')->willReturn($tempFile);
        $parser->method('getFileName')->willReturn('invalid.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to parse XML:'));

        $validator = new XliffSchemaValidator($logger);
        $result = $validator->processFile($parser);

        unlink($tempFile);

        $this->assertSame([], $result);
    }

    public function testProcessFileWithUnsupportedXliffVersion(): void
    {
        $unsupportedXliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="3.0" xmlns="urn:oasis:names:tc:xliff:document:3.0">
    <file id="test">
        <unit id="test">
            <segment>
                <source>Hello</source>
                <target>Hallo</target>
            </segment>
        </unit>
    </file>
</xliff>
XML;

        $tempFile = tempnam(sys_get_temp_dir(), 'xliff_test_');
        file_put_contents($tempFile, $unsupportedXliff);

        $parser = $this->createStub(XliffParser::class);
        $parser->method('getFilePath')->willReturn($tempFile);
        $parser->method('getFileName')->willReturn('unsupported.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('notice')
            ->with($this->stringContains('Skipping XliffSchemaValidator: No support implemented for loading XLIFF version'));

        $validator = new XliffSchemaValidator($logger);
        $result = $validator->processFile($parser);

        unlink($tempFile);

        $this->assertSame([], $result);
    }

    public function testFormatIssueMessageWithArrayError(): void
    {
        // AbstractValidator creates one Issue per error, so errorDetails is a single error array
        $errorDetails = [
            'message' => 'Element validation failed',
            'line' => 42,
            'code' => 'XLIFF001',
            'level' => 'ERROR',
        ];

        $issue = new Issue(
            'test.xlf',
            $errorDetails,
            'XliffParser',
            'XliffSchemaValidator',
        );

        $result = $this->validator->formatIssueMessage($issue, 'Prefix: ');

        $expected = '- <fg=red>Error</> Prefix: Element validation failed (Line: 42) (Code: XLIFF001)';
        $this->assertSame($expected, $result);
    }

    public function testFormatIssueMessageWithWarning(): void
    {
        // AbstractValidator creates one Issue per error, so errorDetails is a single error array
        $errorDetails = [
            'message' => 'Optional element missing',
            'level' => 'WARNING',
        ];

        $issue = new Issue(
            'test.xlf',
            $errorDetails,
            'XliffParser',
            'XliffSchemaValidator',
        );

        $result = $this->validator->formatIssueMessage($issue);

        $expected = '- <fg=yellow>Warning</> Optional element missing';
        $this->assertSame($expected, $result);
    }

    public function testFormatIssueMessageWithEmptyDetails(): void
    {
        $issue = new Issue(
            'test.xlf',
            [],
            'XliffParser',
            'XliffSchemaValidator',
        );

        $result = $this->validator->formatIssueMessage($issue);

        $expected = '- <fg=red>Error</> Schema validation error';
        $this->assertSame($expected, $result);
    }

    public function testFormatIssueMessageWithIncompleteErrorArray(): void
    {
        // Test a single error array with missing message
        $errorDetails = [
            'line' => 10,
            'code' => 1234,
            'level' => 'ERROR',
            // 'message' is missing
        ];

        $issue = new Issue(
            'test.xlf',
            $errorDetails,
            'XliffParser',
            'XliffSchemaValidator',
        );

        $result = $this->validator->formatIssueMessage($issue);

        $expected = '- <fg=red>Error</> Schema validation error';
        $this->assertSame($expected, $result);
    }

    public function testProcessFileWithMissingTargetLanguage(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file source-language="en" original="messages" datatype="plaintext">
        <body>
            <trans-unit id="test">
                <source>Hello</source>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $tempDir = sys_get_temp_dir();
        $tempFile = $tempDir.'/de.locallang.xlf';
        file_put_contents($tempFile, $xliff);

        $parser = new XliffParser($tempFile);
        $result = $this->validator->processFile($parser);

        unlink($tempFile);

        $targetLangErrors = array_filter(
            $result,
            static fn ($e) => isset($e['message']) && str_contains((string) $e['message'], 'target-language'),
        );
        $this->assertCount(1, $targetLangErrors);
        $error = array_values($targetLangErrors)[0];
        $this->assertStringContainsString('Missing "target-language"', $error['message']);
        $this->assertStringContainsString('"de"', $error['message']);
    }

    public function testProcessFileWithRegionInFilenameAndAttributeIsNotAFalsePositive(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file source-language="en" target-language="de-AT" original="messages" datatype="plaintext">
        <body>
            <trans-unit id="test">
                <source>Hello</source>
                <target>Hallo</target>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $tempDir = sys_get_temp_dir();
        $tempFile = $tempDir.'/de_AT.locallang.xlf';
        file_put_contents($tempFile, $xliff);

        $parser = new XliffParser($tempFile);
        $result = $this->validator->processFile($parser);

        unlink($tempFile);

        $this->assertSame([], $result);
    }

    public function testProcessFileWithRegionMismatchEmitsWarning(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file source-language="en" target-language="de-AT" original="messages" datatype="plaintext">
        <body>
            <trans-unit id="test">
                <source>Hello</source>
                <target>Hallo</target>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $tempDir = sys_get_temp_dir().'/xliff_schema_'.uniqid('', true);
        mkdir($tempDir);
        $tempFile = $tempDir.'/de_DE.locallang.xlf';
        file_put_contents($tempFile, $xliff);

        try {
            $result = $this->validator->processFile(new XliffParser($tempFile));
        } finally {
            unlink($tempFile);
            rmdir($tempDir);
        }

        $this->assertCount(1, $result);
        $this->assertSame('WARNING', $result[0]['level']);
        $this->assertStringContainsString('different region', $result[0]['message']);
        $this->assertStringContainsString('de-AT', $result[0]['message']);
        $this->assertStringContainsString('de_DE', $result[0]['message']);
    }

    public function testProcessFileWithRegionMismatchSuffixConventionEmitsWarning(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file source-language="en" target-language="de-AT" original="messages" datatype="plaintext">
        <body>
            <trans-unit id="test">
                <source>Hello</source>
                <target>Hallo</target>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $tempDir = sys_get_temp_dir().'/xliff_schema_'.uniqid('', true);
        mkdir($tempDir);
        $tempFile = $tempDir.'/messages.de_DE.xlf';
        file_put_contents($tempFile, $xliff);

        try {
            $result = $this->validator->processFile(new XliffParser($tempFile));
        } finally {
            unlink($tempFile);
            rmdir($tempDir);
        }

        $this->assertCount(1, $result);
        $this->assertSame('WARNING', $result[0]['level']);
    }

    public function testProcessFileXliff2WithRegionMismatchEmitsWarning(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="2.0" xmlns="urn:oasis:names:tc:xliff:document:2.0" srcLang="en" trgLang="de-AT">
    <file id="messages">
        <unit id="test">
            <segment>
                <source>Hello</source>
                <target>Hallo</target>
            </segment>
        </unit>
    </file>
</xliff>
XML;

        $tempDir = sys_get_temp_dir().'/xliff_schema_'.uniqid('', true);
        mkdir($tempDir);
        $tempFile = $tempDir.'/de_DE.locallang.xlf';
        file_put_contents($tempFile, $xliff);

        try {
            $result = $this->validator->processFile(new XliffParser($tempFile));
        } finally {
            unlink($tempFile);
            rmdir($tempDir);
        }

        $this->assertCount(1, $result);
        $this->assertSame('WARNING', $result[0]['level']);
        $this->assertStringContainsString('trgLang', $result[0]['message']);
    }

    public function testProcessFileWithMismatchedTargetLanguage(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file source-language="en" target-language="fr" original="messages" datatype="plaintext">
        <body>
            <trans-unit id="test">
                <source>Hello</source>
                <target>Hallo</target>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $tempDir = sys_get_temp_dir();
        $tempFile = $tempDir.'/de.locallang.xlf';
        file_put_contents($tempFile, $xliff);

        $parser = new XliffParser($tempFile);
        $result = $this->validator->processFile($parser);

        unlink($tempFile);

        $this->assertCount(1, $result);
        $this->assertStringContainsString('"fr"', $result[0]['message']);
        $this->assertStringContainsString('"de"', $result[0]['message']);
    }

    public function testProcessFileWithoutLanguagePrefixSkipsTargetLanguageCheck(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file source-language="en" original="messages" datatype="plaintext">
        <body>
            <trans-unit id="test">
                <source>Hello</source>
            </trans-unit>
        </body>
    </file>
</xliff>
XML;

        $tempDir = sys_get_temp_dir();
        $tempFile = $tempDir.'/locallang.xlf';
        file_put_contents($tempFile, $xliff);

        $parser = new XliffParser($tempFile);
        $result = $this->validator->processFile($parser);

        unlink($tempFile);

        $this->assertSame([], $result);
    }

    public function testProcessFileXliff2WithMissingTrgLang(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="2.0" xmlns="urn:oasis:names:tc:xliff:document:2.0" srcLang="en">
    <file id="messages">
        <unit id="test">
            <segment>
                <source>Hello</source>
            </segment>
        </unit>
    </file>
</xliff>
XML;

        $tempDir = sys_get_temp_dir();
        $tempFile = $tempDir.'/de.locallang.xlf';
        file_put_contents($tempFile, $xliff);

        $parser = new XliffParser($tempFile);
        $result = $this->validator->processFile($parser);

        unlink($tempFile);

        $hasTargetLangError = false;
        foreach ($result as $error) {
            if (isset($error['message']) && str_contains($error['message'], 'trgLang')) {
                $hasTargetLangError = true;
                break;
            }
        }
        $this->assertTrue($hasTargetLangError, 'Expected a trgLang error for XLIFF 2.x without trgLang');
    }

    public function testProcessFileXliff2WithMismatchedTrgLang(): void
    {
        $xliff = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="2.0" xmlns="urn:oasis:names:tc:xliff:document:2.0" srcLang="en" trgLang="fr">
    <file id="messages">
        <unit id="test">
            <segment>
                <source>Hello</source>
                <target>Bonjour</target>
            </segment>
        </unit>
    </file>
</xliff>
XML;

        $tempDir = sys_get_temp_dir();
        $tempFile = $tempDir.'/de.locallang.xlf';
        file_put_contents($tempFile, $xliff);

        $parser = new XliffParser($tempFile);
        $result = $this->validator->processFile($parser);

        unlink($tempFile);

        $mismatchErrors = array_filter(
            $result,
            static fn ($e) => isset($e['message']) && str_contains((string) $e['message'], 'trgLang'),
        );
        $this->assertCount(1, $mismatchErrors);
        $error = array_values($mismatchErrors)[0];
        $this->assertStringContainsString('"fr"', $error['message']);
        $this->assertStringContainsString('"de"', $error['message']);
    }
}
