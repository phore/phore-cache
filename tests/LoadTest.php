<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 02.12.19
 * Time: 13:53
 */

namespace Phore\Tests;


use Phore\Cache\CacheItemPool;
use Phore\Cache\Driver\RedisCacheDriver;
use PHPUnit\Framework\TestCase;

class LoadTest extends TestCase
{


    public function testGetCached()
    {

        $pool = new CacheItemPool(new RedisCacheDriver("redis://redis"));
        $item = $pool->getItem("someKey")->expiresAfter(3600)->retryAfter(1);

        $calls = 0;
        $cb = function() use (&$calls) {
            $calls++;
            return "data$calls";
        };
        $data = $item->load($cb);

        $this->assertEquals(1, $calls);
        $this->assertEquals("data1", $data);

        $item = $pool->getItem("someKey")->expiresAfter(3600)->retryAfter(1);
        $data = $item->load($cb);

        $this->assertEquals(1, $calls);
        $this->assertEquals("data1", $data);

        sleep(2);
        $item = $pool->getItem("someKey")->expiresAfter(3600)->retryAfter(1);
        $data = $item->load($cb);

        $this->assertEquals(2, $calls);
        $this->assertEquals("data2", $data);

    }



}
