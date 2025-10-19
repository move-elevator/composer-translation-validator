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

use EliasHaeussler\RectorConfig\Config\Config;
use Rector\Config\RectorConfig;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\PHPUnit\PHPUnit60\Rector\ClassMethod\AddDoesNotPerformAssertionToNonAssertingTestRector;
use Rector\ValueObject\PhpVersion;

return static function (RectorConfig $rectorConfig): void {
    Config::create($rectorConfig, PhpVersion::PHP_81)
        ->in(
            __DIR__.'/src',
            __DIR__.'/tests',
        )
        ->not(
            __DIR__.'/tests/vendor',
        )
        ->withSymfony()
        ->withPHPUnit()
        ->skip(NullToStrictStringFuncCallArgRector::class, [
            'src/Command/ValidateTranslationCommand.php',
        ])
        ->skip(AddDoesNotPerformAssertionToNonAssertingTestRector::class)
        ->apply()
    ;
};
