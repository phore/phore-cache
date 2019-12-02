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
        // TODO: Implement has() method.
    }

    public function get(string $key)
    {
        // TODO: Implement get() method.
    }

    public function set(string $key, $value)
    {
        // TODO: Implement set() method.
    }
}
