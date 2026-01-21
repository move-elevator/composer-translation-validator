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

namespace MoveElevator\ComposerTranslationValidator\Utility;

use Psr\Log\LoggerInterface;

use function sprintf;

/**
 * ClassUtility.
 *
 * @author Konrad Michalik <km@move-elevator.de>
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
