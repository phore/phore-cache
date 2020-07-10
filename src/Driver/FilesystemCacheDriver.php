<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 26.11.19
 * Time: 13:10
 */

namespace Phore\Cache\Driver;



use function foo\func;

class FilesystemCacheDriver implements CacheDriver
{


    private $dir;


    /**
     * Filesystem cache driver
     * 
     * 
     * 
     * @param $connect  \Redis|string
     * @param int $dbindex
     * @throws \Exception
     */
    public function __construct(string $dir="/tmp")
    {
        if ( ! is_dir($dir) || ! is_writable($dir))
            throw new CacheDriverException("Cache directory '$dir' not existing or not writeable.");
        $this->dir = $dir;
    }

    /**
     * @throws CacheDriverException
     */
    public function connect()
    {

    }


    private function getStoreFileName($key)
    {
        $filename = $this->dir . "/cache_" . sha1($key);
    }


    public function get(string $key)
    {
        $storeFile = $this->getStoreFileName($key);
        if ( ! file_exists($storeFile))
            return null;

        // Read with lock
        $f = fopen($storeFile, "r");
        flock($f, LOCK_SH);

        $buf = "";
        while ( ! feof($f))
            $buf .= fread($f, 1024);

        flock($f, LOCK_UN);
        fclose($f);
        return unserialize($buf);
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
        // Ttl handling is done by parent class
        
        $storeFile = $this->getStoreFileName($key);

        $f = fopen($storeFile, "w+");
        flock($f, LOCK_EX);

        if ( ! fwrite($f, serialize($data)))
            throw new CacheDriverException("Cannot write to cache file '$storeFile': " . error_get_last()["message"]);

        flock($f, LOCK_UN);
        fclose($f);
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
        $this->set($key, $data);
        return true;
    }

    public function del(string $key) : bool
    {
        $storeFile = $this->getStoreFileName($key);
        
        if (file_exists($storeFile)) {
            unlink($storeFile);
            return true;
        }
        return false;
    }

    public function has(string $key) : bool
    {
        $storeFile = $this->getStoreFileName($key);
        return file_exists($storeFile);
    }

    public function clear()
    {
        // TODO: Implement clear() method.
    }
}

