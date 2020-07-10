# Phore Cache



## Installation

```bash
composer require phore/cache
```



## Basic usage

Setting a driver:

```
$pool = new CacheItemPool(new RedisCacheDriver("redis://redis"));
```

or shortcut:

```
$pool = new CacheItemPool("redis://redis");
```

Load and cache data

```php
$pool = new CacheItemPool("redis://redis");
$item = $pool->getItem("cachekey")->expiresAfter(10)->retryAfter(5);
echo $item->load(function () {
    return "Data"; // Put code to load the cached value
});
```


## Filesystem driver

```
$pool = new CacheItemPool("file:///tmp");
```


