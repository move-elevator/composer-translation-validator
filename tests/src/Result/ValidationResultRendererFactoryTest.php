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

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\Result\{FormatType, ValidationResultCliRenderer, ValidationResultGitHubRenderer, ValidationResultJsonRenderer, ValidationResultRendererFactory};
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ValidationResultRendererFactoryTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class ValidationResultRendererFactoryTest extends TestCase
{
    public function testCreateCliRenderer(): void
    {
        $output = self::createStub(OutputInterface::class);
        $input = self::createStub(InputInterface::class);

        $renderer = ValidationResultRendererFactory::create(
            FormatType::CLI,
            $output,
            $input,
            false,
            false,
        );

        self::assertInstanceOf(ValidationResultCliRenderer::class, $renderer);
    }

    public function testCreateCliRendererWithDryRun(): void
    {
        $output = self::createStub(OutputInterface::class);
        $input = self::createStub(InputInterface::class);

        $renderer = ValidationResultRendererFactory::create(
            FormatType::CLI,
            $output,
            $input,
            true,
            false,
        );

        self::assertInstanceOf(ValidationResultCliRenderer::class, $renderer);
    }

    public function testCreateCliRendererWithStrictMode(): void
    {
        $output = self::createStub(OutputInterface::class);
        $input = self::createStub(InputInterface::class);

        $renderer = ValidationResultRendererFactory::create(
            FormatType::CLI,
            $output,
            $input,
            false,
            true,
        );

        self::assertInstanceOf(ValidationResultCliRenderer::class, $renderer);
    }

    public function testCreateJsonRenderer(): void
    {
        $output = self::createStub(OutputInterface::class);
        $input = self::createStub(InputInterface::class);

        $renderer = ValidationResultRendererFactory::create(
            FormatType::JSON,
            $output,
            $input,
            false,
            false,
        );

        self::assertInstanceOf(ValidationResultJsonRenderer::class, $renderer);
    }

    public function testCreateJsonRendererWithDryRun(): void
    {
        $output = self::createStub(OutputInterface::class);
        $input = self::createStub(InputInterface::class);

        $renderer = ValidationResultRendererFactory::create(
            FormatType::JSON,
            $output,
            $input,
            true,
            false,
        );

        self::assertInstanceOf(ValidationResultJsonRenderer::class, $renderer);
    }

    public function testCreateJsonRendererWithStrictMode(): void
    {
        $output = self::createStub(OutputInterface::class);
        $input = self::createStub(InputInterface::class);

        $renderer = ValidationResultRendererFactory::create(
            FormatType::JSON,
            $output,
            $input,
            false,
            true,
        );

        self::assertInstanceOf(ValidationResultJsonRenderer::class, $renderer);
    }

    public function testCreateGitHubRenderer(): void
    {
        $output = self::createStub(OutputInterface::class);
        $input = self::createStub(InputInterface::class);

        $renderer = ValidationResultRendererFactory::create(
            FormatType::GITHUB,
            $output,
            $input,
            false,
            false,
        );

        self::assertInstanceOf(ValidationResultGitHubRenderer::class, $renderer);
    }

    public function testCreateGitHubRendererWithDryRun(): void
    {
        $output = self::createStub(OutputInterface::class);
        $input = self::createStub(InputInterface::class);

        $renderer = ValidationResultRendererFactory::create(
            FormatType::GITHUB,
            $output,
            $input,
            true,
            false,
        );

        self::assertInstanceOf(ValidationResultGitHubRenderer::class, $renderer);
    }

    public function testCreateGitHubRendererWithStrictMode(): void
    {
        $output = self::createStub(OutputInterface::class);
        $input = self::createStub(InputInterface::class);

        $renderer = ValidationResultRendererFactory::create(
            FormatType::GITHUB,
            $output,
            $input,
            false,
            true,
        );

        self::assertInstanceOf(ValidationResultGitHubRenderer::class, $renderer);
    }
}
