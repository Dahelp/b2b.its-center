<?php

declare(strict_types=1);

$configFile = (string)(getenv('DB_CONFIG_FILE') ?: (dirname(__DIR__) . '/config/config_db.php'));
if (!is_file($configFile)) {
    fwrite(STDERR, "DB_CONFIG_MISSING\n");
    exit(2);
}

$db = require $configFile;

try {
    $pdo = new PDO(
        (string)($db['dsn'] ?? ''),
        (string)($db['user'] ?? ''),
        (string)($db['pass'] ?? ''),
        [PDO::ATTR_TIMEOUT => 5, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->query('SELECT 1');
    echo "DB_CONNECTION_OK\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'DB_CONNECTION_FAILED:' . get_class($exception) . ':' . $exception->getCode() . "\n");
    exit(1);
}
