<?php
namespace EventStore\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @package EventStore\Http
 */
interface HttpClientInterface
{
    /**
     * @param RequestInterface $request
     * @param array $options
     * @return ResponseInterface
     */
    public function send(RequestInterface $request, array $options);
}
