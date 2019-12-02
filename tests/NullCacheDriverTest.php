<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 02.12.19
 * Time: 17:04
 */

namespace Phore\Tests;


use Phore\Cache\CacheItemPool;
use PHPUnit\Framework\TestCase;

class NullCacheDriverTest extends TestCase
{

    public function testNullDriver()
    {
        $pool = new CacheItemPool("null://null");

        $item = $pool->getItem("key");

        $this->assertEquals(false, $item->isHit());
        $this->assertEquals(true, $item->shouldRetry());
        $this->assertEquals(null, $item->get());

        $this->assertEquals("abc", $item->load(function() { return "abc"; }));

    }

}
