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

namespace MoveElevator\ComposerTranslationValidator\Utility;

use Psr\Log\LoggerInterface;

/**
 * ClassUtility.
 *
 * @author Konrad Michalik <hej@konradmichalik.dev>
 * @license GPL-3.0-or-later
 */
class ClassUtility
{
    public static function instantiate(
        string $interface,
        LoggerInterface $logger,
        string $type,
        ?string $className = null,
    ): ?object {
        if (null === $className) {
            return null;
        }

        if (!self::validateClass($interface, $logger, $className)) {
            $logger->error(
                sprintf(
                    'The %s class "%s" must implement %s.',
                    $type,
                    $className,
                    $interface,
                ),
            );

            return null;
        }

        return new $className();
    }

    public static function validateClass(
        string $interface,
        LoggerInterface $logger,
        ?string $class,
    ): bool {
        if (null === $class) {
            return true;
        }

        if (!class_exists($class)) {
            $logger->error(
                sprintf('The class "%s" does not exist.', $class),
            );

            return false;
        }

        if (!is_subclass_of($class, $interface)) {
            $logger->error(
                sprintf(
                    'The class "%s" must implement %s.',
                    $class,
                    $interface,
                ),
            );

            return false;
        }

        return true;
    }
}
