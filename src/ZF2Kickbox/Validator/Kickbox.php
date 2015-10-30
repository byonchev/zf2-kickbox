<?php

namespace ZF2Kickbox\Validator;

use Kickbox\Client;
use Kickbox\HttpClient\Response;
use Traversable;
use Zend\Stdlib\ArrayUtils;
use Zend\Validator\AbstractValidator;
use Zend\Validator\Exception\InvalidArgumentException;

/**
 * @author      Boris Yonchev <boris@yonchev.me>
 */
class Kickbox extends AbstractValidator
{
    const RESULT_DELIVERABLE   = 'deliverable';
    const RESULT_UNDELIVERABLE = 'undeliverable';
    const RESULT_RISKY         = 'risky';
    const RESULT_UNKNOWN       = 'unknown';

    const INVALID   = 'invalidEmail';
    const NOT_SAFE  = 'notSafe';
    const EXCEPTION = 'exception';

    /**
     * @var array
     */
    protected $messageTemplates = [
        self::NOT_SAFE  => "Email address cannot be verified",
        self::INVALID   => "Email address rejected",
        self::EXCEPTION => "There was an error validating the email address"
    ];

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var bool
     */
    protected $strictMode;

    /**
     * Sets validator options
     *
     * @param  array|Traversable $options
     *
     * @throws InvalidArgumentException
     */
    public function __construct($options = null)
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        if (!array_key_exists('apiKey', $options)) {
            throw new InvalidArgumentException("Missing Kickbox API key");
        }

        if (!array_key_exists('strictMode', $options)) {
            $options['strictMode'] = false;
        }

        $this->setApiKey($options['apiKey'])->setStrictMode($options['strictMode']);

        parent::__construct($options);
    }

    /**
     * @return boolean
     */
    public function isStrictMode()
    {
        return $this->strictMode;
    }

    /**
     * @param boolean $strictMode
     *
     * @return $this
     */
    public function setStrictMode($strictMode)
    {
        $this->strictMode = $strictMode;

        return $this;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     *
     * @return $this
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public function isValid($value)
    {
        $this->setValue($value);

        $strictMode = $this->isStrictMode();

        try {
            $client = new Client($this->getApiKey());

            $kickboxClient = $client->kickbox();

            /* @var Response $response */
            $response = $kickboxClient->verify($value);

            $this->logResponse($response);

            $result = $response->body['result'];

            if ($strictMode) {
                if ($result === self::RESULT_UNDELIVERABLE) {
                    $this->error(self::INVALID);

                    return false;
                } else if ($result !== self::RESULT_DELIVERABLE) {
                    $this->error(self::NOT_SAFE);

                    return false;
                }
            } else if ($result === self::RESULT_UNDELIVERABLE) {
                $this->error(self::INVALID);

                return false;
            }
        } catch (\Exception $e) {
            $this->error(self::EXCEPTION);

            $this->logError($e);

            return false;
        }

        return true;
    }

    protected function logError(\Exception $e)
    {
    }

    /**
     * @param Response $response
     */
    protected function logResponse(Response $response)
    {
    }
}
