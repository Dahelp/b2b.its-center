<?php

namespace app\controllers;

class ApiController extends AppController
{
    public function saveMarksAction(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Allow: POST');
            $this->respond(['success' => false, 'error' => 'Method not allowed'], 405);
        }

        $expectedToken = (string)(getenv('API_1C_CALLBACK_TOKEN') ?: '');
        $providedToken = trim((string)($_SERVER['HTTP_X_1C_TOKEN'] ?? ''));
        if ($expectedToken === '') {
            $this->respond(['success' => false, 'error' => 'Callback is not configured'], 503);
        }
        if ($providedToken === '' || !hash_equals($expectedToken, $providedToken)) {
            $this->respond(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $logDir = ROOT . '/storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0750, true);
        }
        $logPath = $logDir . '/mark_receive.log';
        $log = static function (string $message) use ($logPath): void {
            file_put_contents($logPath, '[' . date('Y-m-d H:i:s') . "] {$message}\n", FILE_APPEND | LOCK_EX);
        };

        try {
            $json = file_get_contents('php://input');
            if ($json === false || strlen($json) > 5 * 1024 * 1024) {
                $this->respond(['success' => false, 'error' => 'Invalid payload size'], 413);
            }

            $json = preg_replace('/^\xEF\xBB\xBF/', '', $json);
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data) || !isset($data['mark']) || !is_array($data['mark'])) {
                $this->respond(['success' => false, 'error' => 'Invalid JSON structure'], 422);
            }

            $orderUpdates = 0;
            $marksInserted = 0;
            \R::begin();

            foreach (($data['orders'] ?? []) as $order) {
                $guid = trim((string)($order['orderId'] ?? ''));
                $statusText = trim((string)($order['status'] ?? ''));
                if ($guid === '' || $statusText === '') {
                    continue;
                }

                $statusRow = \R::findOne('order_status', 'status_1c_text = ?', [$statusText]);
                if (!$statusRow) {
                    continue;
                }

                \R::exec(
                    'UPDATE `order` SET status = ?, status_1c = ? WHERE guid_1c = ?',
                    [$statusRow->id, $statusRow->id, $guid]
                );
                $orderUpdates++;
            }

            foreach ($data['mark'] as $orderBlock) {
                $markOrderId = trim((string)($orderBlock['markOrderId'] ?? ''));
                foreach (($orderBlock['marks'] ?? []) as $mark) {
                    $orderId = trim((string)($mark['orderId'] ?? ''));
                    $itemCode = trim((string)($mark['itemCode'] ?? ''));
                    $markText = trim((string)($mark['mark'] ?? ''));
                    $markBase64 = trim((string)($mark['markBase64'] ?? ''));
                    if ($orderId === '' || $itemCode === '' || ($markText === '' && $markBase64 === '')) {
                        continue;
                    }

                    $exists = \R::findOne(
                        'order_marks',
                        'mark_order_id = ? AND order_id = ? AND item_code = ? AND mark = ?',
                        [$markOrderId, $orderId, $itemCode, $markText]
                    );
                    if ($exists) {
                        continue;
                    }

                    \R::exec(
                        'INSERT INTO order_marks (mark_order_id, order_id, item_code, mark, mark_base64) VALUES (?, ?, ?, ?, ?)',
                        [$markOrderId, $orderId, $itemCode, $markText, $markBase64]
                    );
                    $marksInserted++;
                }
            }

            \R::commit();
            $log("Completed: order_updates={$orderUpdates}, marks_inserted={$marksInserted}");
            $this->respond([
                'success' => true,
                'order_updates' => $orderUpdates,
                'marks_inserted' => $marksInserted,
            ]);
        } catch (\JsonException $exception) {
            $log('Rejected invalid JSON');
            $this->respond(['success' => false, 'error' => 'Invalid JSON'], 400);
        } catch (\Throwable $exception) {
            try {
                \R::rollback();
            } catch (\Throwable $ignored) {
            }
            $log('Callback failed: ' . get_class($exception));
            $this->respond(['success' => false, 'error' => 'Internal server error'], 500);
        }
    }

    private function respond(array $payload, int $status = 200): void
    {
        http_response_code($status);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
