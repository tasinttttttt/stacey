<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/extension',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/app/parsers/slir',
        __DIR__ . '/app/lib',
    ])
    ->withPhpSets(php83: true)
    ->withSets([
        SetList::CODE_QUALITY,
        SetList::EARLY_RETURN,
        SetList::INSTANCEOF,
    ]);
