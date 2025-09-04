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

namespace MoveElevator\ComposerTranslationValidator\Tests\Validator;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Parser\JsonParser;
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Parser\PhpParser;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use MoveElevator\ComposerTranslationValidator\Parser\YamlParser;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use MoveElevator\ComposerTranslationValidator\Validator\HtmlTagValidator;
use MoveElevator\ComposerTranslationValidator\Validator\ResultType;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 *
 * @see https://google.de
 *
 * @package ComposerTranslationValidator
 */

final class HtmlTagValidatorTest extends TestCase
{
    public function testProcessFileWithValidFile(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1', 'key2']);
        $parser->method('getContentByKey')->willReturnMap([
            ['key1', 'Hello <strong>world</strong>!'],
            ['key2', 'Welcome <em>user</em>'],
        ]);
        $parser->method('getFileName')->willReturn('test.xlf');

        $validator = new HtmlTagValidator();
        $result = $validator->processFile($parser);

        $this->assertEmpty($result);
    }

    public function testProcessFileWithInvalidFile(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(null);
        $parser->method('getFileName')->willReturn('invalid.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('The source file invalid.xlf is not valid.');

        $validator = new HtmlTagValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertEmpty($result);
    }

    public function testAnalyzeHtmlStructure(): void
    {
        $validator = new HtmlTagValidator();
        $reflection = new ReflectionClass($validator);
        $method = $reflection->getMethod('analyzeHtmlStructure');
        $method->setAccessible(true);

        // Test with valid HTML
        $structure = $method->invoke($validator, 'Hello <strong class="highlight">world</strong>!');

        $this->assertEquals(['strong'], $structure['tags']);
        $this->assertEmpty($structure['structure_errors']);
        $this->assertSame(['highlight'], array_values($structure['attributes']['strong']));

        // Test with self-closing tag
        $structure = $method->invoke($validator, 'Line break <br/> here');

        $this->assertEquals(['br'], $structure['self_closing_tags']);
        $this->assertEmpty($structure['structure_errors']);

        // Test with unclosed tag
        $structure = $method->invoke($validator, 'Unclosed <div>content');

        $this->assertContains('Unclosed tag: <div>', $structure['structure_errors']);

        // Test with unmatched closing tag
        $structure = $method->invoke($validator, 'Unmatched </span> tag');

        $this->assertContains('Unmatched closing tag: </span>', $structure['structure_errors']);
    }

    public function testPostProcessWithConsistentHtml(): void
    {
        $parser1 = $this->createMock(ParserInterface::class);
        $parser1->method('extractKeys')->willReturn(['greeting']);
        $parser1->method('getContentByKey')->willReturn('Hello <strong>world</strong>!');
        $parser1->method('getFileName')->willReturn('en.xlf');

        $parser2 = $this->createMock(ParserInterface::class);
        $parser2->method('extractKeys')->willReturn(['greeting']);
        $parser2->method('getContentByKey')->willReturn('Hallo <strong>Welt</strong>!');
        $parser2->method('getFileName')->willReturn('de.xlf');

        $validator = new HtmlTagValidator();
        $validator->processFile($parser1);
        $validator->processFile($parser2);
        $validator->postProcess();

        $this->assertFalse($validator->hasIssues());
    }

    public function testPostProcessWithInconsistentHtml(): void
    {
        $parser1 = $this->createMock(ParserInterface::class);
        $parser1->method('extractKeys')->willReturn(['greeting']);
        $parser1->method('getContentByKey')->willReturn('Hello <strong>world</strong>!');
        $parser1->method('getFileName')->willReturn('en.xlf');

        $parser2 = $this->createMock(ParserInterface::class);
        $parser2->method('extractKeys')->willReturn(['greeting']);
        $parser2->method('getContentByKey')->willReturn('Hallo <b>Welt</b>!');
        $parser2->method('getFileName')->willReturn('de.xlf');

        $validator = new HtmlTagValidator();
        $validator->processFile($parser1);
        $validator->processFile($parser2);
        $validator->postProcess();

        $this->assertTrue($validator->hasIssues());
        $issues = $validator->getIssues();
        $this->assertCount(1, $issues);

        $issue = $issues[0];
        $details = $issue->getDetails();
        $this->assertEquals('greeting', $details['key']);
        $this->assertNotEmpty($details['inconsistencies']);
    }

    public function testPostProcessWithHtmlErrors(): void
    {
        $parser1 = $this->createMock(ParserInterface::class);
        $parser1->method('extractKeys')->willReturn(['greeting']);
        $parser1->method('getContentByKey')->willReturn('Hello <strong>world</strong>!');
        $parser1->method('getFileName')->willReturn('en.xlf');

        $parser2 = $this->createMock(ParserInterface::class);
        $parser2->method('extractKeys')->willReturn(['greeting']);
        $parser2->method('getContentByKey')->willReturn('Hallo <strong>Welt!');
        $parser2->method('getFileName')->willReturn('de.xlf');

        $validator = new HtmlTagValidator();
        $validator->processFile($parser1);
        $validator->processFile($parser2);
        $validator->postProcess();

        $this->assertTrue($validator->hasIssues());
        $issues = $validator->getIssues();
        $this->assertCount(1, $issues);

        $issue = $issues[0];
        $details = $issue->getDetails();
        $inconsistencies = $details['inconsistencies'];
        $this->assertStringContainsString('HTML structure errors', implode(' ', $inconsistencies));
    }

    public function testSupportsParser(): void
    {
        $validator = new HtmlTagValidator();
        $supportedParsers = $validator->supportsParser();

        $this->assertContains(XliffParser::class, $supportedParsers);
        $this->assertContains(YamlParser::class, $supportedParsers);
        $this->assertContains(JsonParser::class, $supportedParsers);
        $this->assertContains(PhpParser::class, $supportedParsers);
    }

    public function testResultTypeOnValidationFailure(): void
    {
        $validator = new HtmlTagValidator();
        $resultType = $validator->resultTypeOnValidationFailure();

        $this->assertSame(ResultType::WARNING, $resultType);
    }

    public function testShouldShowDetailedOutput(): void
    {
        $validator = new HtmlTagValidator();
        $this->assertTrue($validator->shouldShowDetailedOutput());
    }

    public function testFormatIssueMessage(): void
    {
        $validator = new HtmlTagValidator();
        $issue = new Issue(
            'test.xlf',
            [
                'key' => 'greeting',
                'inconsistencies' => ['File \'de.xlf\' has different HTML tags'],
            ],
            '',
            'HtmlTagValidator',
        );

        $message = $validator->formatIssueMessage($issue, 'test: ');

        $this->assertStringContainsString('Warning', $message);
        $this->assertStringContainsString('test: HTML tag inconsistency', $message);
        $this->assertStringContainsString('greeting', $message);
    }

    public function testDistributeIssuesForDisplay(): void
    {
        $validator = new HtmlTagValidator();
        $fileSet = new FileSet('TestParser', '/test/path', 'test-set', []);

        $issue = new Issue(
            '',
            [
                'key' => 'greeting',
                'files' => [
                    '/test/path/en.xlf' => ['value' => 'Hello <strong>world</strong>!'],
                    '/test/path/de.xlf' => ['value' => 'Hallo <b>Welt</b>!'],
                ],
                'inconsistencies' => ['HTML tag mismatch'],
            ],
            '',
            'HtmlTagValidator',
        );

        $validator->addIssue($issue);
        $distribution = $validator->distributeIssuesForDisplay($fileSet);

        $this->assertCount(2, $distribution);
        $this->assertArrayHasKey('/test/path/en.xlf', $distribution);
        $this->assertArrayHasKey('/test/path/de.xlf', $distribution);
    }

    public function testRenderDetailedOutput(): void
    {
        $validator = new HtmlTagValidator();
        $output = new BufferedOutput();

        $issue = new Issue(
            '',
            [
                'key' => 'greeting',
                'files' => [
                    'en.xlf' => ['value' => 'Hello <strong>world</strong>!'],
                    'de.xlf' => ['value' => 'Hallo <b>Welt</b>!'],
                ],
                'inconsistencies' => ['HTML tag mismatch'],
            ],
            '',
            'HtmlTagValidator',
        );

        $validator->renderDetailedOutput($output, [$issue]);
        $outputContent = $output->fetch();

        $this->assertStringContainsString('greeting', $outputContent);
        $this->assertStringContainsString('en.xlf', $outputContent);
        $this->assertStringContainsString('de.xlf', $outputContent);
    }

    public function testResetState(): void
    {
        $validator = new HtmlTagValidator();
        $reflection = new ReflectionClass($validator);
        $keyDataProperty = $reflection->getProperty('keyData');
        $keyDataProperty->setAccessible(true);

        // Add some data
        $keyDataProperty->setValue($validator, ['test' => 'data']);
        $validator->addIssue(new Issue('test.xlf', [], '', 'HtmlTagValidator'));

        // Reset state
        $resetMethod = $reflection->getMethod('resetState');
        $resetMethod->setAccessible(true);
        $resetMethod->invoke($validator);

        // Check that data is cleared
        $this->assertEmpty($keyDataProperty->getValue($validator));
        $this->assertFalse($validator->hasIssues());
    }
}
