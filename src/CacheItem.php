<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 26.11.19
 * Time: 13:04
 */

namespace Phore\Cache;


use Phore\Cache\Driver\CacheDriver;
use Phore\Cache\Driver\CacheDriverException;
use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerInterface;

class CacheItem implements CacheItemInterface
{

    /**
     * @var CacheDriver
     */
    private $cacheDriver;

    /**
     * @var string
     */
    private $key;


    /**
     * @var null|array
     */
    private $cacheData = null;

    /**
     * @var null|array
     */
    private $newData = null;


    /**
     * @var LoggerInterface
     */
    private $logger;

    protected $failHard = false;

    /**
     * @var CacheItemPool
     */
    private $cacheItemPool;

    public function __construct(CacheItemPool $cacheItemPool, string $key, LoggerInterface $logger, bool $failHard = false)
    {
        $this->key = $key;

        $this->cacheItemPool = $cacheItemPool;
        $this->cacheDriver = $cacheItemPool->getDriver();

        $this->logger = $logger;
        $this->failHard = $failHard;
    }


    public function __get_newData()
    {
        return $this->newData;
    }


    /**
     * Returns the key for the current cache item.
     *
     * The key is loaded by the Implementing Library, but should be available to
     * the higher level callers when needed.
     *
     * @return string
     *   The key string for this cache item.
     */
    public function getKey()
    {
        return $this->key;
    }


    protected function _tryFetchData()
    {
        if ($this->cacheData !== null)
            return $this->cacheData;

        try {
            $this->cacheDriver->connect();
            $data = unserialize($this->cacheDriver->get($this->key));
            if ($data === null)
                $data = null;
            if ($data["expiresAt"] < time())
                $data = null;
        } catch (CacheDriverException $e) {
            $this->logger->alert("cache: fetch key {$this->key}: " . $e->getMessage());
            if ($this->failHard)
                throw $e;
            return null;
        }

        if ($data === null)
            return null;

        $this->cacheData = $data;
        return $data;

    }


    /**
     * Retrieves the value of the item from the cache associated with this object's key.
     *
     * The value returned must be identical to the value originally stored by set().
     *
     * If isHit() returns false, this method MUST return null. Note that null
     * is a legitimate cached value, so the isHit() method SHOULD be used to
     * differentiate between "null value was found" and "no value was found."
     *
     * @return mixed
     *   The value corresponding to this cache item's key, or null if not found.
     */
    public function get()
    {
        $data = $this->_tryFetchData();
        if ($data === null)
            return null;

        return $data["val"];
    }

    /**
     * Confirms if the cache item lookup resulted in a cache hit.
     *
     * Note: This method MUST NOT have a race condition between calling isHit()
     * and calling get().
     *
     * @return bool
     *   True if the request resulted in a cache hit. False otherwise.
     */
    public function isHit()
    {
        if ($this->_tryFetchData() !== null)
            return true;
        return false;
    }


    public function shouldRetry()
    {
        if (($data = $this->_tryFetchData()) === null)
            return true;

        if ($data["retryAt"] !== null && $data["retryAt"] < time())
            return true;

        return false;
    }


    /**
     * Sets the value represented by this cache item.
     *
     * The $value argument may be any item that can be serialized by PHP,
     * although the method of serialization is left up to the Implementing
     * Library.
     *
     * @param mixed $value
     *   The serializable value to be stored.
     *
     * @return static
     *   The invoked object.
     */
    public function set($value)
    {
        if ($this->newData === null)
            $this->newData = ["val"=>null, "expiresAt"=>time(), "retryAt"=>time()];
        $this->newData["val"] = $value;
        return $this;
    }

    /**
     * Sets the expiration time for this cache item.
     *
     * @param \DateTimeInterface|null $expiration
     *   The point in time after which the item MUST be considered expired.
     *   If null is passed explicitly, a default value MAY be used. If none is set,
     *   the value should be stored permanently or for as long as the
     *   implementation allows.
     *
     * @return static
     *   The called object.
     */
    public function expiresAt($expiration) : self
    {
        if ( ! $expiration instanceof \DateTimeInterface)
            throw new \InvalidArgumentException("DateTimeInterface required as parameter 1");
        if ($this->newData === null)
            $this->newData = ["val"=>null, "expiresAt"=>null, "retryAt"=>null];
        $this->newData["expiresAt"] = $expiration->getTimestamp();
        return $this;
    }

    /**
     * Sets the expiration time for this cache item.
     *
     * @param int|\DateInterval|null $time
     *   The period of time from the present after which the item MUST be considered
     *   expired. An integer parameter is understood to be the time in seconds until
     *   expiration. If null is passed explicitly, a default value MAY be used.
     *   If none is set, the value should be stored permanently or for as long as the
     *   implementation allows.
     *
     * @return static
     *   The called object.
     */
    public function expiresAfter($time)
    {
        $expiresAt = time();
        if ($time instanceof \DateInterval) {
            $expiresAt += $time->s + $time->i * 60 + $time->h * 3600 + $time->d * 86400;
        } else if (is_int($time)) {
            $expiresAt += $time;
        } else {
            throw new \InvalidArgumentException("Invalid argument in parameter 1: $time");
        }
        $this->expiresAt(new \DateTime("@$expiresAt"));
        return $this;
    }


    public function retryAt($retry)
    {
        if ( ! $retry instanceof \DateTimeInterface)
            throw new \InvalidArgumentException("DateTimeInterface required as parameter 1");
        if ($this->newData === null)
            $this->newData = ["val"=>null, "expiresAt"=>null, "retryAt"=>null];
        $this->newData["retryAt"] = $retry->getTimestamp();
        if ($this->newData["expiresAt"] < $this->newData["retryAt"])
            $this->newData["expiresAt"] = $this->newData["retryAt"];
        return $this;
    }

    public function retryAfter($time)
    {
        $retryAt = time();
        if ($time instanceof \DateInterval) {
            $retryAt += $time->s + $time->i * 60 + $time->h * 3600 + $time->d * 86400;
        } else if (is_int($time)) {
            $retryAt += $time;
        } else {
            throw new \InvalidArgumentException("Invalid argument in parameter 1: $time");
        }
        $this->retryAt(new \DateTime("@$retryAt"));
        return $this;
    }


    /**
     * Execute a callback function to retrieve Data
     * and return the data.
     *
     * @param callable $dataCb
     * @return mixed
     * @throws \Exception
     */
    public function load(callable $dataCb)
    {
        if ($this->shouldRetry()) {
            try {
                $data = $dataCb($this);
                $this->set($data);
                $this->cacheItemPool->save($this);
                return $data;
            } catch (\Exception $e) {
                $this->logger->alert("getCached({$this->getKey()}): Exception: " . $e->getMessage());
                if ( ! $this->isHit())
                    throw $e;
            }
        }
        return $this->get();
    }

}
