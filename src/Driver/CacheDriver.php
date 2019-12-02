<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 26.11.19
 * Time: 13:14
 */

namespace Phore\Cache\Driver;


interface CacheDriver
{


    public function has(string $key) : bool;

    public function get(string $key);

    public function set(string $key, $value, int $expiresAt);


    public function del(string $key) : bool;

    public function clear();

    public function connect();

}
