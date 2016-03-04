<?php

namespace EventStore\Exception;

/**
 * Class InvalidCommandException
 * @package EventStore\Exception
 */
class InvalidCommandException extends \Exception
{
    public function __construct()
    {
        $this->message = 'Invalid command.';
    }
}
