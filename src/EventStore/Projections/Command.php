<?php

namespace EventStore\Projections;

use EventStore\ValueObjects\Enum\Enum;

final class Command extends Enum
{
    const ENABLE = 'enable';
    const DISABLE = 'disable';
    const RESET = 'reset';

    private static $allowedCommand = [
        self::ENABLE,
        self::DISABLE,
        self::RESET,
    ];

    public static function isAllowed($command)
    {
        return in_array($command, self::$allowedCommand);
    }
}
