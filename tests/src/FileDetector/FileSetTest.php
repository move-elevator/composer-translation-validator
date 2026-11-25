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

namespace MoveElevator\ComposerTranslationValidator\Tests\FileDetector;

use MoveElevator\ComposerTranslationValidator\FileDetector\FileSet;
use MoveElevator\ComposerTranslationValidator\Parser\XliffParser;
use PHPUnit\Framework\TestCase;

/**
 * FileSetTest.
 *
 * @author Konrad Michalik <km@move-elevator.de>
 * @license GPL-3.0-or-later
 */
final class FileSetTest extends TestCase
{
    public function testConstruct(): void
    {
        $parser = XliffParser::class;
        $path = '/path/to/translations';
        $setKey = 'default';
        $files = ['file1.xlf', 'file2.xlf'];

        $fileSet = new FileSet($parser, $path, $setKey, $files);

        self::assertSame($parser, $fileSet->getParser());
        self::assertSame($path, $fileSet->getPath());
        self::assertSame($setKey, $fileSet->getSetKey());
        self::assertSame($files, $fileSet->getFiles());
    }

    public function testConstructWithEmptyValues(): void
    {
        $fileSet = new FileSet('', '', '', []);

        self::assertSame('', $fileSet->getParser());
        self::assertSame('', $fileSet->getPath());
        self::assertSame('', $fileSet->getSetKey());
        self::assertSame([], $fileSet->getFiles());
    }
}
