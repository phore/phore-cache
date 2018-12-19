<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 19.12.18
 * Time: 11:58
 */

namespace Phore\Cache;


use Phore\FileSystem\FileStream;
use Phore\ObjectStore\ObjectStore;
use Psr\Http\Message\StreamInterface;

class Cache
{


    private $minTTL;
    private $maxTTL;
    private $driver;

    public function __construct(ObjectStore $driver, int $minTTL=0, int $maxTTL=86400)
    {
        $this->minTTL = $minTTL;
        $this->maxTTL = $maxTTL;
        $this->driver = $driver;
    }


    public function has(string $key) : bool
    {
        $valid_to = $this->driver->object($key)->getMeta("valid_to", null);
        echo $valid_to;
        if ($valid_to > time() && $valid_to !== null) {
            return true;
        }
        return false;
    }

    public function get(string $key) {
        $obj = $this->driver->object($key);
        switch($obj->getMeta("type")) {
            case "string":
                return (string)$obj->get();
            case "int":
                return (int)$obj->get();
            case "float":
                return (float)$obj->get();
            case "serialized":
                return unserialize($obj->get());
            default:
                throw new \InvalidArgumentException("Invalid type in metadata");
        }

    }

    public function set(string $key, $data, int $ttl = null)
    {
        if ($ttl === null)
            $ttl = $this->maxTTL;

        if (is_string($data)) {
            $type = "string";
        } else if (is_int($data)) {
            $type = "int";
        } else if (is_float($data)) {
            $type = "float";
        } else if (is_array($data) || is_object($data)) {
            $type = "serialized";
            $data = serialize($data);
        } else {
            throw new \InvalidArgumentException("Cache data cannot be type " . gettype($data));
        }
        $this->driver->object($key)->setMeta(["valid_to" => time() + $ttl, "type"=>$type])->put($data);
    }


    /**
     * @param string $key
     * @return StreamInterface
     * @throws \Phore\Core\Exception\NotFoundException
     */
    public function getStream(string $key) : StreamInterface
    {
        return $this->driver->object($key)->getStream();
    }


}
