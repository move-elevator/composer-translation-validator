<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Result;

interface RendererInterface
{
    public function renderResult(): int;
}
