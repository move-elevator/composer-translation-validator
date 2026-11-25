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

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Parser\ParserInterface;
use MoveElevator\ComposerTranslationValidator\Result\Issue;
use MoveElevator\ComposerTranslationValidator\Validator\HtmlTagValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * HtmlTagValidatorTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class HtmlTagValidatorTest extends TestCase
{
    public function testProcessFileWithValidFile(): void
    {
        $parser = self::createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1', 'key2']);
        $parser->method('getContentByKey')->willReturnMap([
            ['key1', 'Hello <strong>world</strong>!'],
            ['key2', 'Welcome <em>user</em>'],
        ]);
        $parser->method('getFileName')->willReturn('test.xlf');

        $validator = new HtmlTagValidator();
        $result = $validator->processFile($parser);

        self::assertEmpty($result);
    }

    public function testProcessFileWithInvalidFile(): void
    {
        $parser = self::createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(null);
        $parser->method('getFileName')->willReturn('invalid.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with('The source file invalid.xlf is not valid.');

        $validator = new HtmlTagValidator($logger);
        $result = $validator->processFile($parser);

        self::assertEmpty($result);
    }

    public function testAnalyzeHtmlStructure(): void
    {
        $validator = new HtmlTagValidator();
        $reflection = new ReflectionClass($validator);
        $method = $reflection->getMethod('analyzeHtmlStructure');

        // Test with valid HTML
        $structure = $method->invoke($validator, 'Hello <strong class="highlight">world</strong>!');

        self::assertEquals(['strong'], $structure['tags']);
        self::assertEmpty($structure['structure_errors']);
        self::assertSame(['highlight'], array_values($structure['attributes']['strong']));

        // Test with self-closing tag
        $structure = $method->invoke($validator, 'Line break <br/> here');

        self::assertEquals(['br'], $structure['self_closing_tags']);
        self::assertEmpty($structure['structure_errors']);

        // Test with unclosed tag
        $structure = $method->invoke($validator, 'Unclosed <div>content');

        self::assertContains('Unclosed tag: <div>', $structure['structure_errors']);

        // Test with unmatched closing tag
        $structure = $method->invoke($validator, 'Unmatched </span> tag');

        self::assertContains('Unmatched closing tag: </span>', $structure['structure_errors']);
    }

    public function testVoidElementsAreNotReportedAsUnclosed(): void
    {
        $validator = new HtmlTagValidator();
        $reflection = new ReflectionClass($validator);
        $method = $reflection->getMethod('analyzeHtmlStructure');

        // Non-self-closing void element (HTML5 syntax, e.g. from CDATA)
        $structure = $method->invoke($validator, 'Test<br>Test');
        self::assertEmpty($structure['structure_errors']);
        self::assertContains('br', $structure['tags']);

        // <br>, <br/> and <br /> must be treated equivalently (no errors)
        foreach (['Line<br>here', 'Line<br/>here', 'Line<br />here'] as $value) {
            $structure = $method->invoke($validator, $value);
            self::assertEmpty($structure['structure_errors'], "Unexpected error for: {$value}");
            self::assertContains('br', $structure['tags']);
        }

        // Void element with attributes
        $structure = $method->invoke($validator, 'Image <img src="x.png"> done');
        self::assertEmpty($structure['structure_errors']);
        self::assertContains('img', $structure['tags']);
        self::assertSame('x.png', $structure['attributes']['img']['src'] ?? null);

        // Regression guard: real non-void tags must still be flagged
        $structure = $method->invoke($validator, 'Unclosed <div>content');
        self::assertContains('Unclosed tag: <div>', $structure['structure_errors']);
    }

    public function testProcessFileSkipsKeysWithNullValue(): void
    {
        $parser = self::createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1', 'key2']);
        $parser->method('getContentByKey')->willReturnMap([
            ['key1', 'Hello <strong>world</strong>!'],
            ['key2', null],
        ]);
        $parser->method('getFileName')->willReturn('test.xlf');

        $validator = new HtmlTagValidator();
        $validator->processFile($parser);

        $reflection = new ReflectionClass($validator);
        $keyData = $reflection->getProperty('keyData')->getValue($validator);

        self::assertArrayHasKey('key1', $keyData);
        self::assertArrayNotHasKey('key2', $keyData);
    }

    public function testRenderDetailedOutputWithEmptyIssues(): void
    {
        $validator = new HtmlTagValidator();
        $output = new BufferedOutput();

        $validator->renderDetailedOutput($output, []);

        self::assertSame('', $output->fetch());
    }

    public function testFindHtmlInconsistenciesWithSingleFileReturnsEmpty(): void
    {
        $parser = self::createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['greeting']);
        $parser->method('getContentByKey')->willReturn('Hello <strong>world</strong>!');
        $parser->method('getFileName')->willReturn('en.xlf');

        // Only one file carries this key, so there is nothing to compare against.
        $validator = new HtmlTagValidator();
        $validator->processFile($parser);
        $validator->postProcess();

        self::assertFalse($validator->hasIssues());
    }

    public function testPostProcessDetectsClassAttributeDifference(): void
    {
        $parser1 = self::createStub(ParserInterface::class);
        $parser1->method('extractKeys')->willReturn(['link']);
        $parser1->method('getContentByKey')->willReturn('See <a class="primary">here</a>');
        $parser1->method('getFileName')->willReturn('en.xlf');

        $parser2 = self::createStub(ParserInterface::class);
        $parser2->method('extractKeys')->willReturn(['link']);
        $parser2->method('getContentByKey')->willReturn('Siehe <a class="secondary">hier</a>');
        $parser2->method('getFileName')->willReturn('de.xlf');

        $validator = new HtmlTagValidator();
        $validator->processFile($parser1);
        $validator->processFile($parser2);
        $validator->postProcess();

        self::assertTrue($validator->hasIssues());
        $issues = $validator->getIssues();
        self::assertCount(1, $issues);

        $inconsistencies = $issues[0]->getDetails()['inconsistencies'];
        self::assertStringContainsString(
            'different class attribute for <a>',
            implode(' ', $inconsistencies),
        );
    }

    public function testPostProcessWithConsistentHtml(): void
    {
        $parser1 = self::createStub(ParserInterface::class);
        $parser1->method('extractKeys')->willReturn(['greeting']);
        $parser1->method('getContentByKey')->willReturn('Hello <strong>world</strong>!');
        $parser1->method('getFileName')->willReturn('en.xlf');

        $parser2 = self::createStub(ParserInterface::class);
        $parser2->method('extractKeys')->willReturn(['greeting']);
        $parser2->method('getContentByKey')->willReturn('Hallo <strong>Welt</strong>!');
        $parser2->method('getFileName')->willReturn('de.xlf');

        $validator = new HtmlTagValidator();
        $validator->processFile($parser1);
        $validator->processFile($parser2);
        $validator->postProcess();

        self::assertFalse($validator->hasIssues());
    }

    public function testPostProcessWithInconsistentHtml(): void
    {
        $parser1 = self::createStub(ParserInterface::class);
        $parser1->method('extractKeys')->willReturn(['greeting']);
        $parser1->method('getContentByKey')->willReturn('Hello <strong>world</strong>!');
        $parser1->method('getFileName')->willReturn('en.xlf');

        $parser2 = self::createStub(ParserInterface::class);
        $parser2->method('extractKeys')->willReturn(['greeting']);
        $parser2->method('getContentByKey')->willReturn('Hallo <b>Welt</b>!');
        $parser2->method('getFileName')->willReturn('de.xlf');

        $validator = new HtmlTagValidator();
        $validator->processFile($parser1);
        $validator->processFile($parser2);
        $validator->postProcess();

        self::assertTrue($validator->hasIssues());
        $issues = $validator->getIssues();
        self::assertCount(1, $issues);

        $issue = $issues[0];
        $details = $issue->getDetails();
        self::assertEquals('greeting', $details['key']);
        self::assertNotEmpty($details['inconsistencies']);
    }

    public function testPostProcessWithHtmlErrors(): void
    {
        $parser1 = self::createStub(ParserInterface::class);
        $parser1->method('extractKeys')->willReturn(['greeting']);
        $parser1->method('getContentByKey')->willReturn('Hello <strong>world</strong>!');
        $parser1->method('getFileName')->willReturn('en.xlf');

        $parser2 = self::createStub(ParserInterface::class);
        $parser2->method('extractKeys')->willReturn(['greeting']);
        $parser2->method('getContentByKey')->willReturn('Hallo <strong>Welt!');
        $parser2->method('getFileName')->willReturn('de.xlf');

        $validator = new HtmlTagValidator();
        $validator->processFile($parser1);
        $validator->processFile($parser2);
        $validator->postProcess();

        self::assertTrue($validator->hasIssues());
        $issues = $validator->getIssues();
        self::assertCount(1, $issues);

        $issue = $issues[0];
        $details = $issue->getDetails();
        $inconsistencies = $details['inconsistencies'];
        self::assertStringContainsString('HTML structure errors', implode(' ', $inconsistencies));
    }

    public function testShouldShowDetailedOutput(): void
    {
        $validator = new HtmlTagValidator();
        self::assertTrue($validator->shouldShowDetailedOutput());
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

        self::assertStringContainsString('Warning', $message);
        self::assertStringContainsString('test: HTML tag inconsistency', $message);
        self::assertStringContainsString('greeting', $message);
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

        self::assertCount(2, $distribution);
        self::assertArrayHasKey('/test/path/en.xlf', $distribution);
        self::assertArrayHasKey('/test/path/de.xlf', $distribution);
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

        self::assertStringContainsString('greeting', $outputContent);
        self::assertStringContainsString('en.xlf', $outputContent);
        self::assertStringContainsString('de.xlf', $outputContent);
    }

    public function testDetailedOutputDoesNotHighlightPlainTextStartingWithAngleBracket(): void
    {
        $validator = new HtmlTagValidator();
        $output = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, true);
        $issue = new Issue(
            '',
            [
                'key' => 'greeting',
                'files' => [
                    'en.xlf' => ['value' => '<3 love <b>this</b>'],
                    'de.xlf' => ['value' => 'Hallo'],
                ],
                'inconsistencies' => ['HTML tag mismatch'],
            ],
            '',
            'HtmlTagValidator',
        );

        $validator->renderDetailedOutput($output, [$issue]);
        $outputContent = $output->fetch();

        self::assertStringContainsString("\e[36m<b>\e[39m", $outputContent);
        self::assertStringNotContainsString("\e[36m<3 love", $outputContent);
    }

    public function testResetState(): void
    {
        $validator = new HtmlTagValidator();
        $reflection = new ReflectionClass($validator);
        $keyDataProperty = $reflection->getProperty('keyData');

        // Add some data
        $keyDataProperty->setValue($validator, ['test' => 'data']);
        $validator->addIssue(new Issue('test.xlf', [], '', 'HtmlTagValidator'));

        // Reset state
        $resetMethod = $reflection->getMethod('resetState');
        $resetMethod->invoke($validator);

        // Check that data is cleared
        self::assertEmpty($keyDataProperty->getValue($validator));
        self::assertFalse($validator->hasIssues());
    }
}
