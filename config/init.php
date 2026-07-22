<?php

$appEnv = getenv('APP_ENV') ?: 'production';
define("DEBUG", $appEnv === 'local' || $appEnv === 'development');
define("ROOT", dirname(__DIR__));
define("WWW", ROOT . '/public');
define("APP", ROOT . '/app');
define("CORE", ROOT . '/vendor/ishop/core');
define("LIBS", ROOT . '/vendor/ishop/core/libs');
define("CACHE", ROOT . '/tmp/cache');
define("CONF", ROOT . '/config');
define("LAYOUT", 'watches');
define("TEMPLATE", 'b2b');
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
    $app_path = "https://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}";
} else {
    $app_path = "https://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}";
}
$app_path = preg_replace("#[^/]+$#", '', $app_path);
$app_path = str_replace('/public/', '', $app_path);
define("PATH", $app_path);

// Новый define: SITE без путей
$configuredSite = rtrim((string)(getenv('APP_URL') ?: ''), '/');
$requestHost = preg_replace('/[^a-z0-9.:-]/i', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
$site = $configuredSite !== ''
    ? $configuredSite
    : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http') . '://' . $requestHost);
define("SITE", $site);

define("ADMIN", PATH . '/adminb2b');
require_once ROOT . '/vendor/autoload.php';
