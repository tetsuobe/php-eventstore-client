<?php

namespace EventStore\Exception;

use EventStore\Http\ResponseCode;

/**
 * Class ProjectionAlreadyExistsException
 * @package EventStore\Exception
 */
class ProjectionAlreadyExistsException extends \Exception
{

    public function __construct()
    {
        $this->message = 'Projection already exists.';
        $this->code = ResponseCode::HTTP_CONFLICT;
    }
}