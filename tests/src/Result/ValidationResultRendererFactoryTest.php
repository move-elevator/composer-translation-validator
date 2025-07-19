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
            false,
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
            false,
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
            true,
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
            false,
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
            false,
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
            true,
        );

        $this->assertInstanceOf(ValidationResultJsonRenderer::class, $renderer);
    }
}
