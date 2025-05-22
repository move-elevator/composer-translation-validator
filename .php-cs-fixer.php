<?php

declare(strict_types=1);

$finder = \PhpCsFixer\Finder::create()
    ->in([__DIR__.'/src']);

$config = new \PhpCsFixer\Config();

return $config->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
    ])
    ->setFinder($finder);
