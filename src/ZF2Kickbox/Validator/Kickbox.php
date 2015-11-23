<?php

namespace ZF2Kickbox\Validator;

use Kickbox\Client;
use Kickbox\HttpClient\Response;
use Traversable;
use Zend\Stdlib\ArrayUtils;
use Zend\Validator\AbstractValidator;
use Zend\Validator\Exception\InvalidArgumentException;
use ZF2Kickbox\Cache;
use ZF2Kickbox\LoggerInterface;

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
     * @var Cache\AdapterInterface
     */
    protected $cacheAdapter;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Sets validator options
     *
     * @param  array|Traversable $options
     *
     * @throws InvalidArgumentException
     */
    public function __construct($options = null)
    {
        if (is_null($options)) {
            $options = [];
        }

        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }

        if (!array_key_exists('apiKey', $options)) {
            throw new InvalidArgumentException("Missing Kickbox API key");
        }

        if (!array_key_exists('strictMode', $options)) {
            $options['strictMode'] = false;
        }

        if (!array_key_exists('cacheAdapter', $options)) {
            $options['cacheAdapter'] = null;
        }

        if (!array_key_exists('logger', $options)) {
            $options['logger'] = null;
        }

        $this->setApiKey($options['apiKey']);
        $this->setStrictMode($options['strictMode']);
        $this->setCacheAdapter($options['cacheAdapter']);
        $this->setLogger($options['logger']);

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
     * @return Cache\AdapterInterface
     */
    public function getCacheAdapter()
    {
        return $this->cacheAdapter;
    }

    /**
     * @param string|Cache\AdapterInterface $cacheAdapter
     */
    public function setCacheAdapter($cacheAdapter)
    {
        if (is_string($cacheAdapter)) {
            $cacheAdapter = new $cacheAdapter;
        }

        $this->cacheAdapter = $cacheAdapter;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param string|LoggerInterface $logger
     */
    public function setLogger($logger)
    {
        if (is_string($logger)) {
            $logger = new $logger;
        }

        $this->logger = $logger;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public function isValid($value)
    {
        $this->setValue($value);

        $cacheAdapter = $this->getCacheAdapter();
        $logger       = $this->getLogger();

        if ($cacheAdapter) {
            $cachedVerification = $cacheAdapter->getCachedVerification($value);

            if (is_bool($cachedVerification)) {
                if ($cachedVerification == false) {
                    $this->error(self::INVALID);
                }

                return $cachedVerification;
            }
        }

        $isValid = true;

        $strictMode = $this->isStrictMode();

        try {
            $client        = new Client($this->getApiKey());
            $kickboxClient = $client->kickbox();

            /* @var Response $response */
            $response = $kickboxClient->verify($value);
            $result   = $response->body['result'];

            if ($logger) {
                $logger->logResponse($response);
            }

            if ($strictMode) {
                if ($result === self::RESULT_UNDELIVERABLE) {
                    $this->error(self::INVALID);

                    $isValid = false;
                } else if ($result !== self::RESULT_DELIVERABLE) {
                    $this->error(self::NOT_SAFE);

                    $isValid = false;
                }
            } else if ($result === self::RESULT_UNDELIVERABLE) {
                $this->error(self::INVALID);

                $isValid = false;
            }

            if ($cacheAdapter) {
                $cacheAdapter->cacheVerification($value, $isValid);
            }
        } catch (\Exception $e) {
            $this->error(self::EXCEPTION);

            if ($logger) {
                $logger->logError($e);
            }
        }

        return $isValid;
    }

}
