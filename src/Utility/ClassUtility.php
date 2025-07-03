<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Utility;

use Psr\Log\LoggerInterface;

class ClassUtility
{
    public static function instantiate(string $interface, LoggerInterface $logger, string $type, ?string $className = null): ?object
    {
        if (null === $className) {
            return null;
        }

        if (!self::validateClass($interface, $logger, $className)) {
            $logger->error(
                sprintf('The %s class "%s" must implement %s.', $type, $className, $interface)
            );

            return null;
        }

        return new $className();
    }

    public static function validateClass(string $interface, LoggerInterface $logger, ?string $class): bool
    {
        if (is_null($class)) {
            return true;
        }

        if (!class_exists($class)) {
            $logger->error(sprintf('The class "%s" does not exist.', $class));

            return false;
        }

        if (!is_subclass_of($class, $interface)) {
            $logger->error(sprintf('The class "%s" must implement %s.', $class, $interface));

            return false;
        }

        return true;
    }
}
