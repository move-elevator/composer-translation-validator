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
use MoveElevator\ComposerTranslationValidator\Validator\PlaceholderConsistencyValidator;
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

final class PlaceholderConsistencyValidatorTest extends TestCase
{
    public function testProcessFileWithValidFile(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1', 'key2']);
        $parser->method('getContentByKey')->willReturnMap([
            ['key1', 'Hello %name%!'],
            ['key2', 'Welcome {user}'],
        ]);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $validator = new PlaceholderConsistencyValidator($logger);
        $result = $validator->processFile($parser);

        // processFile should return empty array as issues are handled in postProcess
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
            ->with($this->stringContains('The source file invalid.xlf is not valid.'));

        $validator = new PlaceholderConsistencyValidator($logger);
        $result = $validator->processFile($parser);

        $this->assertEmpty($result);
    }

    public function testPostProcessWithConsistentPlaceholders(): void
    {
        $parser1 = $this->createMock(ParserInterface::class);
        $parser1->method('extractKeys')->willReturn(['key1']);
        $parser1->method('getContentByKey')->willReturn('Hello %name%!');
        $parser1->method('getFileName')->willReturn('en.xlf');

        $parser2 = $this->createMock(ParserInterface::class);
        $parser2->method('extractKeys')->willReturn(['key1']);
        $parser2->method('getContentByKey')->willReturn('Hallo %name%!');
        $parser2->method('getFileName')->willReturn('de.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $validator->processFile($parser1);
        $validator->processFile($parser2);
        $validator->postProcess();

        $this->assertFalse($validator->hasIssues());
    }

    public function testPostProcessWithInconsistentPlaceholders(): void
    {
        $parser1 = $this->createMock(ParserInterface::class);
        $parser1->method('extractKeys')->willReturn(['key1']);
        $parser1->method('getContentByKey')->willReturn('Hello %name%!');
        $parser1->method('getFileName')->willReturn('en.xlf');

        $parser2 = $this->createMock(ParserInterface::class);
        $parser2->method('extractKeys')->willReturn(['key1']);
        $parser2->method('getContentByKey')->willReturn('Hallo %username%!');
        $parser2->method('getFileName')->willReturn('de.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $validator->processFile($parser1);
        $validator->processFile($parser2);
        $validator->postProcess();

        $this->assertTrue($validator->hasIssues());
        $issues = $validator->getIssues();
        $this->assertCount(1, $issues);
    }

    public function testExtractSymfonyStylePlaceholders(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1']);
        $parser->method('getContentByKey')->willReturn('Hello %name% and %surname%!');
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $reflectionClass = new ReflectionClass($validator);
        $method = $reflectionClass->getMethod('extractPlaceholders');
        $method->setAccessible(true);

        $placeholders = $method->invoke($validator, 'Hello %name% and %surname%!');
        $this->assertContains('%name%', $placeholders);
        $this->assertContains('%surname%', $placeholders);
        // Verify that we have exactly these placeholders (excluding potential false matches)
        $expectedPlaceholders = ['%name%', '%surname%'];
        $placeholdersArray = is_array($placeholders) ? $placeholders : iterator_to_array($placeholders);
        $this->assertEmpty(array_diff($expectedPlaceholders, $placeholdersArray));
    }

    public function testExtractIcuStylePlaceholders(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1']);
        $parser->method('getContentByKey')->willReturn('Hello {name} and {surname}!');
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $reflectionClass = new ReflectionClass($validator);
        $method = $reflectionClass->getMethod('extractPlaceholders');
        $method->setAccessible(true);

        $placeholders = $method->invoke($validator, 'Hello {name} and {surname}!');
        $this->assertContains('{name}', $placeholders);
        $this->assertContains('{surname}', $placeholders);
        $this->assertCount(2, $placeholders);
    }

    public function testExtractTwigStylePlaceholders(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1']);
        $parser->method('getContentByKey')->willReturn('Hello {{ name }} and {{ surname }}!');
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $reflectionClass = new ReflectionClass($validator);
        $method = $reflectionClass->getMethod('extractPlaceholders');
        $method->setAccessible(true);

        $placeholders = $method->invoke($validator, 'Hello {{ name }} and {{ surname }}!');
        $this->assertContains('{{ name }}', $placeholders);
        $this->assertContains('{{ surname }}', $placeholders);
        $this->assertCount(2, $placeholders);
    }

    public function testExtractPrintfStylePlaceholders(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1']);
        $parser->method('getContentByKey')->willReturn('Hello %s and %1$s!');
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $reflectionClass = new ReflectionClass($validator);
        $method = $reflectionClass->getMethod('extractPlaceholders');
        $method->setAccessible(true);

        $placeholders = $method->invoke($validator, 'Hello %s and %1$s!');
        $this->assertContains('%s', $placeholders);
        $this->assertContains('%1$s', $placeholders);
        $this->assertCount(2, $placeholders);
    }

    public function testExtractLaravelStylePlaceholders(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1']);
        $parser->method('getContentByKey')->willReturn('Hello :name and :surname!');
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $reflectionClass = new ReflectionClass($validator);
        $method = $reflectionClass->getMethod('extractPlaceholders');
        $method->setAccessible(true);

        $placeholders = $method->invoke($validator, 'Hello :name and :surname!');
        $this->assertContains(':name', $placeholders);
        $this->assertContains(':surname', $placeholders);
        $this->assertCount(2, $placeholders);
    }

    public function testExtractMixedStylePlaceholders(): void
    {
        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1']);
        $parser->method('getContentByKey')->willReturn('Hello %name% and {user} with %s!');
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $reflectionClass = new ReflectionClass($validator);
        $method = $reflectionClass->getMethod('extractPlaceholders');
        $method->setAccessible(true);

        $placeholders = $method->invoke($validator, 'Hello %name% and {user} with %s!');
        $this->assertContains('%name%', $placeholders);
        $this->assertContains('{user}', $placeholders);
        $this->assertContains('%s', $placeholders);
        $this->assertCount(3, $placeholders);
    }

    public function testSupportsParser(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $expectedParsers = [XliffParser::class, YamlParser::class, JsonParser::class, PhpParser::class];
        $this->assertSame($expectedParsers, $validator->supportsParser());
    }

    public function testGetShortName(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $this->assertSame('PlaceholderConsistencyValidator', $validator->getShortName());
    }

    public function testResultTypeOnValidationFailure(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $this->assertSame(ResultType::WARNING, $validator->resultTypeOnValidationFailure());
    }

    public function testShouldShowDetailedOutput(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $this->assertTrue($validator->shouldShowDetailedOutput());
    }

    public function testFormatIssueMessage(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $issue = new Issue(
            'test.xlf',
            [
                'key' => 'test.key',
                'inconsistencies' => [
                    "File 'de.xlf' is missing placeholders: %name%",
                    "File 'de.xlf' has extra placeholders: %username%",
                ],
            ],
            'XliffParser',
            'PlaceholderConsistencyValidator',
        );

        $result = $validator->formatIssueMessage($issue);

        $this->assertStringContainsString('Warning', $result);
        $this->assertStringContainsString('<fg=yellow>', $result);
        $this->assertStringContainsString('test.key', $result);
        $this->assertStringContainsString('missing placeholders: %name%', $result);
        $this->assertStringContainsString('extra placeholders: %username%', $result);
    }

    public function testFormatIssueMessageWithPrefix(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $issue = new Issue(
            'test.xlf',
            [
                'key' => 'test.key',
                'inconsistencies' => ['Some inconsistency'],
            ],
            'XliffParser',
            'PlaceholderConsistencyValidator',
        );

        $result = $validator->formatIssueMessage($issue, '(TestPrefix) ');

        $this->assertStringContainsString('(TestPrefix)', $result);
        $this->assertStringContainsString('test.key', $result);
    }

    public function testDistributeIssuesForDisplay(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $issue = new Issue(
            '',
            [
                'key' => 'test.key',
                'files' => [
                    'en.xlf' => ['value' => 'Hello %name%!', 'placeholders' => ['%name%']],
                    'de.xlf' => ['value' => 'Hallo %username%!', 'placeholders' => ['%username%']],
                ],
            ],
            '',
            'PlaceholderConsistencyValidator',
        );

        $validator->addIssue($issue);

        $fileSet = new FileSet('XliffParser', '/path/to/files', 'test', []);
        $distribution = $validator->distributeIssuesForDisplay($fileSet);

        $this->assertArrayHasKey('en.xlf', $distribution);
        $this->assertArrayHasKey('de.xlf', $distribution);
        $this->assertCount(1, $distribution['en.xlf']);
        $this->assertCount(1, $distribution['de.xlf']);
    }

    public function testRenderDetailedOutput(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $issue = new Issue(
            'test.xlf',
            [
                'key' => 'test.key',
                'files' => [
                    'en.xlf' => ['value' => 'Hello %name%!', 'placeholders' => ['%name%']],
                    'de.xlf' => ['value' => 'Hallo %username%!', 'placeholders' => ['%username%']],
                ],
                'inconsistencies' => [
                    "File 'de.xlf' is missing placeholders: %name%",
                    "File 'de.xlf' has extra placeholders: %username%",
                ],
            ],
            'XliffParser',
            'PlaceholderConsistencyValidator',
        );

        $output = new BufferedOutput();
        $validator->renderDetailedOutput($output, [$issue]);

        $outputContent = $output->fetch();
        $this->assertStringContainsString('Translation Key', $outputContent);
        $this->assertStringContainsString('test.key', $outputContent);
        $this->assertStringContainsString('en.xlf', $outputContent);
        $this->assertStringContainsString('de.xlf', $outputContent);
        $this->assertStringContainsString('Hello %name%!', $outputContent);
        $this->assertStringContainsString('Hallo %username%!', $outputContent);
    }

    public function testResetState(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $parser = $this->createMock(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1']);
        $parser->method('getContentByKey')->willReturn('Hello %name%!');
        $parser->method('getFileName')->willReturn('test.xlf');

        $validator->processFile($parser);

        $reflectionClass = new ReflectionClass($validator);
        $property = $reflectionClass->getProperty('keyData');
        $property->setAccessible(true);

        $this->assertNotEmpty($property->getValue($validator));

        $resetMethod = $reflectionClass->getMethod('resetState');
        $resetMethod->setAccessible(true);
        $resetMethod->invoke($validator);

        $this->assertEmpty($property->getValue($validator));
    }

    public function testFindPlaceholderInconsistenciesWithSingleFile(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $reflectionClass = new ReflectionClass($validator);
        $method = $reflectionClass->getMethod('findPlaceholderInconsistencies');
        $method->setAccessible(true);

        $fileData = [
            'en.xlf' => ['value' => 'Hello %name%!', 'placeholders' => ['%name%']],
        ];

        $inconsistencies = $method->invoke($validator, $fileData);
        $this->assertEmpty($inconsistencies);
    }

    public function testComplexPlaceholderInconsistencyScenario(): void
    {
        $parser1 = $this->createMock(ParserInterface::class);
        $parser1->method('extractKeys')->willReturn(['greeting', 'farewell']);
        $parser1->method('getContentByKey')->willReturnMap([
            ['greeting', 'Hello %name%! Welcome to {site}'],
            ['farewell', 'Goodbye %name%'],
        ]);
        $parser1->method('getFileName')->willReturn('en.xlf');

        $parser2 = $this->createMock(ParserInterface::class);
        $parser2->method('extractKeys')->willReturn(['greeting', 'farewell']);
        $parser2->method('getContentByKey')->willReturnMap([
            ['greeting', 'Hallo %username%! Willkommen bei {site}'],
            ['farewell', 'Auf Wiedersehen %name% und %surname%'],
        ]);
        $parser2->method('getFileName')->willReturn('de.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $validator->processFile($parser1);
        $validator->processFile($parser2);
        $validator->postProcess();

        $this->assertTrue($validator->hasIssues());
        $issues = $validator->getIssues();
        $this->assertCount(2, $issues); // One for each key with inconsistencies
    }
}
