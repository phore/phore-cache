# Phore Status Page - Fastest way to generate a Status page

Quick prototyping framework. Includes:

- [Bootstrap 4](https://getbootstrap.com)
- [CoreUI](https://coreui.io/)
- [FontAwesome](https://fontawesome.com)

See our **[Interactive Demo](https://phore.github.io/phore-status-page/localhost/)**!

## Quick start

Your brand new status page can look like this within 30 seconds.
![StatusPage](doc/coreui-screenshot.png)

Install library using composer:
```bash
composer require phore/status-page
```

Create your `/www/index.php`:
```php
<?php
namespace App;
use Phore\StatusPage\PageHandler\NaviButtonWithIcon;
use Phore\StatusPage\StatusPageApp;

require __DIR__ . "/../vendor/autoload.php"; // Point to your composer vendor directory here!

$app = new StatusPageApp("MyApplication");

$app->addPage("/", function () {
    return ["h1" => "hello world"];
}, new NaviButtonWithIcon("Home", "fas fa-home"));

$app->serve();
```

Create a `.htaccess` file to define the fallback-resource:
(If using kickstart, define `apache_fallback_resource: "/index.php"` in your `.kick.yml`)
```
FallbackResource /index.php
```

***Done!***


## Authentication (HTTP Basic)

```php
<?php 
$app = new BasicAuthStatusPageApp();
$app->allowUser("admin", "admin");

```


## Features

### Mouting the application in a subdirectory

Just create a subdirectory and a `.htaccess` file pointing to 
the subdirectories index file:

```
FallbackResource /subdir/index.php
```

Create the `index.php` and specifiy the subdirectories name
as second constructor argument:

```php
<?php

$app = new StatusPageApp("SomeName", "/subdir");
$app->addPage("/subdir/", function() {
    return ["h1"=>"SubApp"];
});
$app->serve();
```




### Tables

```php
<?php
$data = ["a", "b", "c"];

$tblData = phore_array_transform($data, function($key, $value) {
    return [
        date ("Y-m-d"),
        ["a @href=/some/other" => "Hello Column"]
    ];
});

$tbl = pt()->basic_table(
    ["Date",    "Comment"],
    $tblData,
    ["",        "@align=right"]
);

```



