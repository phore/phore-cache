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
$pool = new CachItemPool("redis://redis");
```



