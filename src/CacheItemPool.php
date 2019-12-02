<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 26.11.19
 * Time: 13:08
 */

namespace Phore\Cache;


use Phore\Cache\Driver\CacheDriver;
use Phore\Cache\Driver\CacheDriverException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\NullLogger;

class CacheItemPool implements CacheItemPoolInterface
{

    /**
     * @var CacheDriver
     */
    protected $cacheDriver;


    /**
     * @var CacheItem[]
     */
    protected $deferredItems = [];


    /**
     * @var NullLogger
     */
    protected $logger;


    protected $failHard = false;


    public function __construct(CacheDriver $cacheDriver, bool $failHard = false)
    {
        $this->cacheDriver = $cacheDriver;
        $this->logger = new NullLogger();
        $this->failHard = $failHard;
    }


    /**
     * Returns a Cache Item representing the specified key.
     *
     * This method must always return a CacheItemInterface object, even in case of
     * a cache miss. It MUST NOT return null.
     *
     * @param string $key
     *   The key for which to return the corresponding Cache Item.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return CacheItemInterface|CacheItem
     *   The corresponding Cache Item.
     */
    public function getItem($key)
    {
        return new CacheItem($this, $key, $this->logger, $this->failHard);
    }


    public function getDriver() : CacheDriver
    {
        return $this->cacheDriver;
    }


    /**
     * Returns a traversable set of cache items.
     *
     * @param string[] $keys
     *   An indexed array of keys of items to retrieve.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return array|\Traversable
     *   A traversable collection of Cache Items keyed by the cache keys of
     *   each item. A Cache item will be returned for each key, even if that
     *   key is not found. However, if no keys are specified then an empty
     *   traversable MUST be returned instead.
     */
    public function getItems(array $keys = array())
    {
        $ret = [];
        foreach ($keys as $key) {
            $ret[$key] = $this->getItem($key);
        }
        return $ret;
    }

    /**
     * Confirms if the cache contains specified cache item.
     *
     * Note: This method MAY avoid retrieving the cached value for performance reasons.
     * This could result in a race condition with CacheItemInterface::get(). To avoid
     * such situation use CacheItemInterface::isHit() instead.
     *
     * @param string $key
     *   The key for which to check existence.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if item exists in the cache, false otherwise.
     */
    public function hasItem($key)
    {
        return $this->getItem($key)->isHit();
    }

    /**
     * Deletes all items in the pool.
     *
     * @return bool
     *   True if the pool was successfully cleared. False if there was an error.
     */
    public function clear()
    {
        $this->cacheDriver->clear();
    }

    /**
     * Removes the item from the pool.
     *
     * @param string $key
     *   The key to delete.
     *
     * @throws InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the item was successfully removed. False if there was an error.
     */
    public function deleteItem($key)
    {
        try {
            $this->cacheDriver->connect();
            return $this->cacheDriver->del($key);
        } catch (CacheDriverException $e) {
            $this->logger->alert("Cache: deleteItems() failed: " . $e->getMessage());
            if ($this->failHard)
                throw $e;
            return false;
        }
    }

    /**
     * Removes multiple items from the pool.
     *
     * @param string[] $keys
     *   An array of keys that should be removed from the pool.
     *
     * @throws InvalidArgumentException
     *   If any of the keys in $keys are not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     *
     * @return bool
     *   True if the items were successfully removed. False if there was an error.
     */
    public function deleteItems(array $keys)
    {
        foreach ($keys as $key)
            $this->cacheDriver->del($key);
        return true;
    }

    /**
     * Persists a cache item immediately.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   True if the item was successfully persisted. False if there was an error.
     */
    public function save(CacheItemInterface $item)
    {
        if ( ! $item instanceof CacheItem)
            throw new \InvalidArgumentException("Instance of CacheItem expected in parameter 1");
        $newData = $item->__get_newData();
        if ($newData === null)
            return true; // Noting to do
        try {
            $this->cacheDriver->connect();
            $this->cacheDriver->set($item->getKey(), serialize($newData), $newData["expiresAt"]);
        } catch (CacheDriverException $e) {
            $this->logger->alert("Caching error: save({$item->getKey()}): " . $e->getMessage());
            if ($this->failHard)
                throw $e;
            return false;
        }
        return true;
    }




    /**
     * Sets a cache item to be persisted later.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return bool
     *   False if the item could not be queued or if a commit was attempted and failed. True otherwise.
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        if ( ! $item instanceof CacheItem)
            throw new \InvalidArgumentException("Instance of CacheItem expected in parameter 1");
        $this->deferredItems[] = $item;
        return true;
    }

    /**
     * Persists any deferred cache items.
     *
     * @return bool
     *   True if all not-yet-saved items were successfully saved or there were none. False otherwise.
     */
    public function commit()
    {
        foreach ($this->deferredItems as $item)
            $this->save($item);
        return true;
    }







}
