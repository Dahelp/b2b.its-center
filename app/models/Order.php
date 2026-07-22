<?php

namespace app\models;

use ishop\App;
use app\services\MailService;

class Order extends AppModel {
	
	public static function saveOrderProduct($order_id) {
    $logFile = ROOT . '/storage/logs/save_order_product_debug.log';
    file_put_contents($logFile, "=== saveOrderProduct started ===\n", FILE_APPEND);

    if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        file_put_contents($logFile, "🟥 КОРЗИНА ПУСТА или НЕ МАССИВ\n", FILE_APPEND);
        return;
    }

    $values = [];
    $params = [];

    foreach ($_SESSION['cart'] as $key => $product) {
        file_put_contents($logFile, "🔹 Ключ: $key\n" . print_r($product, true) . "\n", FILE_APPEND);

        $product_id = (int)($product['id'] ?? 0);
        // ВАЖНО: НЕ превращаем 0 в NULL — для обычного товара mod_id = 0
        $mod_id     = isset($product['mod_id']) ? (int)$product['mod_id'] : 0;

        $article  = (string)($product['article'] ?? '');
        $qty      = (int)($product['qty'] ?? 0);
        $unit     = (string)($product['unit'] ?? 'шт');
        $name     = (string)($product['name'] ?? '');
        $external = (int)($product['external'] ?? 0);

        // Цена: если есть модификационная — берём её, иначе final_price
        if ($mod_id > 0 && isset($product['mod_price'])) {
            $price = (float)$product['mod_price'];
        } else {
            $price = (float)($product['final_price'] ?? 0);
        }

        if (!$product_id && !$mod_id && !$external) {
            file_put_contents($logFile, "⏭ Пропущено: product_id=0 и mod_id=0 и external=0\n", FILE_APPEND);
            continue;
        }
        if ($qty <= 0 || $article === '') {
            file_put_contents($logFile, "⏭ Пропущено: qty<=0 или пустой article\n", FILE_APPEND);
            continue;
        }

        $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        array_push(
            $params,
            (int)$order_id,
            $product_id,
            $mod_id,                     // ← всегда ЧИСЛО (0 или ID модификации)
            $article,
            $qty,
            $unit,
            $name,
            $price,
            0,                           // discount_value
            0,                           // discount_type
            0,                           // discount
            0,                           // price_discount
            0,                           // discount_amount
            $external
        );
    }

    if (!$values) {
        file_put_contents($logFile, "🟡 Нечего вставлять (values пусто)\n", FILE_APPEND);
        return;
    }

    $sql = "INSERT INTO order_product
        (`order_id`, `product_id`, `mod_id`, `article`, `qty`, `unit`, `name`, `price`,
         `discount_value`, `discount_type`, `discount`, `price_discount`, `discount_amount`, `external`)
        VALUES " . implode(",\n", $values);

    file_put_contents($logFile, "🟢 Финальный SQL (параметризованный):\n$sql\nPARAMS:\n" . print_r($params, true) . "\n", FILE_APPEND);

    try {
        \R::exec($sql, $params);
        file_put_contents($logFile, "✅ SQL выполнен успешно\n", FILE_APPEND);
    } catch (\Exception $e) {
        file_put_contents($logFile, "❌ ОШИБКА SQL:\n" . $e->getMessage() . "\n", FILE_APPEND);
    }

    // Диагностика: сразу проверим, как записалось
    try {
        $rows = \R::getAll("SELECT article, product_id, mod_id, qty FROM order_product WHERE order_id = ?", [(int)$order_id]);
        file_put_contents($logFile, "🔍 Проверка после INSERT:\n" . print_r($rows, true) . "\n", FILE_APPEND);
    } catch (\Exception $e) {
        file_put_contents($logFile, "❗ Ошибка при выборке проверки: " . $e->getMessage() . "\n", FILE_APPEND);
    }

