<?php

namespace app\services;

use app\helpers\ApiClient;

class Api1C
{
    protected static $cacheDir;

    public static function init()
    {
        if (!self::$cacheDir) {
            self::$cacheDir = ROOT . '/storage/cache/api/';

            if (!is_dir(self::$cacheDir)) {
                mkdir(self::$cacheDir, 0750, true);
            }
        }
    }

    public static function normalizeCode($code): string
    {
        $code = trim((string)$code);

        if ($code === '') {
            return '';
        }

        $normalized = ltrim($code, '0');

        return $normalized === '' ? '0' : $normalized;
    }

    public static function getProductData($article, $tip = 2)
    {
        self::init();

        $key = md5($article);
        $cacheFile = self::$cacheDir . $key . '.json';

        $now = time();
        $ttl = max(5, (int)(getenv('API_1C_CACHE_TTL') ?: 30));
        $staleTtl = max($ttl, (int)(getenv('API_1C_STALE_TTL') ?: 86400));
        $cache = null;

        if (file_exists($cacheFile)) {
            $cache = json_decode(file_get_contents($cacheFile), true);
            if (is_array($cache) && isset($cache['updated_at'], $cache['data']) && ($now - $cache['updated_at']) < $ttl) {
                return $cache['data'];
            }
        }

        $response = ApiClient::sendRequest(
            [],
            'api_goods.php',
            'tovars?code=' . urlencode($article),
            'GET'
        );

        if (!empty($response['success']) && isset($response['response'][0])) {
            $result = $response['response'][0];

            $data = [
                'name'       => $result['name'] ?? null,
                'price_rozn' => $result['price_rozn'] ?? null,
                'price_opt'  => $result['price_opt'] ?? null,
                'price_spec' => $result['price_spec'] ?? null,
                'quantity'   => $result['rest'] ?? 0,
                'wait'       => $result['wait'] ?? null,
                'wait_date'  => $result['wait_date'] ?? null,
                'isservice'  => $result['isservice'] ?? false,
            ];

            file_put_contents($cacheFile, json_encode([
                'data' => $data,
                'updated_at' => $now,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);

            return $data;
        }

        if (empty($response['success'])) {
            file_put_contents(
                ROOT . '/storage/logs/log_api_error.txt',
                date('Y-m-d H:i:s') . " | $article | " . ($response['error'] ?? 'unknown error') . " | URL: " . ($response['url'] ?? '') . "\n",
                FILE_APPEND
            );
        }

        if (is_array($cache) && isset($cache['updated_at'], $cache['data']) && ($now - $cache['updated_at']) < $staleTtl) {
            return $cache['data'];
        }

        return null;
    }

    public static function getServices(): array
    {
        self::init();

        $cacheFile = self::$cacheDir . 'services.json';
        $now = time();
        $ttl = max(30, (int)(getenv('API_1C_SERVICES_CACHE_TTL') ?: 300));
        $staleTtl = max($ttl, (int)(getenv('API_1C_STALE_TTL') ?: 86400));
        $cache = self::readCache($cacheFile);

        if ($cache !== null && ($now - $cache['updated_at']) < $ttl) {
            return $cache['data'];
        }

        /*
         * ВАЖНО:
         * Товары у тебя идут через:
         * ApiClient::sendRequest([], 'api_goods.php', 'tovars?code=...', 'GET')
         *
         * Поэтому услуги тоже запрашиваем через ApiClient, а не через self::BASE_URL/curl.
         *
         * На стороне api_goods.php должен быть маршрут services,
         * который обращается к серверу 1С: /trade/hs/goods/services
         */
        $response = ApiClient::sendRequest(
            [],
            'api_goods.php',
            'services',
            'GET'
        );

        if (empty($response['success'])) {
            file_put_contents(
                ROOT . '/storage/logs/log_api_error.txt',
                date('Y-m-d H:i:s') . " | services | " . ($response['error'] ?? 'unknown error') . " | URL: " . ($response['url'] ?? '') . "\n",
                FILE_APPEND
            );

            return $cache !== null && ($now - $cache['updated_at']) < $staleTtl ? $cache['data'] : [];
        }

        $data = $response['response'] ?? [];

        if (!is_array($data)) {
            return $cache !== null && ($now - $cache['updated_at']) < $staleTtl ? $cache['data'] : [];
        }

        self::writeCache($cacheFile, $data, $now);

        return $data;
    }

    public static function getOrderData(string $guid): ?array
    {
        self::init();

        $guid = trim($guid);
        if ($guid === '' || !preg_match('/^[a-f0-9-]{8,64}$/i', $guid)) {
            return null;
        }

        $cacheFile = self::$cacheDir . 'order_' . hash('sha256', $guid) . '.json';
        $now = time();
        $ttl = max(5, (int)(getenv('API_1C_ORDER_CACHE_TTL') ?: 30));
        $staleTtl = max($ttl, (int)(getenv('API_1C_ORDER_STALE_TTL') ?: 300));
        $cache = self::readCache($cacheFile);

        if ($cache !== null && ($now - $cache['updated_at']) < $ttl) {
            return $cache['data'];
        }

        $response = ApiClient::sendGetRequest([], 'api_orders.php', 'order/' . $guid);
        $data = $response['response'] ?? null;

        if (!empty($response['success']) && is_array($data)) {
            self::writeCache($cacheFile, $data, $now);
            return $data;
        }

        return $cache !== null && ($now - $cache['updated_at']) < $staleTtl ? $cache['data'] : null;
    }

    public static function getServicesByCode(): array
    {
        $services = self::getServices();

        $result = [];

        foreach ($services as $service) {
            if (empty($service['code'])) {
                continue;
            }

            if (empty($service['isservice'])) {
                continue;
            }

            $rawCode = trim((string)$service['code']);
            $normalizedCode = self::normalizeCode($rawCode);

            // Ключ с нулями: 00000000304
            $result[$rawCode] = $service;

            // Ключ без нулей: 304
            $result[$normalizedCode] = $service;
        }

        return $result;
    }

    private static function readCache(string $cacheFile): ?array
    {
        if (!is_file($cacheFile)) {
            return null;
        }

        $decoded = json_decode((string)file_get_contents($cacheFile), true);
        if (!is_array($decoded) || !isset($decoded['updated_at'], $decoded['data']) || !is_array($decoded['data'])) {
            return null;
        }

        return [
            'updated_at' => (int)$decoded['updated_at'],
            'data' => $decoded['data'],
        ];
    }

    private static function writeCache(string $cacheFile, array $data, int $updatedAt): void
    {
        file_put_contents(
            $cacheFile,
            json_encode(['data' => $data, 'updated_at' => $updatedAt], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
    }
}
