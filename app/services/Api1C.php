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
        file_put_contents(
            ROOT . '/storage/logs/debug_call.txt',
            date('Y-m-d H:i:s') . " | Вызов getProductData($article)\n",
            FILE_APPEND
        );

        self::init();

        $key = md5($article);
        $cacheFile = self::$cacheDir . $key . '.json';

        $now = time();
        $ttl = max(5, (int)(getenv('API_1C_CACHE_TTL') ?: 30));

        if (file_exists($cacheFile)) {
            $cache = json_decode(file_get_contents($cacheFile), true);
            if ($cache && isset($cache['updated_at']) && ($now - $cache['updated_at']) < $ttl) {
                return $cache['data'];
            }
        }

        $response = ApiClient::sendRequest(
            [],
            'api_goods.php',
            'tovars?code=' . urlencode($article),
            'GET'
        );

        file_put_contents(
            ROOT . '/storage/logs/debug_response.txt',
            date('Y-m-d H:i:s') . " | $article | " . print_r($response, true) . "\n",
            FILE_APPEND
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

        return null;
    }

    public static function getServices(): array
    {
        self::init();

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

        file_put_contents(
            ROOT . '/storage/logs/debug_services_response.txt',
            date('Y-m-d H:i:s') . " | services | " . print_r($response, true) . "\n",
            FILE_APPEND
        );

        if (empty($response['success'])) {
            file_put_contents(
                ROOT . '/storage/logs/log_api_error.txt',
                date('Y-m-d H:i:s') . " | services | " . ($response['error'] ?? 'unknown error') . " | URL: " . ($response['url'] ?? '') . "\n",
                FILE_APPEND
            );

            return [];
        }

        $data = $response['response'] ?? [];

        if (!is_array($data)) {
            return [];
        }

        return $data;
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
}
