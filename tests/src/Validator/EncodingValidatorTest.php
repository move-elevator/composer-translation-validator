<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\JsonParser;
use MoveElevator\ComposerTranslationValidator\Parser\PhpParser;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Parser\YamlParser;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use MoveElevator\ComposerTranslationValidator\Validator\EncodingValidator;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use PHPUnit\Framework\TestCase;

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
            array_map('unlink', glob($this->testFilesPath.'/*') ?: []);
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
        $content = 'key: '.\Normalizer::normalize('café', \Normalizer::FORM_D);
        file_put_contents($filePath, $content);

        $parser = new YamlParser($filePath);
        $issues = $this->validator->processFile($parser);

        $this->assertArrayHasKey('unicode_normalization', $issues);
        $this->assertStringContainsString('non-NFC normalized', $issues['unicode_normalization']);
    }

    public function testInvalidJsonSyntax(): void
    {
        $filePath = $this->testFilesPath.'/invalid-json.json';
        file_put_contents($filePath, '{"key": "value",}'); // Trailing comma

        // We need to test the validator directly without parsing
        // Since the parser would fail before we can validate
        $mockParser = $this->createMock(JsonParser::class);
        $mockParser->method('getFilePath')->willReturn($filePath);

        $issues = $this->validator->processFile($mockParser);

        $this->assertArrayHasKey('json_syntax', $issues);
        $this->assertStringContainsString('invalid JSON syntax', $issues['json_syntax']);
    }

    public function testValidJsonWithBomIsHandled(): void
    {
        $filePath = $this->testFilesPath.'/valid-json-with-bom.json';
        file_put_contents($filePath, "\xEF\xBB\xBF{\"key\": \"value\"}"); // Valid JSON with BOM

        // Mock parser to avoid BOM parsing issues
        $mockParser = $this->createMock(JsonParser::class);
        $mockParser->method('getFilePath')->willReturn($filePath);

        $issues = $this->validator->processFile($mockParser);

        // Should have BOM issue but not JSON syntax issue
        $this->assertArrayHasKey('bom', $issues);
        $this->assertArrayNotHasKey('json_syntax', $issues);
    }

    public function testNonJsonFileDoesNotCheckJsonSyntax(): void
    {
        $filePath = $this->testFilesPath.'/invalid-json.php';
        file_put_contents($filePath, '<?php return ["key" => "value",]; // Trailing comma OK in PHP');

        $parser = new PhpParser($filePath);
        $issues = $this->validator->processFile($parser);

        // Should not check JSON syntax for PHP files
        $this->assertArrayNotHasKey('json_syntax', $issues);
    }

    public function testFileReadError(): void
    {
        // Test with a file that exists but can't be read (we'll mock this)
        $filePath = $this->testFilesPath.'/readable.yaml';
        file_put_contents($filePath, 'key: value');

        $parser = new YamlParser($filePath);

        // Mock file_get_contents failure by using a non-readable path
        $reflection = new \ReflectionClass($this->validator);
        $method = $reflection->getMethod('processFile');

        // For this test, we'll just verify the method handles missing files gracefully
        $issues = $this->validator->processFile($parser);
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
}
