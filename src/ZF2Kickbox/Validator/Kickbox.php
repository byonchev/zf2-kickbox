<?php

namespace ZF2Kickbox\Validator;

use Zend\Validator\AbstractValidator;

/**
 * @author      Boris Yonchev <boris@yonchev.me>
 */
class Kickbox extends AbstractValidator
{
    const RESULT_DELIVERABLE   = 'deliverable';
    const RESULT_UNDELIVERABLE = 'undeliverable';
    const RESULT_RISKY         = 'risky';
    const RESULT_UNKNOWN       = 'unknown';

    public function __construct($options = null)
    {
        parent::__construct($options);
    }

    public function isValid($value)
    {
        $client  = new \Kickbox\Client('');
        $kickbox = $client->kickbox();

        try {
            /* @var \Kickbox\HttpClient\Response $response */
            $response = $kickbox->verify($value);

            $this->logResponse($response);

            return $this->getBooleanResult($response->body['result']);
        } catch (\Exception $e) {
            $this->logError($e);

            return false;
        }
    }

    /**
     * @param string $result
     * @param bool   $strictMode
     *
     * @return bool
     */
    private function getBooleanResult($result, $strictMode = false)
    {
        if ($strictMode) {
            return $result === self::RESULT_DELIVERABLE;
        } else {
            return $result !== self::RESULT_UNDELIVERABLE;
        }
    }

    protected function logError(\Exception $e)
    {
    }

    /**
     * @param \Kickbox\HttpClient\Response $response
     */
    protected function logResponse(\Kickbox\HttpClient\Response $response)
    {
    }
}
