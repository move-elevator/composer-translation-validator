<?php

declare(strict_types=1);

use EliasHaeussler\RectorConfig\Config\Config;
use Rector\Config\RectorConfig;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\ValueObject\PhpVersion;

return static function (RectorConfig $rectorConfig): void {
    Config::create($rectorConfig, PhpVersion::PHP_81)
        ->in(
            __DIR__.'/src',
            __DIR__.'/tests',
        )
        ->not(
            __DIR__.'/tests/Build',
        )
        ->withSymfony()
        ->skip(NullToStrictStringFuncCallArgRector::class, [
            'src/Command/ValidateTranslationCommand.php',
        ])
        ->apply()
    ;
};
