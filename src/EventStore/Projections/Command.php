<?php

namespace EventStore\Projections;

use EventStore\ValueObjects\Enum\Enum;

final class Command extends Enum
{
    const ENABLE = 'enable';
    const DISABLE = 'disable';

    private static $allowedCommand = [
        self::ENABLE,
        self::DISABLE,
    ];

    public static function isAllowed($command)
    {
        return in_array($command, self::$allowedCommand);
    }
}
