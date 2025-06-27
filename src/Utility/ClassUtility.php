<?php

declare(strict_types=1);

namespace MoveElevator\ComposerTranslationValidator\Utility;

use Psr\Log\LoggerInterface;

class ClassUtility
{
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
