<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 26.11.19
 * Time: 13:16
 */

namespace Phore\Cache\Driver;


class NullCacheDriver implements CacheDriver
{

    public function has(string $key): bool
    {
        return false;
    }

    public function get(string $key)
    {
        return null;
    }



    public function del(string $key): bool
    {
        return false;
    }

    public function clear()
    {
        return true;
    }

    public function connect()
    {
        return true;
    }

    public function set(string $key, $value, int $expiresAt)
    {
        return false;
    }
}
