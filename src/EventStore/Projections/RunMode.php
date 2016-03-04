<?php

namespace EventStore\Projections;

use EventStore\ValueObjects\Enum\Enum;

final class RunMode extends Enum
{
    const ONE_TIME = 'onetime';
    const CONTINUOUS = 'continuous';
}
