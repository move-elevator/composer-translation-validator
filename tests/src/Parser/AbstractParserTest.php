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

namespace MoveElevator\ComposerTranslationValidator\Tests\Parser;

use InvalidArgumentException;
use MoveElevator\ComposerTranslationValidator\Parser\AbstractParser;
use PHPUnit\Framework\TestCase;
use RuntimeException;

// Concrete test class for testing AbstractParser

/**
 * TestParser.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
class TestParser extends AbstractParser
{
    public static function getSupportedFileExtensions(): array
    {
        return ['txt', 'test'];
    }
}

/**
 * AbstractParserTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class AbstractParserTest extends TestCase
{
    private string $tempDir;
    private string $tempFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir().'/abstract_parser_test_'.uniqid('', true);
        mkdir($this->tempDir, 0777, true);
        $this->tempFile = $this->tempDir.'/test.txt';
        file_put_contents($this->tempFile, 'test content');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    public function testConstructorWithValidFile(): void
    {
        $parser = new TestParser($this->tempFile);

        $this->assertSame('test.txt', $parser->getFileName());
        $this->assertSame($this->tempFile, $parser->getFilePath());
        $this->assertSame($this->tempDir.\DIRECTORY_SEPARATOR, $parser->getFileDirectory());
    }

    public function testConstructorWithNonExistentFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File "/non/existent/file.txt" does not exist.');

        new TestParser('/non/existent/file.txt');
    }

    public function testConstructorWithUnreadableFile(): void
    {
        $unreadableFile = $this->tempDir.'/unreadable.txt';
        file_put_contents($unreadableFile, 'test content');
        chmod($unreadableFile, 0000);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File "'.$unreadableFile.'" is not readable.');

        try {
            new TestParser($unreadableFile);
        } finally {
            chmod($unreadableFile, 0644);
            unlink($unreadableFile);
        }
    }

    public function testConstructorWithInvalidFileExtension(): void
    {
        $invalidFile = $this->tempDir.'/invalid.xyz';
        file_put_contents($invalidFile, 'test content');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('File "'.$invalidFile.'" is not a valid file.');

        try {
            new TestParser($invalidFile);
        } finally {
            unlink($invalidFile);
        }
    }

    public function testGetSupportedFileExtensions(): void
    {
        $this->assertSame(['txt', 'test'], TestParser::getSupportedFileExtensions());
    }

    public function testGetFileName(): void
    {
        $parser = new TestParser($this->tempFile);
        $this->assertSame('test.txt', $parser->getFileName());
    }

    public function testGetFileDirectory(): void
    {
        $parser = new TestParser($this->tempFile);
        $this->assertSame($this->tempDir.\DIRECTORY_SEPARATOR, $parser->getFileDirectory());
    }

    public function testGetFilePath(): void
    {
        $parser = new TestParser($this->tempFile);
        $this->assertSame($this->tempFile, $parser->getFilePath());
    }

    public function testConstructorWithDifferentSupportedExtension(): void
    {
        $testFile = $this->tempDir.'/test.test';
        file_put_contents($testFile, 'test content');

        $parser = new TestParser($testFile);

        $this->assertSame('test.test', $parser->getFileName());

        unlink($testFile);
    }
}
