<?php

namespace ZF2Kickbox\Logger;

use Kickbox\HttpClient\Response;

/**
 * @author      Boris Yonchev <boris@yonchev.me>
 */
interface LoggerInterface
{
    /**
     * @param Response $response
     */
    public function logResponse(Response $response);

    /**
     * @param \Exception $e
     */
    public function logError(\Exception $e);
}