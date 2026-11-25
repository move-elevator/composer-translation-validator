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
use MoveElevator\ComposerTranslationValidator\Validator\PlaceholderConsistencyValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\Console\Output\BufferedOutput;

use function is_array;

/**
 * PlaceholderConsistencyValidatorTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class PlaceholderConsistencyValidatorTest extends TestCase
{
    public function testProcessFileWithValidFile(): void
    {
        $parser = self::createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1', 'key2']);
        $parser->method('getContentByKey')->willReturnMap([
            ['key1', 'Hello %name%!'],
            ['key2', 'Welcome {user}'],
        ]);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');

        $validator = new PlaceholderConsistencyValidator($logger);
        $result = $validator->processFile($parser);

        // processFile should return empty array as issues are handled in postProcess
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
            ->with(self::stringContains('The source file invalid.xlf is not valid.'));

        $validator = new PlaceholderConsistencyValidator($logger);
        $result = $validator->processFile($parser);

        self::assertEmpty($result);
    }

    public function testProcessFileSkipsKeysWithNullValue(): void
    {
        $parser = self::createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1', 'key2']);
        $parser->method('getContentByKey')->willReturnMap([
            ['key1', 'Hello %name%!'],
            ['key2', null],
        ]);
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = self::createStub(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);
        $validator->processFile($parser);

        $reflection = new ReflectionClass($validator);
        $keyData = $reflection->getProperty('keyData')->getValue($validator);

        self::assertArrayHasKey('key1', $keyData);
        self::assertArrayNotHasKey('key2', $keyData);
    }

    public function testRenderDetailedOutputWithEmptyIssues(): void
    {
        $logger = self::createStub(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $output = new BufferedOutput();
        $validator->renderDetailedOutput($output, []);

        self::assertSame('', $output->fetch());
    }

    public function testPostProcessWithConsistentPlaceholders(): void
    {
        $parser1 = self::createStub(ParserInterface::class);
        $parser1->method('extractKeys')->willReturn(['key1']);
        $parser1->method('getContentByKey')->willReturn('Hello %name%!');
        $parser1->method('getFileName')->willReturn('en.xlf');

        $parser2 = self::createStub(ParserInterface::class);
        $parser2->method('extractKeys')->willReturn(['key1']);
        $parser2->method('getContentByKey')->willReturn('Hallo %name%!');
        $parser2->method('getFileName')->willReturn('de.xlf');

        $logger = self::createStub(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $validator->processFile($parser1);
        $validator->processFile($parser2);
        $validator->postProcess();

        self::assertFalse($validator->hasIssues());
    }

    public function testPostProcessWithInconsistentPlaceholders(): void
    {
        $parser1 = self::createStub(ParserInterface::class);
        $parser1->method('extractKeys')->willReturn(['key1']);
        $parser1->method('getContentByKey')->willReturn('Hello %name%!');
        $parser1->method('getFileName')->willReturn('en.xlf');

        $parser2 = self::createStub(ParserInterface::class);
        $parser2->method('extractKeys')->willReturn(['key1']);
        $parser2->method('getContentByKey')->willReturn('Hallo %username%!');
        $parser2->method('getFileName')->willReturn('de.xlf');

        $logger = self::createStub(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $validator->processFile($parser1);
        $validator->processFile($parser2);
        $validator->postProcess();

        self::assertTrue($validator->hasIssues());
        $issues = $validator->getIssues();
        self::assertCount(1, $issues);
    }

    public function testExtractSymfonyStylePlaceholders(): void
    {
        $parser = self::createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1']);
        $parser->method('getContentByKey')->willReturn('Hello %name% and %surname%!');
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = self::createStub(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $reflectionClass = new ReflectionClass($validator);
        $method = $reflectionClass->getMethod('extractPlaceholders');

        $placeholders = $method->invoke($validator, 'Hello %name% and %surname%!');
        self::assertContains('%name%', $placeholders);
        self::assertContains('%surname%', $placeholders);
        // Verify that we have exactly these placeholders (excluding potential false matches)
        $expectedPlaceholders = ['%name%', '%surname%'];
        $placeholdersArray = is_array($placeholders) ? $placeholders : iterator_to_array($placeholders);
        self::assertEmpty(array_diff($expectedPlaceholders, $placeholdersArray));
    }

    public function testExtractIcuStylePlaceholders(): void
    {
        $parser = self::createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1']);
        $parser->method('getContentByKey')->willReturn('Hello {name} and {surname}!');
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = self::createStub(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $reflectionClass = new ReflectionClass($validator);
        $method = $reflectionClass->getMethod('extractPlaceholders');

        $placeholders = $method->invoke($validator, 'Hello {name} and {surname}!');
        self::assertContains('{name}', $placeholders);
        self::assertContains('{surname}', $placeholders);
        self::assertCount(2, $placeholders);
    }

    public function testExtractTwigStylePlaceholders(): void
    {
        $parser = self::createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1']);
        $parser->method('getContentByKey')->willReturn('Hello {{ name }} and {{ surname }}!');
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = self::createStub(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $reflectionClass = new ReflectionClass($validator);
        $method = $reflectionClass->getMethod('extractPlaceholders');

        $placeholders = $method->invoke($validator, 'Hello {{ name }} and {{ surname }}!');
        self::assertContains('{{ name }}', $placeholders);
        self::assertContains('{{ surname }}', $placeholders);
        self::assertCount(2, $placeholders);
    }

    public function testExtractPrintfStylePlaceholders(): void
    {
        $parser = self::createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1']);
        $parser->method('getContentByKey')->willReturn('Hello %s and %1$s!');
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = self::createStub(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $reflectionClass = new ReflectionClass($validator);
        $method = $reflectionClass->getMethod('extractPlaceholders');

        $placeholders = $method->invoke($validator, 'Hello %s and %1$s!');
        self::assertContains('%s', $placeholders);
        self::assertContains('%1$s', $placeholders);
        self::assertCount(2, $placeholders);
    }

    public function testExtractLaravelStylePlaceholders(): void
    {
        $parser = self::createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1']);
        $parser->method('getContentByKey')->willReturn('Hello :name and :surname!');
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = self::createStub(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $reflectionClass = new ReflectionClass($validator);
        $method = $reflectionClass->getMethod('extractPlaceholders');

        $placeholders = $method->invoke($validator, 'Hello :name and :surname!');
        self::assertContains(':name', $placeholders);
        self::assertContains(':surname', $placeholders);
        self::assertCount(2, $placeholders);
    }

    public function testExtractMixedStylePlaceholders(): void
    {
        $parser = self::createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1']);
        $parser->method('getContentByKey')->willReturn('Hello %name% and {user} with %s!');
        $parser->method('getFileName')->willReturn('test.xlf');

        $logger = self::createStub(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $reflectionClass = new ReflectionClass($validator);
        $method = $reflectionClass->getMethod('extractPlaceholders');

        $placeholders = $method->invoke($validator, 'Hello %name% and {user} with %s!');
        self::assertContains('%name%', $placeholders);
        self::assertContains('{user}', $placeholders);
        self::assertContains('%s', $placeholders);
        self::assertCount(3, $placeholders);
    }

    public function testShouldShowDetailedOutput(): void
    {
        $logger = self::createStub(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        self::assertTrue($validator->shouldShowDetailedOutput());
    }

    public function testFormatIssueMessage(): void
    {
        $logger = self::createStub(LoggerInterface::class);
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

        self::assertStringContainsString('Warning', $result);
        self::assertStringContainsString('<fg=yellow>', $result);
        self::assertStringContainsString('test.key', $result);
        self::assertStringContainsString('missing placeholders: %name%', $result);
        self::assertStringContainsString('extra placeholders: %username%', $result);
    }

    public function testDistributeIssuesForDisplay(): void
    {
        $logger = self::createStub(LoggerInterface::class);
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

        self::assertArrayHasKey('en.xlf', $distribution);
        self::assertArrayHasKey('de.xlf', $distribution);
        self::assertCount(1, $distribution['en.xlf']);
        self::assertCount(1, $distribution['de.xlf']);
    }

    public function testRenderDetailedOutput(): void
    {
        $logger = self::createStub(LoggerInterface::class);
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
        self::assertStringContainsString('Translation Key', $outputContent);
        self::assertStringContainsString('test.key', $outputContent);
        self::assertStringContainsString('en.xlf', $outputContent);
        self::assertStringContainsString('de.xlf', $outputContent);
        self::assertStringContainsString('Hello %name%!', $outputContent);
        self::assertStringContainsString('Hallo %username%!', $outputContent);
    }

    public function testRenderDetailedOutputReusesCachedPlaceholders(): void
    {
        $logger = self::createStub(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        // Both files carry the identical value, so highlighting the second cell
        // reuses the memoized placeholder extraction of the first.
        $issue = new Issue(
            'test.xlf',
            [
                'key' => 'shared.key',
                'files' => [
                    'en.xlf' => ['value' => 'Hello %name%!', 'placeholders' => ['%name%']],
                    'de.xlf' => ['value' => 'Hello %name%!', 'placeholders' => ['%name%']],
                ],
                'inconsistencies' => ['dummy'],
            ],
            'XliffParser',
            'PlaceholderConsistencyValidator',
        );

        $output = new BufferedOutput();
        $validator->renderDetailedOutput($output, [$issue]);

        self::assertStringContainsString('Hello %name%!', $output->fetch());

        $reflection = new ReflectionClass($validator);
        $cache = $reflection->getProperty('placeholderCache')->getValue($validator);
        self::assertArrayHasKey('Hello %name%!', $cache);
        self::assertSame(['%name%'], $cache['Hello %name%!']);
    }

    public function testResetState(): void
    {
        $logger = self::createStub(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $parser = self::createStub(ParserInterface::class);
        $parser->method('extractKeys')->willReturn(['key1']);
        $parser->method('getContentByKey')->willReturn('Hello %name%!');
        $parser->method('getFileName')->willReturn('test.xlf');

        $validator->processFile($parser);

        $reflectionClass = new ReflectionClass($validator);
        $property = $reflectionClass->getProperty('keyData');

        self::assertNotEmpty($property->getValue($validator));

        $resetMethod = $reflectionClass->getMethod('resetState');
        $resetMethod->invoke($validator);

        self::assertEmpty($property->getValue($validator));
    }

    public function testFindPlaceholderInconsistenciesWithSingleFile(): void
    {
        $logger = self::createStub(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $reflectionClass = new ReflectionClass($validator);
        $method = $reflectionClass->getMethod('findPlaceholderInconsistencies');

        $fileData = [
            'en.xlf' => ['value' => 'Hello %name%!', 'placeholders' => ['%name%']],
        ];

        $inconsistencies = $method->invoke($validator, $fileData);
        self::assertEmpty($inconsistencies);
    }

    public function testComplexPlaceholderInconsistencyScenario(): void
    {
        $parser1 = self::createStub(ParserInterface::class);
        $parser1->method('extractKeys')->willReturn(['greeting', 'farewell']);
        $parser1->method('getContentByKey')->willReturnMap([
            ['greeting', 'Hello %name%! Welcome to {site}'],
            ['farewell', 'Goodbye %name%'],
        ]);
        $parser1->method('getFileName')->willReturn('en.xlf');

        $parser2 = self::createStub(ParserInterface::class);
        $parser2->method('extractKeys')->willReturn(['greeting', 'farewell']);
        $parser2->method('getContentByKey')->willReturnMap([
            ['greeting', 'Hallo %username%! Willkommen bei {site}'],
            ['farewell', 'Auf Wiedersehen %name% und %surname%'],
        ]);
        $parser2->method('getFileName')->willReturn('de.xlf');

        $logger = self::createStub(LoggerInterface::class);
        $validator = new PlaceholderConsistencyValidator($logger);

        $validator->processFile($parser1);
        $validator->processFile($parser2);
        $validator->postProcess();

        self::assertTrue($validator->hasIssues());
        $issues = $validator->getIssues();
        self::assertCount(2, $issues); // One for each key with inconsistencies
    }
}
