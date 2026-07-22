<?php

namespace app\helpers;

class ApiClient {

    public static function sendRequest(array $data, string $configPath, string $methodOverride = null, string $httpMethod = 'POST'): array {
        $config = require ROOT . "/config/{$configPath}";

        $host = rtrim($config['host'], '/');
        $base = trim($config['base'], '/');
        $hs = trim($config['hs'], '/');
        $service = trim($config['service'], '/');
        $endpoint = $methodOverride ?: trim($config['method'], '/');

        $url = "{$host}/{$base}/{$hs}/{$service}/{$endpoint}";
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);

        $curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_USERPWD => implode(':', $config['auth']),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 15,
        ];
        
        if (strtoupper($httpMethod) === 'POST') {
            $curlOptions[CURLOPT_POST] = true;
            $curlOptions[CURLOPT_POSTFIELDS] = $payload;
        } elseif (strtoupper($httpMethod) === 'PUT') {
            $curlOptions[CURLOPT_CUSTOMREQUEST] = 'PUT';
            $curlOptions[CURLOPT_POSTFIELDS] = $payload;
        } elseif (strtoupper($httpMethod) === 'GET') {
            $curlOptions[CURLOPT_CUSTOMREQUEST] = 'GET';
            // ❌ не указывать CURLOPT_POSTFIELDS
        }

        curl_setopt_array($ch, $curlOptions);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        // 🔹 Лог сырого ответа (всегда)
        // Successful payloads may contain customer and order data, so they are not logged.

        // 🔻 Если ошибка — логируем отдельно
        if ($httpCode !== 200 || !$response) {
            self::logError([
                'datetime'   => date('Y-m-d H:i:s'),
                'url'        => $url,
                'http_code'  => $httpCode,
                'curl_error' => $curlError,
            ]);

            return [
                'success' => false,
                'error' => $curlError ?: "Ошибка HTTP: $httpCode",
                'response' => $response,
                'http_code' => $httpCode,
                'url' => $url,
            ];
        }

        // 🔄 Очистка BOM
        $responseClean = preg_replace('/^\xEF\xBB\xBF/', '', $response);
        $responseDecoded = json_decode($responseClean, true);

        // ❗ Лог ошибки json_decode
        if (json_last_error() !== JSON_ERROR_NONE) {
            file_put_contents(
                ROOT . '/storage/logs/order_api_json_error.log',
                "[" . date('Y-m-d H:i:s') . "] JSON decode error: " . json_last_error_msg() . "\n" .
                "URL: {$url}\n" .
                "Raw:\n{$response}\n\n" .
                "Clean:\n{$responseClean}\n\n" .
                str_repeat("=", 40) . "\n\n",
                FILE_APPEND
            );
        }

        return [
            'success' => true,
            'response' => $responseDecoded,
            'http_code' => $httpCode,
            'url' => $url,
        ];
    }

    protected static function logError(array $info): void {
        $logPath = ROOT . '/storage/logs/api_failures.log';
        $log = "==== API ERROR ====\n";
        $log .= "Time: {$info['datetime']}\n";
        $log .= "URL: {$info['url']}\n";
        $log .= "HTTP Code: {$info['http_code']}\n";
        if (!empty($info['curl_error'])) {
            $log .= "cURL Error: {$info['curl_error']}\n";
        }
        $log .= str_repeat("=", 40) . "\n\n";

        file_put_contents($logPath, $log, FILE_APPEND);
    }

    protected static function logRaw(array $info): void {
        $logPath = ROOT . '/storage/logs/order_api_raw.log';
        $log = "==== RAW API LOG ====\n";
        $log .= "Time: {$info['datetime']}\n";
        $log .= "URL: {$info['url']}\n";
        $log .= "HTTP Code: {$info['http_code']}\n";
        $log .= "Payload:\n" . json_encode($info['payload'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
        $log .= "Raw Response:\n" . ($info['response'] ?: 'NO RESPONSE') . "\n";
        $log .= str_repeat("=", 40) . "\n\n";

        file_put_contents($logPath, $log, FILE_APPEND);
    }


    public static function sendGetRequest(array $queryParams, string $configPath, string $methodOverride = null): array {
        $config = require ROOT . "/config/{$configPath}";

        $host = rtrim($config['host'], '/');
        $base = trim($config['base'], '/');
        $hs = trim($config['hs'], '/');
        $service = trim($config['service'], '/');
        $endpoint = $methodOverride ?: trim($config['method'], '/');

        $query = http_build_query($queryParams);
        $url = "{$host}/{$base}/{$hs}/{$service}/{$endpoint}" . ($query ? "?{$query}" : "");

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => implode(':', $config['auth']),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // 🔄 Очистка BOM
        $responseClean = preg_replace('/^\xEF\xBB\xBF/', '', $response);
        $responseDecoded = json_decode($responseClean, true);

        // Лог при ошибке или пустом ответе
        if ($httpCode !== 200 || !$response || json_last_error() !== JSON_ERROR_NONE) {
            file_put_contents(ROOT . '/storage/logs/order_api_error.log',
                "[" . date('Y-m-d H:i:s') . "] GET {$url}\nHTTP: {$httpCode}\nError: {$curlError}\nRaw:\n{$response}\n\n",
                FILE_APPEND
            );
        }

        return [
            'success' => ($httpCode === 200 && json_last_error() === JSON_ERROR_NONE),
            'response' => $responseDecoded,
            'http_code' => $httpCode,
            'url' => $url,
        ];
    }

    public static function sendRawRequest($configFile, $method, $httpMethod = 'GET') {
        $configPath = ROOT . "/config/{$configFile}";
        if (!file_exists($configPath)) {
            throw new \Exception("Config file not found: {$configPath}");
        }
    
        $config = require $configPath;
    
        $url = rtrim($config['host'], '/') . '/' .
               trim($config['base'], '/') . '/' .
               trim($config['hs'], '/') . '/' .
               trim($config['service'], '/') . '/' .
               ltrim($method, '/');
    
        $auth = $config['auth'] ?? null;
    
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpMethod);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
        if ($auth) {
            curl_setopt($ch, CURLOPT_USERPWD, $auth[0] . ':' . $auth[1]);
        }
    
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    
        return [
            'http_code' => $httpCode,
            'body' => $body,
        ];
    }
    
    

}
