<?php

declare(strict_types=1);

$environmentRoot = dirname(__DIR__);
require_once $environmentRoot . '/vendor/autoload.php';

if (is_file($environmentRoot . '/.env')) {
    Dotenv\Dotenv::createUnsafeImmutable($environmentRoot)->safeLoad();
}
