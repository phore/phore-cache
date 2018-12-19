<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 19.12.18
 * Time: 16:03
 */

namespace Phore\Tests;


use Phore\Cache\Cache;
use Phore\ObjectStore\Driver\FileSystemObjectStoreDriver;
use Phore\ObjectStore\ObjectStore;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{


    public function testDataPutHasGet ()
    {
        system("rm -R /tmp/cache1");
        $cache = new Cache(new ObjectStore(new FileSystemObjectStoreDriver("/tmp/cache1")));

        $this->assertEquals(false, $cache->has("key"));
        $cache->set("key", "Some Data", 100);

        $this->assertEquals(true, $cache->has("key"));
        $this->assertEquals("Some Data", $cache->get("key"));
    }

}
