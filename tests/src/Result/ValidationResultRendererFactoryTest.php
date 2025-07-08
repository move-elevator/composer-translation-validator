<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Tests\Result;

use MoveElevator\ComposerTranslationValidator\Result\FormatType;
use MoveElevator\ComposerTranslationValidator\Result\ValidationResultCliRenderer;
use MoveElevator\ComposerTranslationValidator\Result\ValidationResultJsonRenderer;
use MoveElevator\ComposerTranslationValidator\Result\ValidationResultRendererFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ValidationResultRendererFactoryTest extends TestCase
{
    public function testCreateCliRenderer(): void
    {
        $output = $this->createMock(OutputInterface::class);
        $input = $this->createMock(InputInterface::class);

        $renderer = ValidationResultRendererFactory::create(
            FormatType::CLI,
            $output,
            $input,
            false,
            false
        );

        $this->assertInstanceOf(ValidationResultCliRenderer::class, $renderer);
    }

    public function testCreateCliRendererWithDryRun(): void
    {
        $output = $this->createMock(OutputInterface::class);
        $input = $this->createMock(InputInterface::class);

        $renderer = ValidationResultRendererFactory::create(
            FormatType::CLI,
            $output,
            $input,
            true,
            false
        );

        $this->assertInstanceOf(ValidationResultCliRenderer::class, $renderer);
    }

    public function testCreateCliRendererWithStrictMode(): void
    {
        $output = $this->createMock(OutputInterface::class);
        $input = $this->createMock(InputInterface::class);

        $renderer = ValidationResultRendererFactory::create(
            FormatType::CLI,
            $output,
            $input,
            false,
            true
        );

        $this->assertInstanceOf(ValidationResultCliRenderer::class, $renderer);
    }

    public function testCreateJsonRenderer(): void
    {
        $output = $this->createMock(OutputInterface::class);
        $input = $this->createMock(InputInterface::class);

        $renderer = ValidationResultRendererFactory::create(
            FormatType::JSON,
            $output,
            $input,
            false,
            false
        );

        $this->assertInstanceOf(ValidationResultJsonRenderer::class, $renderer);
    }

    public function testCreateJsonRendererWithDryRun(): void
    {
        $output = $this->createMock(OutputInterface::class);
        $input = $this->createMock(InputInterface::class);

        $renderer = ValidationResultRendererFactory::create(
            FormatType::JSON,
            $output,
            $input,
            true,
            false
        );

        $this->assertInstanceOf(ValidationResultJsonRenderer::class, $renderer);
    }

    public function testCreateJsonRendererWithStrictMode(): void
    {
        $output = $this->createMock(OutputInterface::class);
        $input = $this->createMock(InputInterface::class);

        $renderer = ValidationResultRendererFactory::create(
            FormatType::JSON,
            $output,
            $input,
            false,
            true
        );

        $this->assertInstanceOf(ValidationResultJsonRenderer::class, $renderer);
    }
}
