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
        $output = $this->createStub(OutputInterface::class);
        $input = $this->createStub(InputInterface::class);

        $renderer = ValidationResultRendererFactory::create(
            FormatType::CLI,
            $output,
            $input,
            false,
            false,
        );

        $this->assertInstanceOf(ValidationResultCliRenderer::class, $renderer);
    }

    public function testCreateCliRendererWithDryRun(): void
    {
        $output = $this->createStub(OutputInterface::class);
        $input = $this->createStub(InputInterface::class);

        $renderer = ValidationResultRendererFactory::create(
            FormatType::CLI,
            $output,
            $input,
            true,
            false,
        );

        $this->assertInstanceOf(ValidationResultCliRenderer::class, $renderer);
    }

    public function testCreateCliRendererWithStrictMode(): void
    {
        $output = $this->createStub(OutputInterface::class);
        $input = $this->createStub(InputInterface::class);

        $renderer = ValidationResultRendererFactory::create(
            FormatType::CLI,
            $output,
            $input,
            false,
            true,
        );

        $this->assertInstanceOf(ValidationResultCliRenderer::class, $renderer);
    }

    public function testCreateJsonRenderer(): void
    {
        $output = $this->createStub(OutputInterface::class);
        $input = $this->createStub(InputInterface::class);

        $renderer = ValidationResultRendererFactory::create(
            FormatType::JSON,
            $output,
            $input,
            false,
            false,
        );

        $this->assertInstanceOf(ValidationResultJsonRenderer::class, $renderer);
    }

    public function testCreateJsonRendererWithDryRun(): void
    {
        $output = $this->createStub(OutputInterface::class);
        $input = $this->createStub(InputInterface::class);

        $renderer = ValidationResultRendererFactory::create(
            FormatType::JSON,
            $output,
            $input,
            true,
            false,
        );

        $this->assertInstanceOf(ValidationResultJsonRenderer::class, $renderer);
    }

    public function testCreateJsonRendererWithStrictMode(): void
    {
        $output = $this->createStub(OutputInterface::class);
        $input = $this->createStub(InputInterface::class);

        $renderer = ValidationResultRendererFactory::create(
            FormatType::JSON,
            $output,
            $input,
            false,
            true,
        );

        $this->assertInstanceOf(ValidationResultJsonRenderer::class, $renderer);
    }

    public function testCreateGitHubRenderer(): void
    {
        $output = $this->createStub(OutputInterface::class);
        $input = $this->createStub(InputInterface::class);

        $renderer = ValidationResultRendererFactory::create(
            FormatType::GITHUB,
            $output,
            $input,
            false,
            false,
        );

        $this->assertInstanceOf(ValidationResultGitHubRenderer::class, $renderer);
    }

    public function testCreateGitHubRendererWithDryRun(): void
    {
        $output = $this->createStub(OutputInterface::class);
        $input = $this->createStub(InputInterface::class);

        $renderer = ValidationResultRendererFactory::create(
            FormatType::GITHUB,
            $output,
            $input,
            true,
            false,
        );

        $this->assertInstanceOf(ValidationResultGitHubRenderer::class, $renderer);
    }

    public function testCreateGitHubRendererWithStrictMode(): void
    {
        $output = $this->createStub(OutputInterface::class);
        $input = $this->createStub(InputInterface::class);

        $renderer = ValidationResultRendererFactory::create(
            FormatType::GITHUB,
            $output,
            $input,
            false,
            true,
        );

        $this->assertInstanceOf(ValidationResultGitHubRenderer::class, $renderer);
    }
}
