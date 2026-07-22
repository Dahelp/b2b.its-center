<?php 

use ishop\App;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    // --- лёгкий лог
    $logFile = APP . '/tmp/cron_export_excel.log';
    $elog = function(string $msg, array $ctx = []) use ($logFile) {
        @file_put_contents($logFile, date('c') . ' ' . $msg . ' ' . json_encode($ctx, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
    };

    // --- вход
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) throw new RuntimeException('Не передан id задания');

    // --- cron
    $cron = \R::findOne('cron', 'id = ?', [$id]);
    if (!$cron) throw new RuntimeException('CRON задание не найдено: id='.$id);

    $date_update = date('Y-m-d H:i');

    // --- парсим категории (через запятую) и флаги из alias
    $rawCats = (string)($cron['categories'] ?? '');
    $catIds  = array_values(array_filter(array_map('intval', preg_split('/[,\s]+/', $rawCats)), fn($v)=>$v>0));

    $alias = mb_strtolower((string)($cron['alias'] ?? ''));
    $flags = array_map('strtolower', preg_split('/[^a-z0-9_]+/i', $alias, -1, PREG_SPLIT_NO_EMPTY));
    $flagStockOnly = in_array('stock', $flags, true); // => p.rest > 0

    // --- путь сохранения: /public/cron/{url_download}
    $publicDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/public/cron';
    $fileName  = $cron['url_download'] ?: 'export.xlsx';
    $xlsxPath  = $publicDir . '/' . $fileName;

    if (!is_dir($publicDir) && !@mkdir($publicDir, 0775, true) && !is_dir($publicDir)) {
        throw new RuntimeException('Не удалось создать каталог: '.$publicDir);
    }
    if (!is_writable($publicDir)) {
        throw new RuntimeException('Каталог не доступен на запись: '.$publicDir);
    }

    // --- SQL фильтры
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

    $total = (int)\R::getCell("SELECT COUNT(*) FROM product p $whereSql", $params);
    $elog('excel export start', ['id'=>$id, 'xlsx'=>$xlsxPath, 'total'=>$total, 'cats'=>$catIds, 'flags'=>$flags]);

    // --- создаём книгу и шапку
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Выгрузка');

    // Заголовки
    $headers = ['ID (артикул)', 'Производитель', 'Номенклатура', 'Остаток', 'Цена', 'Категория'];
    $sheet->fromArray($headers, null, 'A1');

    // данные построчно
    $limit = 1000;
    $row = 2;

    for ($offset = 0; $offset < $total; $offset += $limit) {
        $batchParams = array_merge($params, [$limit, $offset]);
        $rows = \R::getAll("
            SELECT
                p.article,
                COALESCE(b.name, '')   AS brand_name,
                p.name                 AS product_name,
                p.rest,
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
            // Чистим переносы строк
            foreach ($r as $k=>$v) {
                if (is_string($v)) $r[$k] = str_replace(["\r\n","\n","\r"], ' ', $v);
            }
            // Запись: A=article, B=brand, C=name, D=rest, E=price, F=category
            $sheet->setCellValueExplicit('A'.$row, (string)$r['article']);
            $sheet->setCellValue('B'.$row, $r['brand_name']);
            $sheet->setCellValue('C'.$row, $r['product_name']);
            $sheet->setCellValueExplicit('D'.$row, (float)$r['rest']);
            $sheet->setCellValueExplicit('E'.$row, (float)$r['price']);
            $sheet->setCellValue('F'.$row, $r['category_name']);
            $row++;
        }
    }

    // Немного косметики (необязательно)
    foreach (range('A','F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // --- пишем во временный файл и атомарно заменяем
    $tmpPath = $xlsxPath . '.tmp';
    $writer = new Xlsx($spreadsheet);
    $writer->save($tmpPath);

    if (!is_file($tmpPath) || filesize($tmpPath) === 0) {
        @unlink($tmpPath);
        throw new RuntimeException('XLSX не создан или пуст: '.$tmpPath);
    }

    if (!@rename($tmpPath, $xlsxPath)) {
        @unlink($tmpPath);
        throw new RuntimeException('Не удалось заменить файл: '.$xlsxPath);
    }
    @chmod($xlsxPath, 0664);

    // --- апдейты
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

    // тихий режим для системного cron
    if (!empty($_GET['silent'])) { echo "OK\n"; exit; }

    $_SESSION['success'] = 'Excel-файл готов: /public/cron/' . h($fileName);
    redirect(ADMIN . '/cron');

} catch (\Throwable $e) {
    @file_put_contents(APP . '/tmp/cron_errors.log', date('c').' export-excel: '.$e->getMessage()."\n", FILE_APPEND);
    if (!empty($_GET['silent'])) { echo "ERR: ".$e->getMessage()."\n"; exit(1); }
    $_SESSION['error'] = 'Ошибка: ' . $e->getMessage();
    redirect(ADMIN . '/cron');
}
