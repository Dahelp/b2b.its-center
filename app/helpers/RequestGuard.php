<?php

namespace app\helpers;

final class RequestGuard
{
    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string)$_SESSION['csrf_token'];
    }

    public static function requirePost(bool $json = false): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Allow: POST');
            self::fail(405, 'Method not allowed', $json);
        }
    }

    public static function requireAuth(bool $json = false): int
    {
        $userId = (int)($_SESSION['b2buser']['id'] ?? 0);
        if ($userId <= 0) {
            self::fail(401, 'Authentication required', $json);
        }
        return $userId;
    }

    public static function requireCsrf(bool $json = false): void
    {
        $provided = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? ''));
        $expected = (string)($_SESSION['csrf_token'] ?? '');
        if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
            self::fail(403, 'Invalid CSRF token', $json);
        }
    }

    private static function fail(int $status, string $message, bool $json): void
    {
        http_response_code($status);
        if ($json) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}
