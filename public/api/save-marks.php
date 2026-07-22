<?php

require_once __DIR__ . '/../../config/config_db.php';
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../vendor/autoload.php';

$logFile = __DIR__ . '/storage/logs/mark_receive.log';
function logText($msg) {
    global $logFile;
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}

try {
    logText("Получен запрос");

    $json = file_get_contents('php://input');
    logText("RAW JSON: " . $json);

    $data = json_decode($json, true);

    if (!$data || !isset($data['mark'])) {
        logText("Ошибка разбора JSON или отсутствует 'mark'");
        echo json_encode(['error' => 'Неверный формат']);
        exit;
    }

    foreach ($data['mark'] as $orderBlock) {
        $markOrderId = $orderBlock['markOrderId'] ?? null;
        $marks = $orderBlock['marks'] ?? [];

        foreach ($marks as $mark) {
            $orderId     = $mark['orderId'] ?? '';
            $itemCode    = $mark['itemCode'] ?? '';
            $markText    = $mark['mark'] ?? '';
            $markBase64  = $mark['markBase64'] ?? '';

            logText("Добавляем: order=$orderId, code=$itemCode");

            \R::exec("INSERT INTO order_marks 
                (mark_order_id, order_id, item_code, mark, mark_base64) 
                VALUES (?, ?, ?, ?, ?)", 
                [$markOrderId, $orderId, $itemCode, $markText, $markBase64]);
        }
    }

    logText("Добавление завершено");
    echo json_encode(['success' => true]);
    exit;
} catch (Throwable $e) {
    logText("Ошибка: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

