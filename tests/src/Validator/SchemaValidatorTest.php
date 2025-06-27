<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Validator\SchemaValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

final class SchemaValidatorTest extends TestCase
{
    private string $tempDir;
    private string $validXliffFile;
    private string $invalidXliffFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().'/schema_validator_test_'.uniqid();
        mkdir($this->tempDir);

        $this->validXliffFile = $this->tempDir.'/valid.xlf';
        file_put_contents($this->validXliffFile, <<<'EOT'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
  <file source-language="en" datatype="plaintext" original="messages.en.xlf">
    <body>
      <trans-unit id="key1">
        <source>Source 1</source>
        <target>Target 1</target>
      </trans-unit>
    </body>
  </file>
</xliff>
EOT
        );

        $this->invalidXliffFile = $this->tempDir.'/invalid.xlf';
        file_put_contents($this->invalidXliffFile, <<<'EOT'
<?xml version="1.0" encoding="utf-8"?>
<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2">
  <file source-language="en" datatype="plaintext" original="messages.en.xlf">
    <body>
      <trans-unit id="key1">
        <source>Source 1</source>
        <target>Target 1</target>
      </trans-unit>
    </body>
  </file>
</xliff>
EOT
        ); // Missing version attribute
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

    public function testProcessFileWithValidXliff(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('getFilePath')->willReturn($this->validXliffFile);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $validator = new SchemaValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertEmpty($result);
    }

    public function testProcessFileWithInvalidXliff(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('getFilePath')->willReturn($this->invalidXliffFile);

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new SchemaValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('message', $result[0]);
        $this->assertStringContainsString("The attribute 'version' is required but missing.", $result[0]['message']);
    }

    public function testProcessFileWithNonExistentFile(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('getFilePath')->willReturn('/non/existent/file.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to validate XML schema: '));

        $validator = new SchemaValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertEmpty($result);
    }

    public function testRenderIssueSets(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = new BufferedOutput();

        $issueSets = [
            [
                'file1.xlf' => [
                    [
                        'level' => LIBXML_ERR_ERROR,
                        'code' => 76,
                        'message' => 'Element \'trans-unit\' was not closed.',
                        'line' => 10,
                    ],
                ],
                'file2.xlf' => [
                    [
                        'level' => LIBXML_ERR_WARNING,
                        'code' => 77,
                        'message' => 'Some warning.',
                        'line' => 5,
                    ],
                ],
            ],
        ];

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new SchemaValidator($logger);
        $validator->renderIssueSets($input, $output, $issueSets);

        $expectedOutput = <<<'EOT'
+-----------+---------+------+-----------------+------+
| File      | Level   | Code | Message         | Line |
+-----------+---------+------+-----------------+------+
| file1.xlf | ERROR   | 76   | was not closed. | 10   |
+-----------+---------+------+-----------------+------+
| file2.xlf | WARNING | 77   | Some warning.   | 5    |
+-----------+---------+------+-----------------+------+
EOT;

        $this->assertSame(trim($expectedOutput), trim($output->fetch()));
    }

    public function testExplain(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new SchemaValidator($logger);

        $this->assertStringContainsString('XML schema', $validator->explain());
    }
}
