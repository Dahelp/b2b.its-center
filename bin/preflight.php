<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/config/env.php';
$failures = [];

$check = static function (bool $condition, string $name, string $failure) use (&$failures): void {
    if ($condition) {
        echo "OK   {$name}\n";
        return;
    }

    echo "FAIL {$name}: {$failure}\n";
    $failures[] = $name;
};

$check(PHP_VERSION_ID >= 80200, 'php', 'PHP 8.2 or newer is required');

foreach (['curl', 'dom', 'gd', 'mbstring', 'pdo_mysql', 'xml', 'zip'] as $extension) {
    $check(extension_loaded($extension), "ext-{$extension}", 'extension is not loaded');
}

$check(is_file($root . '/vendor/autoload.php'), 'composer', 'vendor/autoload.php is missing');
$check((string)getenv('APP_ENV') === 'production', 'app-env', 'APP_ENV must be production');
$check(filter_var((string)getenv('APP_URL'), FILTER_VALIDATE_URL) !== false, 'app-url', 'APP_URL is missing or invalid');

foreach (['params.php', 'api_goods.php', 'api_orders.php'] as $configName) {
    $check(is_file($root . '/config/' . $configName), "config-{$configName}", 'private config is missing');
}

$dbConfigFile = (string)(getenv('DB_CONFIG_FILE') ?: ($root . '/config/config_db.php'));
$check(is_file($dbConfigFile), 'database-config', 'DB_CONFIG_FILE does not point to a file');

if (is_file($dbConfigFile)) {
    try {
        $db = require $dbConfigFile;
        $pdo = new PDO(
            (string)($db['dsn'] ?? ''),
            (string)($db['user'] ?? ''),
            (string)($db['pass'] ?? ''),
            [PDO::ATTR_TIMEOUT => 5, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $pdo->query('SELECT 1');
        $check(true, 'database-connection', '');
    } catch (Throwable $exception) {
        $check(false, 'database-connection', get_class($exception) . ':' . $exception->getCode());
    }
}

foreach (['storage/cache/api', 'storage/logs', 'storage/sessions', 'tmp/cache'] as $runtimePath) {
    $absolutePath = $root . '/' . $runtimePath;
    $check(is_dir($absolutePath) && is_writable($absolutePath), "writable-{$runtimePath}", 'directory is missing or not writable');
}

$check((string)getenv('API_1C_CALLBACK_TOKEN') !== '', 'callback-token', 'API_1C_CALLBACK_TOKEN is missing');
$check((string)getenv('API_1C_HOST') !== '', 'api-host', 'API_1C_HOST is missing');
$check((string)getenv('API_1C_USER') !== '', 'api-user', 'API_1C_USER is missing');
$check((string)getenv('API_1C_PASSWORD') !== '', 'api-password', 'API_1C_PASSWORD is missing');

if ($failures !== []) {
    fwrite(STDERR, 'PREFLIGHT_FAILED=' . count($failures) . PHP_EOL);
    exit(1);
}

echo "PREFLIGHT_OK\n";
