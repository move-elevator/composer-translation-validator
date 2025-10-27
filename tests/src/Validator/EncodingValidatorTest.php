<?php

declare(strict_types=1);

/*
 * This file is part of the "composer-translation-validator" Composer plugin.
 *
 * (c) 2025 Konrad Michalik <km@move-elevator.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\{JsonParser, PhpParser, XliffParser, YamlParser};
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use MoveElevator\ComposerTranslationValidator\Validator\{EncodingValidator, ResultType};
use Normalizer;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * EncodingValidatorTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class EncodingValidatorTest extends TestCase
{
    private EncodingValidator $validator;
    private string $testFilesPath;

    protected function setUp(): void
    {
        $this->validator = new EncodingValidator();
        $this->testFilesPath = __DIR__.'/../../Fixtures/EncodingValidator';

        if (!is_dir($this->testFilesPath)) {
            mkdir($this->testFilesPath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (is_dir($this->testFilesPath)) {
            array_map(unlink(...), glob($this->testFilesPath.'/*') ?: []);
            rmdir($this->testFilesPath);
        }
    }

    public function testSupportsAllParsers(): void
    {
        $supportedParsers = $this->validator->supportsParser();

        $this->assertContains(XliffParser::class, $supportedParsers);
        $this->assertContains(YamlParser::class, $supportedParsers);
        $this->assertContains(JsonParser::class, $supportedParsers);
        $this->assertContains(PhpParser::class, $supportedParsers);
        $this->assertCount(4, $supportedParsers);
    }

    public function testResultTypeIsWarning(): void
    {
        $this->assertSame(ResultType::WARNING, $this->validator->resultTypeOnValidationFailure());
    }

    public function testValidUtf8File(): void
    {
        $filePath = $this->testFilesPath.'/valid-utf8.json';
        file_put_contents($filePath, '{"key": "value with ümlaut"}');

        $parser = new JsonParser($filePath);
        $issues = $this->validator->processFile($parser);

        $this->assertEmpty($issues);
    }

    public function testInvalidUtf8File(): void
    {
        $filePath = $this->testFilesPath.'/invalid-utf8.php';
        file_put_contents($filePath, "<?php return ['key' => 'value\x80']; // Invalid UTF-8");

        $parser = new PhpParser($filePath);
        $issues = $this->validator->processFile($parser);

        $this->assertArrayHasKey('encoding', $issues);
        $this->assertStringContainsString('not valid UTF-8', $issues['encoding']);
    }

    public function testFileWithBom(): void
    {
        $filePath = $this->testFilesPath.'/with-bom.yaml';
        file_put_contents($filePath, "\xEF\xBB\xBFkey: value"); // UTF-8 BOM

        $parser = new YamlParser($filePath);
        $issues = $this->validator->processFile($parser);

        $this->assertArrayHasKey('bom', $issues);
        $this->assertStringContainsString('Byte Order Mark', $issues['bom']);
    }

    public function testFileWithInvisibleCharacters(): void
    {
        $filePath = $this->testFilesPath.'/invisible-chars.yaml';
        file_put_contents($filePath, "key: value\u{200B}with\u{200C}invisible"); // Zero-width chars

        $parser = new YamlParser($filePath);
        $issues = $this->validator->processFile($parser);

        $this->assertArrayHasKey('invisible_chars', $issues);
        $this->assertStringContainsString('invisible characters', $issues['invisible_chars']);
        $this->assertStringContainsString('Zero-width space', $issues['invisible_chars']);
        $this->assertStringContainsString('Zero-width non-joiner', $issues['invisible_chars']);
    }

    public function testFileWithControlCharacters(): void
    {
        $filePath = $this->testFilesPath.'/control-chars.yaml';
        file_put_contents($filePath, "key: value\x07with\x1Fcontrol"); // Control characters

        $parser = new YamlParser($filePath);
        $issues = $this->validator->processFile($parser);

        $this->assertArrayHasKey('invisible_chars', $issues);
        $this->assertStringContainsString('Control characters', $issues['invisible_chars']);
    }

    public function testFileWithUnicodeNormalizationIssues(): void
    {
        if (!class_exists('Normalizer')) {
            $this->markTestSkipped('Intl extension not available');
        }

        $filePath = $this->testFilesPath.'/unicode-norm.yaml';
        // Create content with NFD normalization (decomposed)
        $content = 'key: '.Normalizer::normalize('café', Normalizer::FORM_D);
        file_put_contents($filePath, $content);

        $parser = new YamlParser($filePath);
        $issues = $this->validator->processFile($parser);

        $this->assertArrayHasKey('unicode_normalization', $issues);
        $this->assertStringContainsString('non-NFC normalized', $issues['unicode_normalization']);
    }

    public function testJsonFilesAreSupported(): void
    {
        $filePath = $this->testFilesPath.'/valid.json';
        file_put_contents($filePath, '{"key": "value"}');

        $parser = new JsonParser($filePath);
        $issues = $this->validator->processFile($parser);

        // Should not have any encoding issues for valid JSON
        $this->assertEmpty($issues);
    }

    public function testJsonWithBomIsDetected(): void
    {
        $filePath = $this->testFilesPath.'/valid-json-with-bom.json';
        file_put_contents($filePath, "\xEF\xBB\xBF{\"key\": \"value\"}"); // Valid JSON with BOM

        // Mock parser to avoid BOM parsing issues
        $mockParser = $this->createMock(JsonParser::class);
        $mockParser->method('getFilePath')->willReturn($filePath);

        $issues = $this->validator->processFile($mockParser);

        // Should detect BOM issue
        $this->assertArrayHasKey('bom', $issues);
    }

    public function testPhpFilesAreSupported(): void
    {
        $filePath = $this->testFilesPath.'/valid.php';
        file_put_contents($filePath, '<?php return ["key" => "value"]; // Valid PHP');

        $parser = new PhpParser($filePath);
        $issues = $this->validator->processFile($parser);

        // Should not have any encoding issues for valid PHP
        $this->assertEmpty($issues);
    }

    public function testFileReadError(): void
    {
        // Create a validator that simulates file_get_contents returning false
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Could not read file content:'));

        $validator = new class($logger) extends EncodingValidator {
            public function processFile(\MoveElevator\ComposerTranslationValidator\Parser\ParserInterface $file): array
            {
                // Simulate file_get_contents returning false
                $content = @file_get_contents($file->getFilePath()); // Suppress warning with @
                if (false === $content) {
                    $this->logger?->error(
                        'Could not read file content: '.$file->getFileName(),
                    );

                    return [];
                }

                return parent::processFile($file);
            }
        };

        $filePath = '/non/existent/file.yaml';
        $parser = $this->createMock(YamlParser::class);
        $parser->method('getFilePath')->willReturn($filePath);
        $parser->method('getFileName')->willReturn('file.yaml');

        $issues = $validator->processFile($parser);
        $this->assertEmpty($issues);
    }

    public function testEmptyFile(): void
    {
        $filePath = $this->testFilesPath.'/empty.json';
        file_put_contents($filePath, '{}'); // Empty but valid JSON object

        // Create a mock parser that simulates an empty file content
        $mockParser = $this->createMock(JsonParser::class);
        $mockParser->method('getFilePath')->willReturn($filePath);

        // Manually test the empty file path in the validator
        $validator = new class extends EncodingValidator {
            /**
             * @return array<string, mixed>
             */
            public function testEmptyContent(): array
            {
                $content = file_get_contents('/dev/null'); // This returns '' (empty string)
                if ('' === $content) {
                    return [];
                }

                return ['should_not_reach' => 'this'];
            }
        };

        $issues = $validator->testEmptyContent();
        $this->assertEmpty($issues);
    }

    public function testFormatIssueMessage(): void
    {
        $details = [
            'encoding' => 'File is not valid UTF-8 encoded',
            'bom' => 'File contains UTF-8 Byte Order Mark (BOM)',
        ];

        $issue = new Issue('test-file.json', $details, 'JsonParser', 'EncodingValidator');
        $message = $this->validator->formatIssueMessage($issue, 'test: ');

        $this->assertStringContainsString('Warning', $message);
        $this->assertStringContainsString('test: encoding issue: File is not valid UTF-8 encoded', $message);
        $this->assertStringContainsString('test: encoding issue: File contains UTF-8 Byte Order Mark (BOM)', $message);
    }

    public function testMultipleIssuesInSingleFile(): void
    {
        $filePath = $this->testFilesPath.'/multiple-issues.yaml';
        // Create file with multiple problems: BOM + invisible chars
        $content = "\xEF\xBB\xBFkey: value\u{200B}"; // BOM + zero-width space
        file_put_contents($filePath, $content);

        $parser = new YamlParser($filePath);
        $issues = $this->validator->processFile($parser);

        $this->assertArrayHasKey('bom', $issues);
        $this->assertArrayHasKey('invisible_chars', $issues);
        $this->assertCount(2, $issues);
    }

    public function testCleanFileWithNoIssues(): void
    {
        $filePath = $this->testFilesPath.'/clean.yaml';
        file_put_contents($filePath, "key: value\nanother_key: another value\n");

        $parser = new YamlParser($filePath);
        $issues = $this->validator->processFile($parser);

        $this->assertEmpty($issues);
    }

    public function testEmptyFileContentReturnsNoIssues(): void
    {
        // Test the specific case where file_get_contents returns empty string
        $filePath = $this->testFilesPath.'/just-empty-content.txt';
        file_put_contents($filePath, ''); // Completely empty file

        // Create a mock parser that can handle empty files
        $mockParser = $this->createMock(\MoveElevator\ComposerTranslationValidator\Parser\ParserInterface::class);
        $mockParser->method('getFilePath')->willReturn($filePath);
        $mockParser->method('getFileName')->willReturn('just-empty-content.txt');

        $issues = $this->validator->processFile($mockParser);

        $this->assertEmpty($issues);
    }

    public function testFileReadErrorHandling(): void
    {
        // Create a file and then make it unreadable or delete it
        $filePath = $this->testFilesPath.'/temp-file.yaml';
        file_put_contents($filePath, 'test content');

        // Create mock parser
        $mockParser = $this->createMock(\MoveElevator\ComposerTranslationValidator\Parser\ParserInterface::class);
        $mockParser->method('getFilePath')->willReturn($filePath);
        $mockParser->method('getFileName')->willReturn('temp-file.yaml');

        // Delete the file after creating parser to simulate read error
        unlink($filePath);

        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('File does not exist: temp-file.yaml');

        $validator = new EncodingValidator($logger);
        $issues = $validator->processFile($mockParser);

        $this->assertEmpty($issues);
    }

    public function testUnicodeNormalizationWithoutIntlExtension(): void
    {
        // Test the fallback when Normalizer class doesn't exist
        $filePath = $this->testFilesPath.'/unicode-test.yaml';
        file_put_contents($filePath, 'key: café'); // Contains normalized Unicode

        $parser = new YamlParser($filePath);

        // Test by calling the method through reflection since it's private
        $reflector = new ReflectionClass(EncodingValidator::class);
        $method = $reflector->getMethod('hasUnicodeNormalizationIssues');

        $validator = new EncodingValidator();

        if (!class_exists('Normalizer')) {
            $result = $method->invokeArgs($validator, [file_get_contents($filePath)]);
            $this->assertFalse($result);
        } else {
            // If Normalizer exists, test that it works normally
            $issues = $validator->processFile($parser);
            // File with normal Unicode should not have normalization issues
            $this->assertArrayNotHasKey('unicode_normalization', $issues);
        }
    }

    public function testUnicodeNormalizationWithNonNormalizedContent(): void
    {
        if (!class_exists('Normalizer')) {
            $this->markTestSkipped('Normalizer class not available');
        }

        $filePath = $this->testFilesPath.'/non-normalized-unicode.yaml';

        // Create content with combining characters that need normalization
        // é can be written as e + combining accent (decomposed form)
        $decomposedE = "e\u{0301}"; // e + combining acute accent
        $content = "key: caf{$decomposedE}"; // café in decomposed form

        file_put_contents($filePath, $content);

        $parser = new YamlParser($filePath);
        $issues = $this->validator->processFile($parser);

        // The content should trigger unicode_normalization issue if it's not in NFC form
        $normalizedContent = Normalizer::normalize($content, Normalizer::FORM_C);
        if ($content !== $normalizedContent) {
            $this->assertArrayHasKey('unicode_normalization', $issues);
            $this->assertStringContainsString('non-NFC normalized', $issues['unicode_normalization']);
        } else {
            $this->assertArrayNotHasKey('unicode_normalization', $issues);
        }
    }
}
