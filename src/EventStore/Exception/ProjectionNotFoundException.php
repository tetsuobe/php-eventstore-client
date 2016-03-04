<?php

namespace EventStore\Exception;

/**
 * Class ProjectionNotFoundException
 * @package EventStore\Exception
 */
class ProjectionNotFoundException extends \Exception
{
    public function __construct()
    {
        $this->message = 'Projection not found.';
    }
}
