<?php

use PhpOffice\PhpSpreadsheet\Shared\StringHelper; // не обязателен, но пусть будет
use ishop\App;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

try {
    // --- вход
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        throw new RuntimeException('Не передан id задания');
    }

    // --- запись cron
    $cron = \R::findOne('cron', 'id = ?', [$id]);
    if (!$cron) throw new RuntimeException('CRON задание не найдено: id='.$id);

    $date_update = date('Y-m-d H:i');

    // --- парсим категории
    $rawCats = (string)($cron['categories'] ?? '');
    $catIds  = array_values(array_filter(array_map('intval', preg_split('/[,\s]+/', $rawCats)), fn($v)=>$v>0));

    // --- флаги из alias (просто разделяем по не-алфанум символам)
    $alias = mb_strtolower((string)($cron['alias'] ?? ''));
    $flags = preg_split('/[^a-z0-9_]+/i', $alias, -1, PREG_SPLIT_NO_EMPTY);
    $flags = array_map('strtolower', $flags);
    $flagStockOnly = in_array('stock', $flags, true); // => p.rest > 0

    // --- путь к файлу
    $root     = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
    $fileName = $cron['url_download'] ?: 'export.csv';
    $csvPath  = $root . '/public/cron/' . $fileName;

    // убедимся, что каталог есть
    $dir = dirname($csvPath);
    if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Не удалось создать каталог: '.$dir);
    }

    // --- открываем CSV (BOM для Excel)
    $fh = @fopen($csvPath, 'wb');
    if (!$fh) throw new RuntimeException('Не удалось открыть файл для записи: '.$csvPath);
    fwrite($fh, "\xEF\xBB\xBF");

    // заголовки (подстрой при желании)
    $headers = [
        'ID (артикул)', 'Производитель', 'Номенклатура', 'Наличие', 'Цена', 'Категория'
    ];
    fputcsv($fh, $headers, ';', '"');

    // --- собираем WHERE
    $where = ["p.hide = 'show'"];
    $params = [];

    if (!empty($catIds)) {
        $ph = implode(',', array_fill(0, count($catIds), '?'));
        $where[] = "p.category_id IN ($ph)";
        $params = array_merge($params, $catIds);
    }

    if ($flagStockOnly) {
        $where[] = "p.rest > 0";
    }

    $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

    // --- считаем всего и идём батчами
    $total = (int)\R::getCell("
        SELECT COUNT(*) FROM product p
        $whereSql
    ", $params);

    $limit = 1000;
    for ($offset = 0; $offset < $total; $offset += $limit) {
        $batchParams = array_merge($params, [$limit, $offset]);
        $rows = \R::getAll("
            SELECT
                p.article,
                COALESCE(b.name, '')   AS brand_name,
                p.name                 AS product_name,
                p.rest,                        -- актуальный остаток
                p.price,
                COALESCE(c.name, '')   AS category_name
            FROM product p
            LEFT JOIN category c ON c.id = p.category_id
            LEFT JOIN brand    b ON b.id = p.brand_id
            $whereSql
            ORDER BY p.id
            LIMIT ? OFFSET ?
        ", $batchParams);

        foreach ($rows as $r) {
            foreach ($r as $k => $v) {
                if (is_string($v)) {
                    $r[$k] = str_replace(["\r\n","\n","\r"], ' ', $v);
                }
            }
            fputcsv($fh, $r, ';', '"');
        }
    }

    fclose($fh);

    if (!is_file($csvPath) || filesize($csvPath) === 0) {
        throw new RuntimeException('CSV не создан или пуст: '.$csvPath);
    }

    // --- обновляем дату и историю
    \R::exec("UPDATE cron SET date_update = ? WHERE id = ?", [$date_update, $id]);

    $adminId = (int)($_SESSION['user']['id'] ?? 0);
    if ($adminId > 0) {
        \R::exec(
            "INSERT INTO admin_last_history (gh_id, ah_id, name_tbl, id_tbl, date_modified, customer_id)
             VALUES (2, 49, 'cron', ?, ?, ?)",
            [$id, date('Y-m-d H:i:s'), $adminId]
        );
    } else {
        \R::exec(
            "INSERT INTO admin_last_history (gh_id, ah_id, name_tbl, id_tbl, date_modified, customer_id)
             VALUES (2, 51, 'cron', ?, ?, NULL)",
            [$id, date('Y-m-d H:i:s')]
        );
    }

    // режим для системного cron
    if (!empty($_GET['silent'])) {
        echo "OK\n";
        exit;
    }

    $_SESSION['success'] = 'Задание "'.h($cron['name']).'" выполнено! Файл: /public/cron/'.h($fileName);
    redirect(ADMIN . '/cron');

} catch (\Throwable $e) {
    @file_put_contents(APP . '/tmp/cron_errors.log', date('c').' export-csv: '.$e->getMessage()."\n", FILE_APPEND);
    $_SESSION['error'] = 'Ошибка: '.$e->getMessage();
    redirect(ADMIN . '/cron');
}
