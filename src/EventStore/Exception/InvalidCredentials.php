<?php

namespace EventStore\Exception;

class InvalidCredentials extends \Exception
{
    public function __construct()
    {
        $this->message = 'Invalid credentials';
    }
}
