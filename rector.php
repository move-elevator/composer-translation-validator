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

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests/src',
    ])
    ->withPhpVersion(PhpVersion::PHP_81)
    ->withSets([
        LevelSetList::UP_TO_PHP_81,
    ])
    ->withComposerBased(symfony: true)
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
    ])
;
