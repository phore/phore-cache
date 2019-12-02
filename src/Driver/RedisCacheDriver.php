<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 26.11.19
 * Time: 13:10
 */

namespace Phore\Cache\Driver;



use function foo\func;

class RedisCacheDriver implements CacheDriver
{

    private $connection;

    /**
     * @var \Redis
     */
    private $redis;

    /**
     * RedisFlashDriver constructor.
     * @param $connect  \Redis|string
     * @param int $dbindex
     * @throws \Exception
     */
    public function __construct($connection)
    {
        if ($connection instanceof \Redis) {
            $this->connection = $connection;
            return;
        }

        if (is_string($connection)) {
            $this->connection = $connection;
            return;
        }
        throw new \InvalidArgumentException("Can't handle parameter 1 type " . gettype($connection));
    }

    /**
     * @throws CacheDriverException
     */
    public function connect()
    {
        if ($this->redis !== null)
            return;
        if (is_string ($this->connection)) {
            $uri = phore_parse_url($this->connection);

            if ($uri->scheme !== "redis")
                throw new \InvalidArgumentException("Invalid scheme. Format: redis://[passwd@]<host>?<options>");
            if ($uri->host === null)
                throw new \InvalidArgumentException("Invalid host. Format: redis://[passwd@]<host>?<options>");


            $this->redis = new \Redis();
            try {
                $err = null;
                set_error_handler(function ($code, $errmsg) use (&$err) {
                    $err = $errmsg;
                });
                $result = $this->redis->connect($uri->host, $uri->port);
                restore_error_handler();

                if ($err !== null)
                    throw new \RedisException($err);
                if (!$result)
                    throw new \Exception("Can't connect redis server '{$uri->host}'.");
                if ($uri->pass !== null) {
                    if (!$this->redis->auth($uri->pass)) {
                        throw new \Exception("Authentication to redis server on host '{$uri->host}' failed.");
                    }
                }
            } catch (\RedisException $e) {
                throw new CacheDriverException($e->getMessage(), $e->getCode(), $e);
            }
        }

    }


    public function get(string $key)
    {
        $ret = $this->redis->get($key);
        if ($ret === false)
            return null;
        return $ret;
    }


    /**
     * @param string $key
     * @param $data
     * @param int $expiresAt
     * @return bool
     * @throws CacheDriverException
     */
    public function set(string $key, $data, int $expiresAt) : bool
    {
        $ttl = 0;
        if ($expiresAt > 0) {
            $ttl = $expiresAt - time();
            if ($ttl < 0)
                $ttl = 0;
        }
        try {
            if (!$this->redis->set($key, $data, $ttl))
                throw new CacheDriverException("set($key): redis error: " . $this->redis->getLastError());
        } catch (\RedisException $e) {
            throw new CacheDriverException("set($key): redis error: ". $e->getMessage(), $e->getCode(), $e);
        }
        return true;
    }

    /**
     * Update only if key exists.
     *
     * @param string $key
     * @param $data
     * @return bool
     * @throws CacheDriverException
     */
    public function update(string $key, $data) : bool
    {
        if ( ! $this->redis->exists($key))
            return false;
        if ( ! $this->redis->set($key, $data))
            throw new CacheDriverException("Cannot set data. Redis error: " . $this->redis->getLastError());
        return true;
    }

    public function del(string $key) : bool
    {
        if ($this->redis->delete($key) === 1)
            return true;
        return false;
    }

    public function has(string $key) : bool
    {
        return $this->redis->exists($key);
    }

    public function clear()
    {
        // TODO: Implement clear() method.
    }
}

