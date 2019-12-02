<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 29.11.19
 * Time: 15:05
 */

namespace Phore\Tests;


use Phore\Cache\CacheItemPool;
use Phore\Cache\Driver\RedisCacheDriver;
use PHPUnit\Framework\TestCase;

class CacheItemPoolTest extends TestCase
{


    public function testWithStringConnection()
    {
        $pool = new CacheItemPool("redis://redis");
        $this->assertInstanceOf(RedisCacheDriver::class, $pool->getDriver());
    }

    public function testWithObjectConnection()
    {
        $pool = new CacheItemPool(new RedisCacheDriver("redis://redis"));
        $this->assertInstanceOf(RedisCacheDriver::class, $pool->getDriver());
    }

    public function testCaching()
    {

        $pool = new CacheItemPool("redis://redis");


        $item = $pool->getItem("a");
        $item->set("abc");
        $item->expiresAfter(100);

        $pool->save($item);

        $item = $pool->getItem("a");

        $this->assertEquals("abc", $item->get());


    }


    public function testExpiresOnTime()
    {
        $pool = new CacheItemPool("redis://redis");


        $item = $pool->getItem("a");
        $item->set("abc");
        $item->expiresAfter(1);
        $pool->save($item);

        sleep(2);

        $item = $pool->getItem("a");
        $this->assertEquals(null, $item->get());
    }


    public function testShouldRetry()
    {
        $pool = new CacheItemPool("redis://redis");


        $item = $pool->getItem("a");
        $item->set("abc");
        $item->expiresAfter(3);
        $item->retryAfter(1);
        $pool->save($item);

        $item = $pool->getItem("a");
        $this->assertEquals(false, $item->shouldRetry());

        sleep(2);
        $item = $pool->getItem("a");
        $this->assertEquals(true, $item->shouldRetry());

        sleep (2);

        $item = $pool->getItem("a");
        $this->assertEquals(null, $item->get());
    }


    public function testFailsSilently ()
    {
         $pool = new CacheItemPool("redis://non-existent-redis");

         $item = $pool->getItem("a");

         $item->get();

         $pool->save($item);
         $this->assertTrue(true);
    }


    public function testDefaultExpiresAfter()
    {
        $pool = new CacheItemPool("redis://redis");
        $pool->setDefaultExpiresAfter(1);
        $item = $pool->getItem("abc");
        $item->set("val1");
        $pool->save($item);

        $item = $pool->getItem("abc");
        $this->assertEquals("val1", $item->get());
    }


}
