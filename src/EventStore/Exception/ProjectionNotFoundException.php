<?php

namespace EventStore\Exception;

use EventStore\Http\ResponseCode;

/**
 * Class ProjectionNotFoundException
 * @package EventStore\Exception
 */
class ProjectionNotFoundException extends \Exception
{
    public function __construct()
    {
        $this->message = 'Projection not found.';
        $this->code = ResponseCode::HTTP_NOT_FOUND;
    }
}