    // Страховка (на случай, если вдруг mod_id остался NULL/0 у модификаций — восстановим по article)
    try {
        \R::exec("
            UPDATE order_product op
            JOIN modification m ON m.article = op.article
            SET op.mod_id = m.id,
                op.product_id = m.product_id
            WHERE op.order_id = ? AND (op.mod_id IS NULL OR op.mod_id = 0)
        ", [(int)$order_id]);

        $rows2 = \R::getAll("SELECT article, product_id, mod_id FROM order_product WHERE order_id = ?", [(int)$order_id]);
        file_put_contents($logFile, "🔧 После авто-починки mod_id:\n" . print_r($rows2, true) . "\n", FILE_APPEND);
    } catch (\Exception $e) {
        file_put_contents($logFile, "❗ Ошибка авто-починки: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}



    public static function mailOrder(
    $order_id,
    $user_email,
    $uname,
    $telefon,
    $admin_id,
    $note,
    $date,
    $dostavka_name,
    $branch_name,
    $address,
    $transport_company,
    $city_name,
    $vid,            // параметр оставлен для совместимости, но будет принудительно заменён
    $compname,
    $nds,
    $dogovor,
    $end_buyer_text = ''
){
    // --- ЛОГ ---
    $logDir  = ROOT . '/storage/logs';
    $logPath = $logDir . '/order_mail.log';
    if (!is_dir($logDir)) { @mkdir($logDir, 0775, true); }
    $log = function($m) use ($logDir, $logPath){
        if (is_dir($logDir) && is_writable($logDir)) @file_put_contents($logPath, date('Y-m-d H:i:s')." | {$m}\n", FILE_APPEND);
    };

    // --- ЗАКАЗ ---
    $ord = \R::getRow('SELECT * FROM `order` WHERE id = ? LIMIT 1', [(int)$order_id]);
    if (!$ord) { $log("mailOrder: order not found id={$order_id}"); return; }

    $order_inv = trim((string)($ord['inv'] ?? ''));
    if ($order_inv === '') { $log("mailOrder: inv empty, skip send id={$order_id}"); return; }

    $date = $ord['date'] ?? $date;

    // --- КОМПАНИЯ / НДС ---
    $comp_id = (int)($ord['comp_id'] ?? 0);
    $compRow = $comp_id ? \R::getRow('SELECT comp_short_name, nds FROM company WHERE id = ? LIMIT 1', [$comp_id]) : [];
    $compname = trim((string)($compRow['comp_short_name'] ?? '')) ?: (string)($compname ?? '');

    $nds_val  = $compRow['nds'] ?? $nds;
    $nds_text = (is_numeric($nds_val) ? (((int)$nds_val === 1) ? 'с НДС' : 'без НДС')
               : ((mb_stripos((string)$nds_val, 'ндс') !== false) ? 'с НДС' : 'без НДС'));

    // --- ВИД КЛИЕНТА: ТОЛЬКО "Юридическое лицо" ---
    $vid = 'Юридическое лицо';

    // --- ДОСТАВКА / САМОВЫВОЗ ---
    $isPickup = ((int)($ord['dostavka_id'] ?? 0) === 1) || (mb_stripos((string)$dostavka_name, 'самовывоз') !== false);
    if ($isPickup && empty($branch_name)) {
        $branch_name = \R::getCell('SELECT branch_name FROM branch_office WHERE branch_id = ? LIMIT 1', [(int)($ord['branch_id'] ?? 0)]) ?: '';
    }
    if (empty($city_name)) {
        if (!empty($ord['city_id'])) {
            $city_name = \R::getCell('SELECT name FROM cities WHERE id = ? LIMIT 1', [(int)$ord['city_id']]) ?: '';
        }
        if (empty($city_name) && !empty($ord['city_text'])) $city_name = $ord['city_text'];
    }

    // --- ПОЗИЦИИ (после 1С) ---
    $rows = \R::getAll('SELECT name, qty, price FROM order_product WHERE order_id = ? ORDER BY id', [(int)$order_id]);
    $qtyAll = 0; $sumAll = 0.0;
    foreach ($rows as $r) {
        $q = (int)($r['qty'] ?? 0);
        $p = (float)($r['price'] ?? 0);
        $qtyAll += $q;
        $sumAll += $p * $q;
    }

    // --- ВАЛЮТА ---
    $currency = \ishop\App::$app->getProperty('currency') ?: ['symbol_left'=>'','symbol_right'=>''];
    $symL = $currency['symbol_left'] ?? ''; $symR = $currency['symbol_right'] ?? '';
    $fmt = function($n){ return number_format((float)$n, 0, '.', ' '); };

    // --- ПРОЧЕЕ/Экранирование ---
    $e = function($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
    $shopName  = \ishop\App::$app->getProperty('shop_name');
    $tell_site = \ishop\App::options('option_telefon');
    $PATH      = defined('PATH') ? PATH : '';
    $km_out    = (!empty($end_buyer_text) && mb_strtolower(trim($end_buyer_text)) !== 'нет') ? 'Да' : 'Нет';

    $unameE      = $e($uname);
    $shopNameE   = $e($shopName);
    $branchE     = $e($branch_name);
    $addrE       = $e($address);
    $tkE         = $e($transport_company);
    $cityE       = $e($city_name);
    $vidE        = $e($vid);
    $compE       = $e($compname);
    $ndsE        = $e($nds_text);
    $dogovorE    = $e($dogovor);
    $telE        = $e($telefon);
    $emailE      = $e($user_email);
    $noteE       = nl2br($e($note));
    $dateE       = $e($date);
    $invE        = $e($order_inv);
    $pathE       = $e($PATH);
    $tellSiteE   = $e($tell_site);

    // --- Таблица позиций ---
    $itemsHtml = '';
    foreach ($rows as $r) {
        $name  = $e($r['name'] ?? '');
        $qty   = (int)($r['qty'] ?? 0);
        $price = (float)($r['price'] ?? 0);
        $sum   = $price * $qty;
        $itemsHtml .= '<tr>'
                    . '<td style="padding:8px; border:1px solid #ddd;">'.$name.'</td>'
                    . '<td style="padding:8px; border:1px solid #ddd; text-align:center;">'.$qty.'</td>'
                    . '<td style="padding:8px; border:1px solid #ddd; text-align:right;">'.$symL.$fmt($price).($symR ? ' '.$symR : '').'</td>'
                    . '<td style="padding:8px; border:1px solid #ddd; text-align:right;">'.$symL.$fmt($sum).($symR ? ' '.$symR : '').'</td>'
                    . '</tr>';
    }

    // --- Блок доставки ---
    if ($isPickup) {
        $deliveryHtml  = '<p><strong>Способ доставки:</strong> Самовывоз<br>';
        if ($branchE !== '') $deliveryHtml .= '<strong>Пункт выдачи:</strong> '.$branchE.'<br>';
        if ($addrE   !== '') $deliveryHtml .= '<strong>Адрес:</strong> '.$addrE.'<br>';
        $deliveryHtml .= '</p>';
    } else {
        $deliveryHtml  = '<p><strong>Способ доставки:</strong> Транспортная компания<br>'
                       . '<strong>Название ТК:</strong> '.$tkE.'<br>';
        if ($cityE !== '') $deliveryHtml .= '<strong>Город:</strong> '.$cityE.'<br>';
        $deliveryHtml .= '</p>';
    }

    $dow = date('w', strtotime($date));
    $helloLine = ($dow>0 && $dow<6)
        ? 'Ваш заказ на сайте '.$shopNameE.' оформлен. Для согласования заказа с Вами свяжется менеджер в рабочее время ПН-ПТ с 09:00 до 17:00'
        : 'Ваш заказ на сайте '.$shopNameE.' оформлен. Для согласования заказа с Вами свяжется менеджер в понедельник в рабочее время с 09:00 до 17:00';

    // --- ТЕЛО ПИСЬМА ---
    $body =
'<!doctype html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Заказ №'.$invE.' на сайте '.$shopNameE.'</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<table style="width:740px;background-color:#f4f6f9;font-family:Tahoma, Helvetica, sans-serif;color:#212529;font-size:13px;border:1px solid #eee;margin:0 auto">
  <tr>
    <td style="padding:20px;width:300px">
      <img src="'.$pathE.'/images/logo.png" alt="'.$shopNameE.'" style="width:260px;height:50px">
    </td>
    <td style="padding:20px;width:440px;font-weight:bold" align="right">
      <a href="'.$pathE.'" style="color:#2C3E50">Главная</a> |
      <a href="'.$pathE.'/catalog" style="color:#2C3E50">Каталог</a> |
      <a href="'.$pathE.'/services/dostavka" style="color:#2C3E50">Доставка</a> |
      <a href="'.$pathE.'/pages/contacts" style="color:#2C3E50">Контакты</a>
    </td>
  </tr>
  <tr>
    <td colspan="2">
      <table cellspacing="0" cellpadding="0" style="width:700px;background:#fff;font-size:13px" align="center">
        <tr>
          <td>
            <table cellspacing="0" cellpadding="0" style="width:660px;padding:20px;font-family:Tahoma, Helvetica, sans-serif;color:#212529;font-size:13px" align="center">
              <tr>
                <td style="padding:20px 0 20px 0">
                  <p>Здравствуйте, '.$unameE.'.</p>
                  <p>Благодарим Вас за заказ!<br>'.$helloLine.'</p>
                  <p><strong>Ваш заказ: № '.$invE.' от '.$dateE.'</strong></p>

                  <table style="border:1px solid #ddd; border-collapse:collapse; width:100%;">
                    <thead>
                      <tr style="background:#f9f9f9;">
                        <th style="padding:8px; border:1px solid #ddd; text-align:left;">Наименование</th>
                        <th style="padding:8px; border:1px solid #ddd; text-align:center;">Кол-во</th>
                        <th style="padding:8px; border:1px solid #ddd; text-align:right;">Цена</th>
                        <th style="padding:8px; border:1px solid #ddd; text-align:right;">Сумма</th>
                      </tr>
                    </thead>
                    <tbody>'.
                      $itemsHtml.
                     '<tr>
                        <td colspan="3" style="padding:8px; border:1px solid #ddd; text-align:right;"><strong>Итого товаров:</strong></td>
                        <td style="padding:8px; border:1px solid #ddd; text-align:right;">'.$qtyAll.'</td>
                      </tr>
                      <tr>
                        <td colspan="3" style="padding:8px; border:1px solid #ddd; text-align:right;"><strong>На сумму:</strong></td>
                        <td style="padding:8px; border:1px solid #ddd; text-align:right;">'.$symL.$fmt($sumAll).($symR ? ' '.$symR : '').'</td>
                      </tr>
                    </tbody>
                  </table>

                  <br>

                  <p><strong>Вывести КМ из оборота:</strong> '.$km_out.'</p>

                  '.$deliveryHtml.'

                  <p>
                    <strong>Вид клиента:</strong> '.$vidE.'<br>
                    <strong>Компания (зарегистрирована):</strong> '.$compE.'<br>
                    <strong>Налогообложение:</strong> '.$ndsE.'<br>
                    <strong>Условия поставки:</strong> '.$dogovorE.'
                  </p>

                  <p>
                    <strong>Имя:</strong> '.$unameE.'<br>
                    <strong>Номер телефона:</strong> '.$telE.'<br>
                    <strong>E-mail:</strong> <a href="mailto:'.$emailE.'" target="_blank">'.$emailE.'</a><br>'.
                    (!empty($note) ? '<strong>Комментарий:</strong> '.$noteE.'<br>' : '').
                   '<strong>Время заказа:</strong> '.$dateE.'
                  </p>

                  <p>С уважением, '.$shopNameE.'<br>
                  <strong>Телефон:</strong> '.$tellSiteE.'</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr><td colspan="2" style="padding:20px"></td></tr>
</table>
</body>
</html>';

    // --- SMTP ОТПРАВКА ---
    $adminEmail = \ishop\App::$app->getProperty('admin_email');

    try {
        $subjClient = "Вы совершили заказ №{$order_inv} на сайте {$shopName}";
        $subjAdmin  = "Сделан заказ №{$order_inv} на сайте {$shopName}";

        if (!empty($user_email) && filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
            MailService::sendHtml($user_email, $subjClient, $body);
        }
        if (!empty($adminEmail) && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            MailService::sendHtml($adminEmail, $subjAdmin, $body);
        }
        if (!empty($admin_id) && (string)$admin_id !== '0') {
            $adm = \R::findOne('user', 'id = ?', [$admin_id]);
            if ($adm && !empty($adm['email'])) {
                MailService::sendHtml($adm['email'], $subjAdmin, $body);
            }
        }
    } catch (\Throwable $e) {
        $log('SMTP error: ' . get_class($e));
    }

    // --- ОЧИСТКА СЕССИИ И СООБЩЕНИЕ ---
    unset($_SESSION['cart'], $_SESSION['cart.qty'], $_SESSION['cart.sum'], $_SESSION['cart.weight'], $_SESSION['cart.volume'], $_SESSION['cart.currency']);
    $dow = date('w', strtotime($date));
    $_SESSION['success'] = ($dow>0 && $dow<6)
        ? 'Спасибо за Ваш заказ. Для согласования заказа с Вами свяжется менеджер в рабочее время ПН-ПТ с 09:00 до 17:00'
        : 'Спасибо за Ваш заказ. Для согласования заказа с Вами свяжется менеджер в понедельник в рабочее время с 09:00 до 17:00';
}


	public static function sendTo1C($order_id)
    {
        $order = \R::load('order', $order_id);
        $adminEmail = App::$app->getProperty('admin_email');

        if (!$order->id) {
            return ['success' => false, 'error' => "Заказ с ID {$order_id} не найден"];
        }

        $products     = \R::getAll("SELECT * FROM order_product WHERE order_id = ?", [$order_id]);
        $comp         = \R::load('company', $order->comp_id);
        $tk           = \R::load('transport_company', $order->transport_id);
        $dostavka_id  = (int)($order->dostavka_id ?? 0);

        // ==== Город (по city_id ИЛИ по city_text) ====
        $cityName = '';
        if (!empty($order->city_id)) {
            $cityBean = \R::load('cities', (int)$order->city_id);
            if ($cityBean && $cityBean->id) $cityName = $cityBean->city_name ?? '';
        }
        if ($cityName === '' && !empty($order->city_text)) {
            $cityName = $order->city_text;
        }
        $cityChunk = $cityName ? ('г. ' . $cityName . '.') : '';

        // ==== Пункт самовывоза ====
        $branchDebug = [
            'input_branch_id'   => (int)($order->branch_id ?? 0),
            'by_branch_id_row'  => null,
            'by_id_row'         => null,
            'fallback_branch_1' => null,
        ];

        $branchName = '';
        $branchId   = (int)($order->branch_id ?? 0);

        // 1) по branch_id
        if ($branchId > 0) {
            $row = \R::getRow("SELECT * FROM branch_office WHERE branch_id = ? LIMIT 1", [$branchId]);
            $branchDebug['by_branch_id_row'] = $row ?: null;
            if (!empty($row)) {
                $branchName = trim((string)($row['branch_name'] ?? ''));
            }
        }

        // 2) если пусто — пробуем по id (вдруг в БД подразумевали первичный ключ id)
        if ($branchName === '' && $branchId > 0) {
            $rowById = \R::getRow("SELECT * FROM branch_office WHERE id = ? LIMIT 1", [$branchId]);
            $branchDebug['by_id_row'] = $rowById ?: null;
            if (!empty($rowById)) {
                $branchName = trim((string)($rowById['branch_name'] ?? ''));
            }
        }

        // 3) фолбэк на branch_id = 1
        if ($branchName === '') {
            $row1 = \R::getRow("SELECT * FROM branch_office WHERE branch_id = 1 LIMIT 1");
            $branchDebug['fallback_branch_1'] = $row1 ?: null;
            if (!empty($row1)) {
                $branchName = trim((string)($row1['branch_name'] ?? ''));
            }
        }

        $tkName = $tk['name'] ?? '';
        $note   = trim($order->note ?? '');

        // ==== Параметры доставки / комментарий для 1С ====
        $typeDelivery = '';
        $nameDelivery = '';
        $noteDelivery = '';

        if ($dostavka_id === 1) {
            // Самовывоз: обязательно указываем пункт выдачи
            $typeDelivery = 'Самовывоз';
            $noteDelivery = 'Самовывоз. Пункт выдачи: ' . ($branchName ?: '—') . '.';
        } elseif ($dostavka_id === 2) {
            // До ТК: приклеиваем город в формате "г. ... ."
            $typeDelivery = 'ДоставкаДоТранспортнойКомпании';
            $nameDelivery = $tkName;
            $noteDelivery = 'До терминала: ' . ($nameDelivery ?: '—') . '. ' . $cityChunk;
            $noteDelivery = trim($noteDelivery);
        } elseif ($dostavka_id === 3) {
            // Курьерка: "г. ... . Адрес: ..."
            $typeDelivery = 'КурьерскаяДоставка';
            $noteDelivery = ($cityChunk ? $cityChunk . ' ' : '') .
                            ($order->address ? ('Адрес: ' . $order->address) : '');
            $noteDelivery = trim($noteDelivery);
            if ($noteDelivery !== '' && substr($noteDelivery, -1) !== '.') {
                $noteDelivery .= '.';
            }
        }

        if ($note !== '') {
            $noteDelivery .= ($noteDelivery ? ' ' : '') . 'Примечание: ' . $note;
        }

        // ==== Позиции ====
        $items = [];
        foreach ($products as $product) {
            $items[] = [
                'itemCode' => $product['article'] ?? '',
                'qnt'      => (int)($product['qty'] ?? 0),
                'price'    => (float)($product['price'] ?? 0),
            ];
        }

        // ==== Пакет ====
        $payload = [
            'typeDelivery'    => $typeDelivery,
            'nameDelivery'    => $nameDelivery,
            'noteDelivery'    => $noteDelivery,
            'customer'        => $comp['guid'] ?? '',
            'items'           => $items,
        ];

        $guid     = trim((string)($order->guid_1c ?? ''));
        $isUpdate = $guid !== '';
        if ($isUpdate) {
            $payload['orderId'] = $guid; // некоторые конфигурации 1С хотят guid и в теле при PUT
        }

        $methodOverride = $isUpdate ? "order/{$guid}" : null;
        $httpMethod     = $isUpdate ? 'PUT' : 'POST';

        $response = \app\helpers\ApiClient::sendRequest($payload, 'api_orders.php', $methodOverride, $httpMethod);

        // ==== Расширенный лог ====
        $debugBlock = [
            'order_id'     => (int)$order_id,
            'dostavka_id'  => $dostavka_id,
            'order_branch_id' => (int)($order->branch_id ?? 0),
            'order_city_id'   => (int)($order->city_id ?? 0),
            'order_city_text' => (string)($order->city_text ?? ''),
            'resolved' => [
                'branchName'   => $branchName,
                'cityName'     => $cityName,
                'cityChunk'    => $cityChunk,
                'tkName'       => $tkName,
                'noteDelivery' => $noteDelivery,
            ],
            'branch_debug' => $branchDebug,
        ];

        $log  = "====== Заказ #{$order_id} ======\n";
        $log .= "Дата: " . date('Y-m-d H:i:s') . "\n";
        $log .= "DEBUG:\n" . json_encode($debugBlock, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
        $log .= "URL: " . ($response['url'] ?? '') . "\n";
        $log .= "Payload:\n" . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
        $log .= "Ответ:\n" . print_r($response['response'] ?? null, true) . "\n";
        $log .= "Ошибки: " . ($response['error'] ?? 'нет') . "\n\n";
        @file_put_contents(ROOT . '/storage/logs/order_1c.log', $log, FILE_APPEND);

        // ==== Обработка ответа ====
        $httpCode = (int)($response['http_code'] ?? 0);
        $ok = !empty($response['success']) && $httpCode >= 200 && $httpCode < 300;

        if ($ok) {
            if ($isUpdate) {
                $number = $response['response']['data']['number'] ?? null;
                if ($number) {
                    $order->inv = $number;
                    $order->update_at = date('Y-m-d H:i:s');
                    \R::store($order);
                }
            } else {
                $orderId1C   = $response['response']['data']['orderId'] ?? '';
                $orderNumber = $response['response']['data']['number']  ?? null;

                if ($orderId1C) {
                    $order->guid_1c = $orderId1C;
                    if ($orderNumber) $order->inv = $orderNumber;
                    $order->update_at = date('Y-m-d H:i:s');
                    \R::store($order);
                } else {
                    return ['success' => false, 'error' => '1С не вернула orderId при создании'];
                }
            }

            return [
                'success' => true,
                'guid'    => (string)$order->guid_1c,
                'number'  => $order->inv,
                'message' => $isUpdate ? 'Заказ обновлён' : 'Заказ создан',
            ];
        }

        // Ошибка: уведомим и вернём ошибку
        $errText = $response['error']
            ?? ($response['response']['message'] ?? 'Ошибка при отправке заказа');
        @mail(
            $adminEmail,
            "❗ Не удалось отправить заказ #{$order_id}",
            "Ошибка при отправке заказа:\n\n{$log}"
        );

        return [
            'success' => false,
            'error'   => $errText . ' (подробности в логе)',
        ];
    }

	public static function updateOrder($order_id, $post)
    {
        header('Content-Type: application/json; charset=UTF-8');
        error_reporting(E_ALL & ~E_NOTICE);

        $order = \R::load('order', $order_id);
        if (!$order || !$order->id) {
            echo json_encode(['success' => false, 'error' => 'Заказ не найден']); exit;
        }
        if ((int)$order->status !== 1) {
            echo json_encode(['success' => false, 'error' => 'Редактировать можно только новый заказ']); exit;
        }

        // Тип компании (для расчёта цен ТОЛЬКО для НОВЫХ строк)
        $company = null;
        if (!empty($_SESSION['b2buser']['comp_id'])) {
            $company = \R::load('company', (int)$_SESSION['b2buser']['comp_id']);
        }
        $companyTip = $company ? (int)$company->tip : 0; // 1=розн, 2=опт, 3=спец

        // ------- Параметры "шапки" заказа -------
        $dostavka_id  = (int)($post['dostavka_id'] ?? 0);
        $transport_id = (int)($post['transport_id'] ?? 0);
        $branch_id    = (int)($post['branch_id'] ?? 0);
        $address      = trim($post['address'] ?? '');
        $note         = $post['note'] ?? '';
        $end_buyer    = !empty($post['end_buyer']) ? 1 : 0;

        // Город: либо id из справочника, либо свободный текст (city_name)
        $city_id   = (int)($post['city_id'] ?? 0);
        $city_name = trim($post['city_name'] ?? '');

        // Санитизация своего города
        if ($city_name !== '') {
            $city_name = mb_substr($city_name, 0, 100);
            $city_name = preg_replace("/[^\\p{L}\\s\\-\\.\\']+/u", '', $city_name);
            $city_name = preg_replace('/\\s+/u', ' ', $city_name);
            $city_name = trim($city_name);
        }

        // Если выбрана ТК — обязателен город (id или текст)
        if ($dostavka_id === 2) {
            if ($city_id <= 0 && $city_name === '') {
                echo json_encode(['success' => false, 'error' => 'Укажите город для доставки ТК (выберите из списка или введите свой).']); exit;
            }
        }

        // Если пришёл city_id, можно валидировать его наличие в справочнике (не обязательно)
        if ($city_id > 0 && $dostavka_id !== 1) {
            $cityBean = \R::load('cities', $city_id);
            if (!$cityBean || !$cityBean->id) {
                echo json_encode(['success' => false, 'error' => 'Город не найден в справочнике.']); exit;
            }
        }

        // Обновим "шапку"
        $order->dostavka_id  = $dostavka_id ?: null;
        $order->transport_id = $transport_id ?: null;
        $order->branch_id    = $branch_id ?: null;
        $order->address      = $address;
        $order->note         = $note;
        $order->end_buyer    = $end_buyer;
        $order->update_at    = date('Y-m-d H:i:s');

        // Логика города: сохраняем либо city_id, либо city_text
        if ($city_id > 0) {
            $order->city_id   = $city_id;
            $order->city_text = null;
        } elseif ($city_name !== '') {
            $order->city_id   = null;
            $order->city_text = $city_name;
        } else {
            // Самовывоз/прочее — город не обязателен
            $order->city_id   = null;
            $order->city_text = null;
        }

        \R::store($order);

        // ------- Позиции из формы -------
        $items = $post['items'] ?? $post['products'] ?? [];
        if (!is_array($items)) $items = [];

        // Существующие строки заказа
        $existing = \R::getAll("SELECT * FROM order_product WHERE order_id = ?", [$order->id]);
        $existingMap = []; // ключ "article-mod_id"
        foreach ($existing as $row) {
            $existingMap[ trim((string)$row['article']) . '-' . (int)$row['mod_id'] ] = $row;
        }

        // Выбор цены по типу компании (для НОВЫХ строк)
        $pickPrice = static function($bean, int $tip): float {
            if ($tip === 2 && isset($bean->opt_price)  && $bean->opt_price  !== '') return (float)$bean->opt_price;
            if ($tip === 3 && isset($bean->spec_price) && $bean->spec_price !== '') return (float)$bean->spec_price;
            if (isset($bean->price) && $bean->price !== '') return (float)$bean->price;
            return 0.0;
        };

        // По article определяем источник: modification → product → fallback
        $resolveByArticle = function(string $article, int $productIdFromPost, int $modIdFromPost) {
            $article = trim($article);

            // Если оба ID пришли — считаем модификацией (не дёргаем лишние запросы)
            if ($productIdFromPost > 0 && $modIdFromPost > 0) {
                return ['mode'=>'modification','product_id'=>$productIdFromPost,'mod_id'=>$modIdFromPost,'bean'=>null];
            }

            // 1) точный поиск модификации
            $mod = \R::findOne('modification', 'article = ? LIMIT 1', [$article]);
            if ($mod && $mod->id) {
                return ['mode'=>'modification','product_id'=>(int)$mod->product_id,'mod_id'=>(int)$mod->id,'bean'=>$mod];
            }

            // 2) точный поиск товара
            $product = \R::findOne('product', 'article = ? LIMIT 1', [$article]);
            if ($product && $product->id) {
                return ['mode'=>'product','product_id'=>(int)$product->id,'mod_id'=>0,'bean'=>$product];
            }

            // 3) fallback по product_id
            if ($productIdFromPost > 0) {
                $p = \R::load('product', $productIdFromPost);
                if ($p && $p->id) return ['mode'=>'product','product_id'=>(int)$p->id,'mod_id'=>0,'bean'=>$p];
            }

            return ['mode'=>'none','product_id'=>0,'mod_id'=>0,'bean'=>null];
        };

        // === Обработка позиций ===
        foreach ($items as $item) {
            $article   = trim($item['itemCode'] ?? $item['article'] ?? '');
            $qty       = (int)($item['qnt'] ?? 0);
            $productId = (int)($item['product_id'] ?? 0);
            $modId     = (int)($item['mod_id'] ?? 0);

            if ($qty <= 0 || $article === '') continue;

            $key = $article . '-' . $modId;

            if (isset($existingMap[$key])) {
                // строка уже есть → меняем ТОЛЬКО qty (если отличается)
                $was = $existingMap[$key];
                $wasQty = (int)$was['qty'];
                if ($wasQty !== $qty) {
                    \R::exec("
                        UPDATE order_product
                        SET qty = ?
                        WHERE order_id = ? AND article = ? AND IFNULL(mod_id,0) = ?
                    ", [
                        $qty, (int)$order->id, $article, $modId
                    ]);
                }
                continue;
            }

            // новая строка → резолвим по article
            $res = $resolveByArticle($article, $productId, $modId);

            $finalProductId = $productId;
            $finalModId     = $modId;
            $name           = '';
            $price          = 0.0;
            $external       = 0;

            if ($res['mode'] === 'modification') {
                $finalProductId = $res['product_id'];
                $finalModId     = $res['mod_id'];
                $bean           = $res['bean'] ?: \R::load('modification', $finalModId);
                $name           = $bean->name_modification ?: ($bean->name ?? '');
                $price          = $pickPrice($bean, $companyTip);
            } elseif ($res['mode'] === 'product') {
                $finalProductId = $res['product_id'];
                $finalModId     = 0;
                $bean           = $res['bean'] ?: \R::load('product', $finalProductId);
                $name           = $bean->name;
                $price          = $pickPrice($bean, $companyTip);
            } else {
                // внешний (не нашли в БД)
                $external = 1;
                $name  = $item['name'] ?? "Товар по артикулу {$article}";
                $price = (float)($item['price'] ?? 0);
            }

            \R::exec("
                INSERT INTO order_product
                (order_id, product_id, mod_id, article, name, qty, price, external)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ", [
                (int)$order->id, $finalProductId, $finalModId, $article, $name, $qty, $price, $external
            ]);
        }

        // === ЯВНЫЕ удаления (только то, что пришло в deleted[]) ===
        $deleted = $post['deleted'] ?? [];
        if (!is_array($deleted)) $deleted = [$deleted];
        $deleted = array_values(array_unique(array_map('strval', $deleted)));

        foreach ($deleted as $delKey) {
            [$art, $mid] = explode('-', $delKey, 2);
            if (isset($existingMap[$delKey])) {
                \R::exec("
                    DELETE FROM order_product
                    WHERE order_id = ? AND article = ? AND IFNULL(mod_id,0) = ?
                ", [(int)$order->id, $art, (int)$mid]);
            }
        }

        // === Отправка в 1С ===
        $result = self::sendTo1C($order_id);

        if (!empty($result['success'])) {
            $_SESSION['success'] = $result['message'] ?? 'Заказ обновлён';
            echo json_encode(['success' => true]); exit;
        } else {
            echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Ошибка при отправке']); exit;
        }
    }

}
