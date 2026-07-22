<?php

namespace app\controllers;

use app\models\User;
use app\models\Order;
use app\services\MailService;
use app\services\Api1C;
use app\widgets\cabinet\Cabinet;
use ishop\App;
use ishop\libs\Pagination;
use app\helpers\ApiClient;
use app\helpers\RequestGuard;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class UserController extends AppController {
	
	public function newsletterAction() {
        if (!User::checkAuth()) {
            redirect('/');
            exit;
        }

        $userId = $_SESSION['b2buser']['id'] ?? null;
        if (!$userId) {
            redirect('/');
            exit;
        }

        $newsletters = \R::getAll("SELECT * FROM newsletter");
        $user = \R::findOne('user', 'id = ?', [$userId]);

        $this->setMeta('Подписки на новости');
        $this->set(compact('newsletters', 'user'));
    }
	
	public function addnewsletterAction() {
		RequestGuard::requirePost(true);
		RequestGuard::requireCsrf(true);

        if (!User::checkAuth()) {
            redirect('/');
            exit;
        }

        $userId = $_SESSION['b2buser']['id'] ?? null;
        if (!$userId || empty($_POST)) {
            redirect('/');
            exit;
        }

        $newsletter_id = (int) ($_POST['newsletter_id'] ?? 0);
        $checked = (int) ($_POST['checked'] ?? 0);

        if ($newsletter_id) {
            if ($checked === 1) {
                \R::exec("DELETE FROM `user_newsletter` WHERE `newsletter_id` = ? AND `user_id` = ?", [$newsletter_id, $userId]);
            } elseif ($checked === 0) {
                \R::exec("INSERT INTO `user_newsletter`(`user_id`, `newsletter_id`) VALUES (?, ?)", [$userId, $newsletter_id]);
                \R::exec("UPDATE `user` SET `newsletter` = '1' WHERE `id` = ?", [$userId]);
            }
        }

        if ($this->isAjax()) {
            $this->loadView('newsletter_block');
        }
    }
	
	public function deletenewsletterAction() {
		RequestGuard::requirePost(true);
		RequestGuard::requireCsrf(true);

        if (!User::checkAuth()) {
            redirect('/');
            exit;
        }

        $userId = $_SESSION['b2buser']['id'] ?? null;
        $checked = (int) ($_POST['checked'] ?? -1);

        if (!$userId || $checked === -1) {
            redirect('/');
            exit;
        }

        if ($checked === 1) {
            \R::exec("DELETE FROM `user_newsletter` WHERE `user_id` = ?", [$userId]);
            \R::exec("UPDATE `user` SET `newsletter` = '0' WHERE `id` = ?", [$userId]);
        } elseif ($checked === 0) {
            \R::exec(
                'INSERT IGNORE INTO user_newsletter (user_id, newsletter_id) SELECT ?, id FROM newsletter',
                [(int)$userId]
            );
            \R::exec("UPDATE `user` SET `newsletter` = '1' WHERE `id` = ?", [$userId]);
        }

        if ($this->isAjax()) {
            $this->loadView('newsletter_block');
        }
    }
	
	public function notificationsAction() {
        if (!User::checkAuth()) {
            redirect('/');
            exit;
        }
        $this->setMeta('Сообщения');
    }
	
    public function loginAction() {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            RequestGuard::requirePost();
            RequestGuard::requireCsrf();
            $user = new User();
            if ($user->login()) {
                unset($_SESSION['error']);
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                session_regenerate_id(true);
                $_SESSION['success'] = 'Вы успешно авторизованы';
                session_write_close();

                if ($_SESSION['b2buser']['role'] === 'b2buser') {
                    redirect('cabinet');
                } elseif ($_SESSION['b2buser']['role'] === 'admin') {
                    redirect(ADMIN);
                } else {
                    $_SESSION['error'] = 'Доступ запрещен';
                    redirect();
                }
                exit;
            } else {
                $_SESSION['error'] = 'Логин/пароль введены неверно';
                redirect();
                exit;
            }
        }

        $this->setMeta('Вход');
    }

    public function logoutAction() {
        RequestGuard::requirePost();
        RequestGuard::requireCsrf();
        unset($_SESSION['b2buser']);
        unset($_SESSION['form_data']);
        unset($_SESSION['csrf_token']);
        session_regenerate_id(true);
        redirect('/');
    }
	
	public function cabinetAction() {
        if (!User::checkAuth()) {
            redirect('/');
            exit;
        }
        $this->setMeta('Личный кабинет');
    }

    public function editAction() {
        if (!User::checkAuth()) { redirect('/'); exit; }

        if (!empty($_POST)) {
            RequestGuard::requirePost();
            RequestGuard::requireCsrf();
            $user = \R::load('user', $_SESSION['b2buser']['id'] ?? 0);
            if (!$user->id) {
                $_SESSION['error'] = 'Пользователь не найден';
                redirect();
                exit;
            }

            $userModel = new User();
            $data = $_POST;
            $passwordChanged = false;

            if (!empty($_POST['old_password']) && !empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
                if (!password_verify($_POST['old_password'], $user->password)) {
                    $_SESSION['error'] = 'Старый пароль неверен';
                    redirect();
                    exit;
                }
                if ($_POST['new_password'] !== $_POST['confirm_password']) {
                    $_SESSION['error'] = 'Пароли не совпадают';
                    redirect();
                    exit;
                }
                if (!$userModel->validatePassword($_POST['new_password'])) {
                    redirect();
                    exit;
                }
                $user->password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $passwordChanged = true;
            }

            unset($data['password'], $data['old_password'], $data['new_password'], $data['confirm_password']);
            $user->import($data);

            if (!$userModel->validate($data) || !$userModel->checkUnique()) {
                $_SESSION['error'] = 'Ошибка валидации!';
                redirect();
                exit;
            }

            if (\R::store($user)) {
                $_SESSION['b2buser']['email'] = $user->email;
                $_SESSION['b2buser']['name'] = $user->name;

                $_SESSION['success'] = $passwordChanged ? 'Пароль успешно изменён!' : 'Изменения сохранены';
            } else {
                $_SESSION['error'] = 'Ошибка сохранения данных.';
            }

            redirect('cabinet');
            exit;
        }

        $this->setMeta('Изменение личных данных');
    }


	
	public function companyAction(){
        if (!User::checkAuth()) { redirect('/'); exit; }
		$category = \R::getAll("SELECT * FROM category");
		$company = \R::findOne('company', 'user_id = ?', [$_SESSION['b2buser']['id']]);
        $this->setMeta('Компания');
        $this->set(compact('company', 'category'));
        
    }

    public function ordersAction() {
    if (!User::checkAuth()) {
        redirect('/');
        exit;
    }

    $userId = (int)$_SESSION['b2buser']['id'];
    $status = $_GET['status'] ?? null;

    // фильтр
    $where  = "o.user_id = ?";
    $params = [$userId];

    if ($status !== null && in_array($status, ['1','2','3','4','5','6','7'], true)) {
        $where   .= " AND o.status = ?";
        $params[] = (int)$status;
    }

    // пагинация
    $page    = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perpage = App::$app->getProperty('pagination') ?? 10;

    // ВАЖНО: считаем заказы без привязки к наличию позиций
    $total = \R::getCell("
        SELECT COUNT(*) 
        FROM `order` o
        WHERE $where
    ", $params);

    $pagination = new Pagination($page, $perpage, $total);
    $start = $pagination->getStart();

    // Список: LEFT JOIN, чтобы показывать заказы и без позиций
    $orders = \R::getAll("
        SELECT 
            o.id, o.inv, o.currency, o.date, o.update_at,
            o.status, o.status_1c, o.guid_1c,
            ROUND(COALESCE(SUM(op.price * op.qty), 0), 2) AS sum
        FROM `order` o
        LEFT JOIN `order_product` op ON op.order_id = o.id
        WHERE $where
        GROUP BY o.id
        ORDER BY o.date DESC, o.id DESC
        LIMIT ?, ?
    ", array_merge($params, [$start, $perpage]));

    $log = "[START] Обновление заказов: найдено " . count($orders) . " заказов\n";

    // нормализатор артикула из 1С: срезаем ведущие нули только у чисто числовых
    $normalizeArticle = function (string $a): string {
        $a = trim($a);
        if ($a === '') return '';
        if (ctype_digit($a)) {
            $a = ltrim($a, '0');
            if ($a === '') $a = '0';
        }
        return $a;
    };

    foreach ($orders as &$order) {
        // если нет GUID — заказ покажем, но не синкаем
        if (empty($order['guid_1c'])) {
            $log .= "⏭ Пропущен заказ ID={$order['id']} — guid_1c пуст\n";
            continue;
        }

        $syncTtl = max(5, (int)(getenv('API_1C_ORDER_CACHE_TTL') ?: 30));
        $lastSync = !empty($order['update_at']) ? strtotime((string)$order['update_at']) : false;
        if ($lastSync !== false && $lastSync > time() - $syncTtl) {
            continue;
        }

        // подтянуть актуальные данные из 1С
        $apiData = Api1C::getOrderData((string)$order['guid_1c']);
        if ($apiData === null) {
            $log .= "❌ API ошибка для заказа ID={$order['id']}\n";
            continue;
        }

        $orderInDb = \R::load('order', (int)$order['id']);
        $changed = false;

        // номер из 1С
        if (!empty($apiData['number']) && $order['inv'] !== $apiData['number']) {
            $orderInDb->inv = $apiData['number'];
            $order['inv']   = $apiData['number'];
            $changed = true;
            $log .= "📌 Обновлён номер заказа: {$apiData['number']}\n";
        }

        // позиции из 1С
        $itemsFrom1C = $apiData['items'] ?? [];
        if (is_array($itemsFrom1C) && count($itemsFrom1C) > 0) {
            // удаляем текущие строки заказа и заливаем из 1С (только если 1С вернула непустой список!)
            \R::exec("DELETE FROM order_product WHERE order_id = ?", [(int)$order['id']]);

            foreach ($itemsFrom1C as $it) {
                $raw   = trim($it['itemCode'] ?? '');
                $art   = $normalizeArticle($raw);
                $qty   = (int)($it['qnt'] ?? 0);
                $priceRaw = (string)($it['price'] ?? '0');
                $priceRaw = str_replace(',', '.', $priceRaw);
                $price    = number_format((float)$priceRaw, 2, '.', '');
                $name  = (string)($it['itemName'] ?? '');

                // приоритет — модификация по НОРМАЛИЗОВАННОМУ артикулу
                $mod = \R::findOne('modification', 'article = ? LIMIT 1', [$art]);

                $productId = null;
                $modId     = 0;

                if ($mod && $mod->id) {
                    $productId = (int)$mod->product_id;
                    $modId     = (int)$mod->id;
                } else {
                    $product = \R::findOne('product', 'article = ? LIMIT 1', [$art]);
                    if ($product && $product->id) {
                        $productId = (int)$product->id;
                        $modId     = 0;
                    }
                }

                $isExternal = $productId === null ? 1 : 0;

                \R::exec("
                    INSERT INTO order_product
                    (order_id, product_id, mod_id, article, name, qty, price, external)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ", [
                    (int)$order['id'],
                    $isExternal ? null : $productId,
                    $isExternal ? 0    : $modId,
                    $art,
                    $name,
                    $qty,
                    $price,
                    $isExternal,
                ]);

                if ($isExternal) {
                    $log .= "⚠️ ВНЕШНИЙ товар: {$raw} → {$art}\n";
                } else {
                    $log .= ($modId > 0)
                        ? "➕ MOD: article={$art}, product_id={$productId}, mod_id={$modId}\n"
                        : "➕ PRODUCT: article={$art}, product_id={$productId}\n";
                }
            }

            $changed = true;

            // обновим сумму для отображения сразу
            $order['sum'] = (string)\R::getCell("
                SELECT ROUND(COALESCE(SUM(price*qty),0), 2)
                FROM order_product
                WHERE order_id = ?
            ", [(int)$order['id']]);

        } else {
            // 1С вернула пусто — НИЧЕГО не трём, оставляем то, что уже было
            $log .= "ℹ️ Заказ ID={$order['id']}: 1С вернула пустые items — позиции не трогаем\n";
        }

        // статусы 1С
        $status1C = trim(mb_strtolower($apiData['processingStatus'] ?? ''));
        if ($status1C !== '') {
            $statusRow = \R::findOne('order_status', 'LOWER(status_1c_text) = ?', [$status1C]);
            if ($statusRow) {
                if ($order['status'] != $statusRow->id || $orderInDb->status_1c != $statusRow->id) {
                    $orderInDb->status    = $statusRow->id;
                    $orderInDb->status_1c = $statusRow->id;
                    $order['status']      = $statusRow->id;
                    $changed = true;
                    $log .= "✅ Статус обновлён на {$statusRow->id} ({$statusRow->status_name})\n";
                }
            } else {
                $log .= "❌ Не нашли статус в order_status: '{$status1C}'\n";
            }
        }

        // статусы оплаты/отгрузки
        $statusPaymentRaw  = trim($apiData['statusPayment']  ?? '');
        $statusShipmentRaw = trim($apiData['statusShipment'] ?? '');

        if ($statusPaymentRaw !== '') {
            $statusPaymentId = \R::getCell("SELECT id FROM order_status_payment WHERE status_payment = ?", [$statusPaymentRaw]);
            if ($statusPaymentId) {
                $orderInDb->status_payment_id = $statusPaymentId;
                $changed = true;
                $log .= "✅ Оплата → ID={$statusPaymentId}\n";
            } else {
                $log .= "❌ Не нашли статус оплаты: '{$statusPaymentRaw}'\n";
            }
        }

        if ($statusShipmentRaw !== '') {
            $statusShipmentId = \R::getCell("SELECT id FROM order_status_shipment WHERE status_shipment = ?", [$statusShipmentRaw]);
            if ($statusShipmentId) {
                $orderInDb->status_shipment_id = $statusShipmentId;
                $changed = true;
                $log .= "✅ Отгрузка → ID={$statusShipmentId}\n";
            } else {
                $log .= "❌ Не нашли статус отгрузки: '{$statusShipmentRaw}'\n";
            }
        }

        // Фиксируем успешную синхронизацию даже при отсутствии изменений,
        // чтобы просмотр страницы не опрашивал 1С повторно до истечения TTL.
        $orderInDb->update_at = date('Y-m-d H:i:s');
        \R::store($orderInDb);
    }

    $log .= "[END] Завершено\n";
    if (in_array((string)getenv('APP_ENV'), ['local', 'development'], true)) {
        file_put_contents(ROOT . '/storage/logs/status_debug.log', $log, FILE_APPEND | LOCK_EX);
    }

    $this->setMeta('История заказов');
    $this->set(compact('orders', 'status', 'pagination'));
}

  

    public function orderAction() {
        if (!User::checkAuth()) {
            redirect('/');
            exit;
        }

        if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
            $_SESSION['error'] = 'Некорректный ID заказа';
            redirect('/user/orders');
            exit;
        }

        $id = (int)$_GET['id'];
        $user_id = $_SESSION['b2buser']['id'];

        // Основной запрос
        $order = \R::getAll("
            SELECT 
                o.id AS order_id,
                op.id AS order_product_id,
                op.product_id,
                op.mod_id,
                op.article,
                op.qty,
                op.price,
                op.external,
                op.name,

                COALESCE(NULLIF(pp.unload_img, ''), NULLIF(p.unload_img, ''), NULLIF(pp.img, ''), NULLIF(p.img, '')) AS unload_img,
                COALESCE(pp.alias, p.alias) AS alias,
                COALESCE(pp.weight, p.weight) AS weight,
                COALESCE(pp.volume, p.volume) AS volume,
                COALESCE(m.quantity, p.quantity) AS quantity

            FROM `order` o
            JOIN order_product op ON o.id = op.order_id

            LEFT JOIN modification m 
                ON op.mod_id > 0 
                AND op.mod_id = m.id

            LEFT JOIN product p 
                ON p.id = op.product_id

            LEFT JOIN product pp 
                ON pp.id = m.product_id

            WHERE o.user_id = ? 
            AND o.id = ?

            ORDER BY op.id ASC
        ", [$user_id, $id]);

        $order_info = \R::findOne('order', 'user_id = ? AND id = ?', [$user_id, $id]);

        if (!$order_info) {
            $_SESSION['error'] = 'Заказ не найден';
            redirect('/user/orders');
            exit;
        }

        $order_date = date('Y-m-d', strtotime($order_info->date));
        $today = date('Y-m-d');
        $is_editable = ($order_info->status == 1) && ($order_date == $today);
        
        // Загружаем данные по заказу из 1С (API)
        $api_items = [];
        $guid = $order_info->guid_1c ?? null;

        if ($guid) {
        $url = 'order/' . $guid;
        $api_order_data = \app\helpers\ApiClient::sendRawRequest('api_orders.php', $url, 'GET');

        if (!empty($api_order_data['http_code']) && $api_order_data['http_code'] == 200) {
            $raw = $api_order_data['body'];
            $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw); // 💥 удаляем BOM
            $body = json_decode($raw, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                echo '❌ Ошибка JSON: ' . json_last_error_msg(); exit;
            }

            $api_items = $body['items'] ?? [];
        }
    }

    $comp_id = $_SESSION['b2buser']['comp_id'] ?? 0;
    $tip = \R::getCell("SELECT tip FROM company WHERE id = ?", [$comp_id]);

    foreach ($order as &$item) {
    $article = $item['article'] ?? '';
    $local_code = ltrim($article, '0');
    $updated_from_api_order = false;

    // 1. Пробуем обновить из API-заказа
    foreach ($api_items as $api_item) {
        $api_code = ltrim($api_item['itemCode'] ?? '', '0');
        if ($api_code === $local_code) {
            $item['name']  = $api_item['itemName'] ?? $item['name'];
            $item['price'] = $api_item['price'] ?? $item['price'];
            $updated_from_api_order = true;
            break;
        }
    }

    // 2. Если товар внешний или нет в api_items — делаем отдельный запрос
    if (!$updated_from_api_order || empty($item['product_id'])) {
        try {
            $response = \app\helpers\ApiClient::sendRawRequest('api_goods.php', 'tovars?code=' . $article, 'GET');
            $raw = $response['body'] ?? '';
            $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw); // Удаляем BOM
            $data = json_decode($raw, true);

            if (json_last_error() === JSON_ERROR_NONE && !empty($data[0])) {
                $item['name'] = $data[0]['name'] ?? $item['name'];

                if ($tip == 1) {
                    $item['price'] = $data[0]['price_rozn'] ?? $item['price'];
                } elseif ($tip == 2) {
                    $item['price'] = $data[0]['price_opt'] ?? $item['price'];
                } elseif ($tip == 3) {
                    $item['price'] = $data[0]['price_spec'] ?? $item['price'];
                }
            }
        } catch (\Exception $e) {
            // ничего не делаем
        }
    }

    // 3. Получаем наличие (rest) независимо от источника
    try {
        $response = \app\helpers\ApiClient::sendRawRequest('api_goods.php', 'tovars?code=' . $article, 'GET');
        $raw = $response['body'] ?? '';
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
        $data = json_decode($raw, true);

        if (json_last_error() === JSON_ERROR_NONE && !empty($data[0])) {
            $item['rest'] = $data[0]['rest'] ?? ($item['quantity'] ?? 0);
        } else {
            $item['rest'] = $item['quantity'] ?? 0;
        }
    } catch (\Exception $e) {
        $item['rest'] = $item['quantity'] ?? 0;
    }
}
unset($item);




        // Расчёт итогов
        $total_sum = $total_weight = $total_volume = $total_qty = 0;
        foreach ($order as $item) {
            $qty    = (float) $item['qty'];
            $price  = (float) $item['price'];
            $weight = (float) $item['weight'];
            $volume = (float) $item['volume'];

            $total_sum    += $qty * $price;
            $total_weight += $qty * $weight;
            $total_volume += $qty * $volume;
            $total_qty    += $qty;
        }

        $order_info->sum       = $total_sum;
        $order_info->weight    = $total_weight;
        $order_info->volume    = $total_volume;
        $order_info->total_qty = $total_qty;

        $status = \R::findOne('order_status', 'id = ?', [$order_info->status]);
        $dostavka            = \R::getAll("SELECT id, name FROM dostavka WHERE id IN (1, 2) AND hide='show' ORDER BY id");
        $transport_companies = \R::getAll("SELECT id, name FROM transport_company ORDER BY name");
        $cities              = \R::getAll("SELECT id, city_name FROM cities ORDER BY city_name");
        $order_info->city_display_name = trim((string)($order_info->city_text ?? ''));
        if ((int)($order_info->city_id ?? 0) > 0) {
            $savedCityName = \R::getCell(
                "SELECT city_name FROM cities WHERE id = ? LIMIT 1",
                [(int)$order_info->city_id]
            );
            if (is_string($savedCityName) && trim($savedCityName) !== '') {
                $order_info->city_display_name = trim($savedCityName);
            }
        }
        $order_marks         = \R::getAll("
            SELECT om.* 
            FROM order_marks om 
            JOIN `order` o ON o.guid_1c = om.order_id 
            WHERE o.id = ?
        ", [$id]);

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        $this->setMeta('Просмотр заказа');
        $this->set(compact('order', 'order_info', 'status', 'is_editable', 'dostavka', 'transport_companies', 'cities', 'order_marks'));
    }

    public function deleteOrdersAction() {
        RequestGuard::requirePost();
        RequestGuard::requireCsrf();
        if (!User::checkAuth()) {
            redirect('/');
            exit;
        }
    
        $id = $_POST['id'] ?? null;
    
        if (!$id || !is_numeric($id)) {
            $_SESSION['error'] = 'Некорректный ID заказа';
            redirect('/user/orders');
            exit;
        }
    
        $user_id = $_SESSION['b2buser']['id'];
        $order = \R::findOne('order', 'id = ? AND user_id = ?', [$id, $user_id]);
    
        if (!$order) {
            $_SESSION['error'] = 'Заказ не найден';
            redirect('/user/orders');
            exit;
        }
    
        $order_date = date('Y-m-d', strtotime($order->date));
        $today = date('Y-m-d');
    
        if ($order->status != 1 || $order_date !== $today) {
            $_SESSION['error'] = 'Отмена доступна только для сегодняшних заказов со статусом "Новый"';
            redirect('/user/orders');
            exit;
        }
    
        if (empty($order->guid_1c)) {
            $_SESSION['error'] = 'GUID заказа не найден';
            redirect('/user/orders');
            exit;
        }
    
        // === Отправка запроса на отмену
        $response = \app\helpers\ApiClient::sendRequest(
            ['id' => $order->guid_1c],
            'order_cancel.php' // файл конфигурации
        );
    
        if ($response['success']) {
            $_SESSION['success'] = 'Заказ успешно отменён.';
            // При необходимости — обновить статус:
            // \R::exec("UPDATE `order` SET status = 9 WHERE id = ?", [$order->id]);
        } else {
            $_SESSION['error'] = 'Ошибка при отмене заказа: ' . $response['error'];
        }
    
        redirect('/user/orders');
    }
    

    public function retry1cAction() {

        $this->view = false;
        RequestGuard::requirePost();
        RequestGuard::requireCsrf();
    
        if (!User::checkAuth()) {
            redirect('/');
            exit;
        }
    
        $order_id = $_POST['id'] ?? null;
        $user_id = $_SESSION['b2buser']['id'];
    
        if (!$order_id || !is_numeric($order_id)) {
            $_SESSION['error'] = 'Некорректный ID заказа';
            redirect('/user/orders');
            exit;
        }
    
        $order = \R::load('order', $order_id);
        if (!$order || $order->user_id != $user_id) {
            $_SESSION['error'] = 'Доступ к заказу запрещён';
            redirect('/user/orders');
            exit;
        }
    
        $today = date('Y-m-d');
        $orderDate = date('Y-m-d', strtotime($order->date));
        if ($orderDate !== $today) {
            $_SESSION['error'] = 'Повторная отправка возможна только в день создания заказа';
            redirect('/user/orders');
            exit;
        }
    
        $result = \app\models\Order::sendTo1C($order_id);
    
        if (!empty($result['success'])) {
            if (!empty($result['number'])) {
                $order->inv = $result['number'];
            }
            if (!empty($result['guid'])) {
                $order->guid_1c = $result['guid'];
            }
    
            $order->status_1c = 1;
    
            try {
                \R::store($order);
                $_SESSION['success'] = 'Заказ успешно повторно отправлен';
            } catch (\Exception $e) {
                file_put_contents(
                    ROOT . '/storage/logs/order_errors.log',
                    date('Y-m-d H:i:s') . " ❌ Ошибка при повторном сохранении заказа ID={$order_id}: " . $e->getMessage() . "\n",
                    FILE_APPEND
                );
                $_SESSION['error'] = 'Ошибка при сохранении данных заказа. Обратитесь в поддержку.';
            }
        } else {
            $_SESSION['error'] = $result['error'] ?? 'Ошибка при повторной отправке заказа';
        }
    
        redirect('/user/orders');
    } 

    // Поиск товаров в заказе select2
public function productSearchAction() {
    $this->layout = false;

    $request = (int)($_POST['request'] ?? $_GET['request'] ?? 1);

    // 1) Определяем тип компании и поле цены
    $tip = 1; // 1=price, 2=opt_price, 3=spec_price
    if (!empty($_SESSION['b2buser']['comp_id'])) {
        $tipDb = \R::getCell('SELECT tip FROM company WHERE id = ? LIMIT 1', [(int)$_SESSION['b2buser']['comp_id']]);
        if ($tipDb) $tip = (int)$tipDb;
    }
    $priceFieldMap = [1 => 'price', 2 => 'opt_price', 3 => 'spec_price'];
    $priceField = $priceFieldMap[$tip] ?? 'price';

    // 2) Поиск для Select2 (сначала product, потом modification)
if ($request === 1) {
    header('Content-Type: application/json; charset=utf-8');

    $q = trim((string)($_GET['q'] ?? ''));

    if ($q === '') {
        echo json_encode(['items' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Обычный LIKE по введённой строке
    $qLike = '%' . $q . '%';

    // Нормализованный текст: 18*7-8 / 18х7-8 / 18x7-8 -> 1878
    $qNormText = mb_strtolower($q, 'UTF-8');
    $qNormText = str_replace(['ё'], ['е'], $qNormText);
    $qNormText = str_replace(['×', 'х', 'Х', '*'], 'x', $qNormText);
    $qNormText = preg_replace('/[^\p{L}\p{N}]+/u', '', $qNormText);
    $qNormTextLike = $qNormText !== '' ? '%' . $qNormText . '%' : '%__NO_MATCH__%';

    // Только цифры: 18*7-8 -> 1878, 001234 -> 1234
    $qDigits = preg_replace('/\D+/u', '', $q);
    $qDigitsNoZero = ltrim($qDigits, '0');
    $qDigitsLike = $qDigitsNoZero !== '' ? '%' . $qDigitsNoZero . '%' : '%__NO_MATCH__%';

    // Канонический размер: 18*7-8 / 18х7-8 / 18×7-8 -> 18x7-8
    $qSize = mb_strtolower($q, 'UTF-8');
    $qSize = str_replace(['×', 'х', 'Х', '*'], 'x', $qSize);
    $qSize = str_replace(['–', '—', '_'], '-', $qSize);
    $qSize = preg_replace('/\s+/u', '', $qSize);
    $qSizeLike = $qSize !== '' ? '%' . $qSize . '%' : '%__NO_MATCH__%';

    // Для частичного текстового поиска по префиксам: 18x7, 18x7-, 18x7-8
    $qSizeText = str_replace('x', ' ', $qSize);
    $qSizeText = str_replace('-', ' ', $qSizeText);
    $qSizeText = preg_replace('/\s+/u', ' ', trim($qSizeText));
    $qSizeTextLike = $qSizeText !== '' ? '%' . $qSizeText . '%' : '%__NO_MATCH__%';

    $rows = \R::getAll(
        "
        (SELECT
            CONCAT('p:', p.id)       AS sid,
            p.article                AS article,
            p.name                   AS name,
            p.quantity               AS quantity,
            p.{$priceField}          AS vprice,
            1                        AS pri,
            CASE
                WHEN p.article = ? THEN 100
                WHEN TRIM(LEADING '0' FROM p.article) = ? THEN 95
                WHEN p.size_canonical = ? THEN 90
                WHEN p.size_search_compact = ? THEN 85
                WHEN p.name LIKE ? THEN 70
                WHEN p.name_norm_text LIKE ? THEN 65
                WHEN p.name_norm_digits LIKE ? THEN 60
                WHEN p.size_variants LIKE ? THEN 55
                WHEN p.size_search_text_prefixes LIKE ? THEN 50
                WHEN p.size_search_prefixes LIKE ? THEN 45
                ELSE 10
            END AS relevance
         FROM product p
         WHERE p.quantity > 0
           AND p.hide = 'show'
           AND (
                p.name LIKE ?
                OR p.article LIKE ?
                OR TRIM(LEADING '0' FROM p.article) LIKE ?
                OR p.name_norm_text LIKE ?
                OR p.name_norm_digits LIKE ?
                OR p.size_canonical LIKE ?
                OR p.size_variants LIKE ?
                OR p.size_search_compact LIKE ?
                OR p.size_search_prefixes LIKE ?
                OR p.size_search_text_prefixes LIKE ?
           ))

        UNION ALL

        (SELECT
            CONCAT('m:', m.id)       AS sid,
            m.article                AS article,
            p.name                   AS name,
            m.quantity               AS quantity,
            m.{$priceField}          AS vprice,
            0                        AS pri,
            CASE
                WHEN m.article = ? THEN 100
                WHEN TRIM(LEADING '0' FROM m.article) = ? THEN 95
                WHEN p.size_canonical = ? THEN 90
                WHEN p.size_search_compact = ? THEN 85
                WHEN p.name LIKE ? THEN 70
                WHEN p.name_norm_text LIKE ? THEN 65
                WHEN p.name_norm_digits LIKE ? THEN 60
                WHEN p.size_variants LIKE ? THEN 55
                WHEN p.size_search_text_prefixes LIKE ? THEN 50
                WHEN p.size_search_prefixes LIKE ? THEN 45
                ELSE 10
            END AS relevance
         FROM modification m
         JOIN product p ON p.id = m.product_id
         WHERE m.quantity > 0
           AND p.hide = 'show'
           AND (
                p.name LIKE ?
                OR m.article LIKE ?
                OR TRIM(LEADING '0' FROM m.article) LIKE ?
                OR p.name_norm_text LIKE ?
                OR p.name_norm_digits LIKE ?
                OR p.size_canonical LIKE ?
                OR p.size_variants LIKE ?
                OR p.size_search_compact LIKE ?
                OR p.size_search_prefixes LIKE ?
                OR p.size_search_text_prefixes LIKE ?
           ))

        ORDER BY relevance DESC, pri DESC, quantity DESC, name ASC
        LIMIT 30
        ",
        [
            // relevance product
            $q,
            $qDigitsNoZero,
            $qSize,
            $qDigitsNoZero,
            $qLike,
            $qNormTextLike,
            $qDigitsLike,
            $qSizeLike,
            $qSizeLike,
            $qDigitsLike,

            // WHERE product
            $qLike,
            $qLike,
            $qDigitsLike,
            $qNormTextLike,
            $qDigitsLike,
            $qSizeLike,
            $qSizeLike,
            $qDigitsLike,
            $qDigitsLike,
            $qSizeLike,

            // relevance modification
            $q,
            $qDigitsNoZero,
            $qSize,
            $qDigitsNoZero,
            $qLike,
            $qNormTextLike,
            $qDigitsLike,
            $qSizeLike,
            $qSizeLike,
            $qDigitsLike,

            // WHERE modification
            $qLike,
            $qLike,
            $qDigitsLike,
            $qNormTextLike,
            $qDigitsLike,
            $qSizeLike,
            $qSizeLike,
            $qDigitsLike,
            $qDigitsLike,
            $qSizeLike,
        ]
    );

    $items = [];
    foreach ($rows as $r) {
        $price = (float)($r['vprice'] ?? 0);

        $text = $r['article']
            . ' — ' . $r['name']
            . ' — ' . (int)$r['quantity'] . ' шт.'
            . ' — ' . \ishop\App::format_price($price) . ' руб.';

        $items[] = [
            'id' => $r['sid'],
            'text' => $text
        ];
    }

    echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
    exit;
}

    // 3) Добавление позиции (товар/модификация)
    if ($request === 2) {
        header('Content-Type: text/html; charset=utf-8');

        $codeRaw = $_GET['code'] ?? '';
        if ($codeRaw === '' ) { echo ''; exit; }

        // поддержка "p:123", "m:456" и просто "123" (по умолчанию товар)
        $type = (strlen($codeRaw) > 2 && $codeRaw[1] === ':') ? substr($codeRaw, 0, 2) : 'p:';
        $code = (int)(($type === 'p:' || $type === 'm:') ? substr($codeRaw, 2) : $codeRaw);

        // ----- МОДИФИКАЦИЯ -----
        if ($type === 'm:' && $code > 0) {
            $mod = \R::load('modification', $code);
            if (!$mod || !$mod->id || !$mod->product_id) { echo ''; exit; }

            $product = \R::load('product', (int)$mod->product_id);
            if (!$product || !$product->id) { echo ''; exit; }

            $article    = (string)($mod->article ?: $product->article);
            $name       = htmlspecialchars((string)$product->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); // name из товара
            $quantity   = (int)($mod->quantity ?? 0);          // наличие из modification
            $img        = $product->unload_img ?: 'no-image.jpg';
            $dataWeight = (float)($mod->weight ?? $product->weight ?? 0);
            $dataVolume = (float)($mod->volume ?? $product->volume ?? 0);

            // цена из modification по типу
            $price = (float)($mod->{$priceField} ?? 0);
            if ($price <= 0 && $priceField !== 'price') {
                // лёгкий фоллбэк на розницу модификации, чтобы не было 0
                $price = (float)($mod->price ?? 0);
            }
            if ($price <= 0) {
                // последний шанс — цена товара (редкие кейсы)
                $price = (float)($product->{$priceField} ?? $product->price ?? 0);
            }

            $pid = (int)$product->id;
            $html = '<tr class="product type-product" data-article="' . htmlspecialchars($article, ENT_QUOTES) . '" data-order-id="' . $pid . '" data-mod-id="' . (int)$mod->id . '">
                <td><img src="/images/product/unload/' . htmlspecialchars($img, ENT_QUOTES) . '" alt="" width="60"></td>
                <td>' . htmlspecialchars($article, ENT_QUOTES) . '</td>
                <td>' . $name . '</td>
                <td>' . $quantity . '</td>
                <td>
                    <div class="quantity-block" style="display: inline-flex;">
                        <button type="button" class="btn btn-outline-secondary order-quantity-minus" data-order-id="' . $pid . '">-</button>
                        <span class="order-qty-item">
                            <input type="text" class="form-control form-control-sm order-qty-input order-qty-item-' . $pid . '"
                                data-id="' . $pid . '"
                                data-mod-id="' . (int)$mod->id . '"
                                value="1" min="1"
                                max="' . $quantity . '"
                                data-price="' . $price . '"
                                data-weight="' . $dataWeight . '"
                                data-volume="' . $dataVolume . '">
                        </span>
                        <button type="button" class="btn btn-outline-secondary order-quantity-plus" data-order-id="' . $pid . '">+</button>
                    </div>
                </td>
                <td class="total-cell">' . \ishop\App::format_price($price) . '</td>
                <td>
                    <span class="btn btn-sm btn-danger del-item-order" data-order-id="' . $pid . '">
                        <i class="fas fa-times"></i>
                    </span>
                </td>
            </tr>';

            echo $html; exit;
        }

        // ----- ТОВАР -----
        $product = \R::load('product', $code);
        if (!$product || !$product->id) { echo ''; exit; }

        $article    = (string)$product->article;
        $name       = htmlspecialchars((string)$product->name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $quantity   = (int)$product->quantity;
        $img        = $product->unload_img ?: 'no-image.jpg';
        $dataWeight = (float)($product->weight ?? 0);
        $dataVolume = (float)($product->volume ?? 0);

        // цена по типу компании из товара
        $price = (float)($product->{$priceField} ?? 0);
        if ($price <= 0) {
            foreach (['spec_price','opt_price','price'] as $alt) {
                if (!empty($product->{$alt}) && (float)$product->{$alt} > 0) { $price = (float)$product->{$alt}; break; }
            }
        }

        $html = '<tr class="product type-product" data-article="' . htmlspecialchars($article, ENT_QUOTES) . '" data-order-id="' . (int)$product->id . '">
            <td><img src="/images/product/unload/' . htmlspecialchars($img, ENT_QUOTES) . '" alt="" width="60"></td>
            <td>' . htmlspecialchars($article, ENT_QUOTES) . '</td>
            <td>' . $name . '</td>
            <td>' . $quantity . '</td>
            <td>
                <div class="quantity-block" style="display: inline-flex;">
                    <button type="button" class="btn btn-outline-secondary order-quantity-minus" data-order-id="' . (int)$product->id . '">-</button>
                    <span class="order-qty-item">
                        <input type="text" class="form-control form-control-sm order-qty-input order-qty-item-' . (int)$product->id . '"
                            data-id="' . (int)$product->id . '"
                            value="1" min="1"
                            max="' . (int)$product->quantity . '"
                            data-price="' . $price . '"
                            data-weight="' . $dataWeight . '"
                            data-volume="' . $dataVolume . '">
                    </span>
                    <button type="button" class="btn btn-outline-secondary order-quantity-plus" data-order-id="' . (int)$product->id . '">+</button>
                </div>
            </td>
            <td class="total-cell">' . \ishop\App::format_price($price) . '</td>
            <td>
                <span class="btn btn-sm btn-danger del-item-order" data-order-id="' . (int)$product->id . '">
                    <i class="fas fa-times"></i>
                </span>
            </td>
        </tr>';

        echo $html; exit;
    }

    // fallback
    echo ''; exit;
}


    

    public function updateOrderAction() {
        header('Content-Type: application/json');
        RequestGuard::requirePost(true);
        $userId = RequestGuard::requireAuth(true);
        RequestGuard::requireCsrf(true);
    
        $order_id = $_POST['order_id'] ?? null;
        if (!$order_id) {
            echo json_encode(['success' => false, 'message' => 'Не указан ID заказа']); exit;
        }

        $ownedOrder = \R::findOne('order', 'id = ? AND user_id = ?', [(int)$order_id, $userId]);
        if (!$ownedOrder) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Заказ не найден']); exit;
        }
    
        try {
            // вызываем модель и передаём $_POST как есть
            $result = \app\models\Order::updateOrder($order_id, $_POST);
            echo json_encode($result); exit;
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении заказа', 'error' => $e->getMessage()]); exit;
        }
    }   

    public function bookmarksAction()
{
    if (!User::checkAuth() || empty($_SESSION['b2buser']['id'])) {
        redirect('/');
        exit;
    }
    $user_id = (int)$_SESSION['b2buser']['id'];

    // В начале экшена после checkAuth:
    if (isset($_POST['product_id'])) {
        RequestGuard::requirePost(true);
        RequestGuard::requireCsrf(true);
        $user_id    = (int)$_SESSION['b2buser']['id'];
        $product_id = (int)$_POST['product_id'];
        $mod_id     = (int)($_POST['mod_id'] ?? 0); // 0 = товар

        // ТОЛЬКО эта пара считается уникальной
        $exists_id = \R::getCell(
            'SELECT id FROM product_bookmarks WHERE user_id = ? AND product_id = ? AND mod_id = ? LIMIT 1',
            [$user_id, $product_id, $mod_id]
        );

        if ($exists_id) {
            \R::exec('DELETE FROM product_bookmarks WHERE id = ?', [(int)$exists_id]);
            if ($this->isAjax()) { echo json_encode(['success'=>true, 'action'=>'removed']); exit; }
        } else {
            \R::exec('INSERT INTO product_bookmarks (user_id, product_id, mod_id) VALUES (?, ?, ?)',
                [$user_id, $product_id, $mod_id]);
            if ($this->isAjax()) { echo json_encode(['success'=>true, 'action'=>'added']); exit; }
        }
    }

    // --- tip компании: 1=price, 2=opt_price, 3=spec_price ---
    $tip = 1;
    $comp_id = (int)($_SESSION['b2buser']['comp_id'] ?? 0);
    if ($comp_id) {
        $tipDb = \R::getCell('SELECT tip FROM company WHERE id = ? LIMIT 1', [$comp_id]);
        if ($tipDb) $tip = (int)$tipDb;
    }
    $clientField = ($tip === 2) ? 'opt_price' : (($tip === 3) ? 'spec_price' : 'price');

    // --- пагинация ---
    $page    = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perpage = \ishop\App::$app->getProperty('pagination') ?? 20;

    $total = \R::count('product_bookmarks', 'user_id = ?', [$user_id]);
    $pagination = new \ishop\libs\Pagination($page, $perpage, $total);
    $start = $pagination->getStart();

    // --- берём ВСЕ нужные поля из product и modification ---
    $sql = "
        SELECT
            pb.id              AS bookmark_id,
            pb.product_id,
            pb.mod_id,

            -- product
            p.alias            AS p_alias,
            p.img              AS p_img,
            p.unload_img       AS p_unload_img,
            p.category_id      AS p_category_id,

            p.name             AS p_name,
            p.article          AS p_article,
            p.quantity         AS p_qty,
            p.stock_status_id  AS p_status,

            p.price            AS p_price,
            p.opt_price        AS p_opt_price,
            p.spec_price       AS p_spec_price,

            -- modification (может быть NULL)
            m.id               AS m_id,
            m.name_modification            AS m_name,
            m.article          AS m_article,
            m.quantity         AS m_qty,
            

            m.price            AS m_price,
            m.opt_price        AS m_opt_price,
            m.spec_price       AS m_spec_price
        FROM product_bookmarks pb
        JOIN product p ON p.id = pb.product_id
        LEFT JOIN modification m ON m.id = pb.mod_id
        WHERE pb.user_id = ?
        ORDER BY pb.id DESC
        LIMIT ?, ?
    ";
    $rows = \R::getAll($sql, [$user_id, $start, $perpage]);

    // --- приоритет: если у товара есть остаток -> товар, иначе модификация (если есть) ---
    $bookmarks = [];
    foreach ($rows as $r) {
        $p_qty   = (int)($r['p_qty'] ?? 0);
        $has_mod = !empty($r['m_id']);

        $useProduct = ($p_qty > 0) || !$has_mod;

        if ($useProduct) {
            // показываем товар
            $name        = (string)$r['p_name'];
            $article     = (string)$r['p_article'];
            $qty         = (int)$r['p_qty'];
            $status      = (int)$r['p_status'];

            $price_rrs   = (float)($r['p_price'] ?? 0);                 // РРЦ всегда = price
            $client_price= (float)($r['p_' . $clientField] ?? 0);       // Ваша цена по tip

            // для дебага оставим «сырые» цены товара
            $raw_price   = (float)($r['p_price'] ?? 0);
            $raw_opt     = (float)($r['p_opt_price'] ?? 0);
            $raw_spec    = (float)($r['p_spec_price'] ?? 0);
        } else {
            // показываем модификацию
            $name        = (string)$r['m_name'];
            $article     = (string)$r['m_article'];
            $qty         = (int)$r['m_qty'];
            $status      = (int)$r['m_status'];

            $price_rrs   = (float)($r['m_price'] ?? 0);                 // РРЦ всегда = price
            $client_price= (float)($r['m_' . $clientField] ?? 0);       // Ваша цена по tip

            // для дебага оставим «сырые» цены модификации
            $raw_price   = (float)($r['m_price'] ?? 0);
            $raw_opt     = (float)($r['m_opt_price'] ?? 0);
            $raw_spec    = (float)($r['m_spec_price'] ?? 0);
        }

        $bookmarks[] = [
            'id'               => (int)$r['bookmark_id'],
            'product_id'       => (int)$r['product_id'],
            'mod_id'           => (int)($r['mod_id'] ?? 0),

            'alias'            => (string)$r['p_alias'],
            'img'              => (string)($r['p_img'] ?? ''),
            'unload_img'       => (string)($r['p_unload_img'] ?? ''),
            'category_id'      => (int)($r['p_category_id'] ?? 0),

            'name'             => $name,
            'article'          => $article,
            'quantity'         => $qty,
            'stock_status_id'  => $status,

            // ДВА ключа, которые использует шаблон:
            'price_rrs'        => $price_rrs,     // РРЦ (= price выбранного источника)
            'client_price'     => $client_price,  // Ваша цена (по tip из выбранного источника)

            // (опционально) сырые для дебага
            'dbg_price'        => $raw_price,
            'dbg_opt_price'    => $raw_opt,
            'dbg_spec_price'   => $raw_spec,
        ];
    }

    $this->setMeta('Закладки');
    $this->set(compact('bookmarks', 'pagination', 'tip'));
}
    	
	public function bookmarksDeleteAction() {
		RequestGuard::requirePost();
		RequestGuard::requireCsrf();
		if (!User::checkAuth()) { redirect('/'); exit; }
		$user_id = (int)$_SESSION['b2buser']['id'];
		$id = (int)($_POST['id'] ?? 0);
		if ($id) {
			\R::exec('DELETE FROM product_bookmarks WHERE id = ? AND user_id = ?', [$id, $user_id]);
		}
		redirect('/user/bookmarks');
	}
	
	public function pricelistAction()
{
    if (!User::checkAuth()) {
        redirect('/');
        exit;
    }

    $product = [];

    if (!empty($_POST)) {
        $format = $_POST['format'] ?? '';
        $category_id = (int)($_POST['category_id'] ?? 0);
        $brand_id = (int)($_POST['brand_id'] ?? 0);
        $article = trim($_POST['article'] ?? '');
        $actSelect = (string)($_POST['actSelect'] ?? '');

        if ($format == '1') { // PDF

            $select = "
                SELECT 
                    product.category_id,
                    product.article,
                    product.model,
                    product.name,
                    product.quantity,
                    product.alias,
                    product.opt_price,
                    product.price,
                    brand.name AS vendor
                FROM product
                JOIN brand ON product.brand_id = brand.id
            ";

            // 1 или 4 — определённая категория / категория + производитель
            if ($actSelect == '1' || $actSelect == '4') {
                $where = [];
                $params = [];

                if ($category_id == 1) {
                    $where[] = "product.category_id IN (9, 18, 19, 20, 21, 22, 23, 24)";
                } elseif ($category_id == 2) {
                    $where[] = "product.category_id = ?";
                    $params[] = 2;
                } elseif ($category_id == 25) {
                    $where[] = "product.category_id IN (31, 32, 33)";
                } elseif ($category_id == 4) {
                    $where[] = "product.category_id IN (26, 27, 28, 29, 30)";
                } elseif ($category_id == 3) {
                    $where[] = "product.category_id IN (10, 11, 12, 13, 14, 15, 16, 17)";
                } else {
                    $where[] = "product.category_id = ?";
                    $params[] = $category_id;
                }

                $where[] = "product.hide = ?";
                $params[] = 'show';

                if (!empty($brand_id)) {
                    $where[] = "product.brand_id = ?";
                    $params[] = $brand_id;
                }

                $sql = $select . " WHERE " . implode(' AND ', $where);
                $product = \R::getAll($sql, $params);
            }

            // 5 — все товары
            if ($actSelect == '5') {
                $product = \R::getAll(
                    $select . " WHERE product.hide = ?",
                    ['show']
                );
            }

            // 2 — по производителю
            if ($actSelect == '2' && !empty($brand_id)) {
                $product = \R::getAll(
                    $select . " WHERE product.brand_id = ? AND product.hide = ?",
                    [$brand_id, 'show']
                );
            }

            // 3 — по артикулу
            if ($actSelect == '3' && $article !== '') {
                $product = \R::getAll(
                    $select . " WHERE product.article = ? AND product.hide = ?",
                    [$article, 'show']
                );
            }
        }
    }

    $company = \R::findOne('company', 'user_id = ?', [$_SESSION['b2buser']['id'] ?? 0]);

    $this->setMeta('Прайс-лист');
    $this->set(compact('product', 'company'));
}
	
	public function pdfcatalogAction(){
		if (!User::checkAuth()) { redirect('/'); exit; }
		
		$this->setMeta('Каталог');
		
	}

    public function pdfscoreAction(){
        if (!User::checkAuth()) { redirect('/'); exit; }
		$order_id = (int)($_GET["order"] ?? 0);
		$userId = (int)$_SESSION['b2buser']['id'];
		$order = \R::findOne("order", "id = ? AND user_id = ?", [$order_id, $userId]);
		if (!$order) { http_response_code(404); exit('Заказ не найден'); }
        $order_products = \R::findAll('order_product', "order_id = ?", [$order_id]);
		$seller = \R::findOne('company', 'id = ?', [$order['seller']]);
		$user = \R::findOne('user', 'id = ?', [$order['user_id']]);
		$comp = \R::findOne('company', 'id = ?', [$user['comp_id']]);
        $this->set(compact('order', 'order_products', 'seller', 'user', 'comp'));
	}
	
	public function dogovorAction(){
		if (!User::checkAuth()) { redirect('/'); exit; }

        $company = \R::findOne("company", "user_id = ?", [$_SESSION['b2buser']['id']]);

		$this->setMeta('Договор');
        $this->set(compact('company'));
	}
	

	
	public function recoverAction(){
		if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
			RequestGuard::requirePost();
			RequestGuard::requireCsrf();
			$email = strtolower(trim((string)($_POST["email"] ?? '')));
			$user = \R::findOne('user', 'email = ?', [$email]);

			// Одинаковый ответ не позволяет проверить, зарегистрирован ли email.
			$_SESSION['success'] = 'Если такой email зарегистрирован, ссылка для восстановления будет отправлена.';
			if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !$user) {
				redirect();
			}

			// Не выпускаем новый токен, пока предыдущий ещё действует.
			if (\R::findOne('recover', 'email = ? AND expire > ?', [$email, time()])) {
				redirect();
			}
			\R::exec("DELETE FROM recover WHERE email = ?", [$email]);

			$expire = time() + 3600; // Токен действует 1 час
			$token = bin2hex(random_bytes(50)); // Генерируем новый токен

			$tokenHash = hash('sha256', $token);
			\R::exec("INSERT INTO `recover`(`hash`, `expire`, `email`) VALUES (?, ?, ?)", [$tokenHash, $expire, $email]);

			$reset_link = "https://b2b.its-center.ru/?token=" . $token;

			// Отправка письма через единый SMTP-сервис
			try {
				// Данные для письма
				$shop_name = App::$app->getProperty('shop_name');
				$admin_email = App::$app->getProperty('admin_email');
				$user_name = $user['name'];
				$tell_site = \ishop\App::options('option_telefon');
				
				// Генерируем HTML-шаблон письма
				ob_start();
				require APP . '/views/' . TEMPLATE . '/mail/mail_recover.php';
				$body = ob_get_clean();

				if (!MailService::sendHtml($email, "Восстановление пароля на сайте " . $shop_name, $body, $user_name)) {
					throw new \RuntimeException('SMTP is not configured or recipient is invalid');
				}
				MailService::sendHtml(
					$admin_email,
					"Запрос на восстановление пароля от $user_name",
					"Пользователь $user_name ($email) запросил восстановление пароля."
				);

			} catch (\Throwable $e) {
				error_log('Password recovery email failed: ' . get_class($e));
				\R::exec("DELETE FROM recover WHERE email = ?", [$email]);
			}

			redirect();
		}

		$this->setMeta('Восстановление пароля');
	}

	
	public function recoverPassAction(){
		RequestGuard::requirePost(true);
		header('Content-Type: application/json; charset=utf-8');
		if (!empty($_POST['password']) && !empty($_POST['token'])) {
			$token = trim((string)$_POST['token']);
			$password = (string)$_POST['password'];
			if (!preg_match('/^[a-f0-9]{100}$/', $token)) {
				echo json_encode(["success" => false, "error" => "Неверная или устаревшая ссылка"], JSON_UNESCAPED_UNICODE);
				exit;
			}

			// Второй вариант сохраняет совместимость с токенами, выданными до обновления.
			$record = \R::findOne('recover', '(hash = ? OR hash = ?) AND expire > ?', [hash('sha256', $token), $token, time()]);
			if (!$record) {
				echo json_encode(["success" => false, "error" => "Неверная или устаревшая ссылка"], JSON_UNESCAPED_UNICODE);
				exit;
			}
			$userModel = new User();
			if (!$userModel->validatePassword($password)) {
				$error = (string)($_SESSION['error'] ?? 'Пароль не соответствует требованиям безопасности.');
				unset($_SESSION['error']);
				echo json_encode(["success" => false, "error" => $error], JSON_UNESCAPED_UNICODE);
				exit;
			}
			$claimed = \R::exec('DELETE FROM recover WHERE id = ? AND expire > ?', [(int)$record->id, time()]);
			if ($claimed !== 1) {
				echo json_encode(["success" => false, "error" => "Неверная или устаревшая ссылка"], JSON_UNESCAPED_UNICODE);
				exit;
			}

			// Хешируем новый пароль
			$hashed_password = password_hash($password, PASSWORD_DEFAULT);
			\R::exec("UPDATE user SET password = ? WHERE email = ?", [$hashed_password, $record->email]);

			echo json_encode(["success" => true], JSON_UNESCAPED_UNICODE);
			exit;
		}

		echo json_encode(["success" => false, "error" => "Ошибка запроса"]);
		exit;
	}
	
	public function zvonokAction(){
		RequestGuard::requirePost(true);
		$userId = RequestGuard::requireAuth(true);
		RequestGuard::requireCsrf(true);
		header('Content-Type: application/json; charset=utf-8');
		$phone = trim((string)($_POST["phone"] ?? ''));
		$title = mb_substr(trim((string)($_POST["title"] ?? '')), 0, 255);
		$digits = preg_replace('/\D+/', '', $phone);
		if (!preg_match('/^79\d{9}$/', $digits)) {
			http_response_code(422);
			echo json_encode(['success' => false, 'error' => 'Укажите корректный мобильный номер.'], JSON_UNESCAPED_UNICODE);
			exit;
		}
		if ((int)($_SESSION['callback_sent_at'] ?? 0) > time() - 60) {
			http_response_code(429);
			echo json_encode(['success' => false, 'error' => 'Повторите запрос через минуту.'], JSON_UNESCAPED_UNICODE);
			exit;
		}

		$callback = \R::dispense('callback');
		$callback->user_id = $userId;
		$callback->topic = $title;
		$callback->phone = $phone;
		$callback->date_create = date('Y-m-d H:i:s');
		$callback->date_modified = '';
		$callback->user_modified = '';
		$callback->hide = '';
		if (\R::store($callback)) {
					$_SESSION['callback_sent_at'] = time();
					setcookie("request-mig", "1house", [
						'expires' => time() + 3600,
						'path' => '/',
						'secure' => !empty($_SERVER['HTTPS']),
						'httponly' => true,
						'samesite' => 'Lax',
					]);
					
					$namecomp = App::$app->getProperty('shop_name');
					$tell_site = \ishop\App::options('option_telefon');
					
					// Create a message
					ob_start();
					require APP . '/views/'.TEMPLATE.'/mail/mail_callback.php';
					$body = ob_get_clean();


					try {
						MailService::sendHtml(
							App::$app->getProperty('admin_email'),
							"Заказ обратного звонка на сайте " . App::$app->getProperty('shop_name'),
							$body
						);
					} catch (\Throwable $e) {
						error_log('Callback email failed: ' . get_class($e));
					}
					
					$_SESSION['success'] = 'Спасибо за заказ обратного звонка. Наш менеджер обязательно Вам позвонит по указаному номеру который вы указали. Ожидайте звонка в рабочее время с ПН-ПТ 9:00 до 17:00 по МСК.';
					echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
					exit;
		}
		http_response_code(500);
		echo json_encode(['success' => false, 'error' => 'Не удалось сохранить запрос.'], JSON_UNESCAPED_UNICODE);
		exit;
	}

    public function downloadMarksAction() {
        ini_set('display_errors', 0);
        error_reporting(0);
    
        $order_id = (int)($_GET['id'] ?? 0);
        if (!$order_id) exit('Нет заказа');

        $user_id = RequestGuard::requireAuth();
    
        $order = \R::findOne('order', 'id = ? AND user_id = ?', [$order_id, $user_id]);
        if (!$order) exit('Заказ не найден');
    
        $marks = \R::getAll("
            SELECT om.*, op.name 
            FROM order_marks om
            JOIN `order` o ON o.guid_1c = om.order_id
            LEFT JOIN order_product op ON o.id = op.order_id AND om.item_code = op.article
            WHERE o.id = ?
            ORDER BY om.id ASC
        ", [$order_id]);
    
        if (empty($marks)) exit('Марки не найдены');
    
        $qr_images = [];
        $qrWriter = new PngWriter();
        foreach ($marks as $i => $m) {
            $markBase64 = $m['mark_base64'] ?? '';
            if ($markBase64 === '') {
                $qr_images[$i] = '';
                continue;
            }
            try {
                $qrCode = new QrCode(data: $markBase64, size: 180, margin: 4);
                $qr_images[$i] = 'data:image/png;base64,' . base64_encode($qrWriter->write($qrCode)->getString());
            } catch (\Throwable $e) {
                error_log('Local QR generation failed: ' . get_class($e));
                $qr_images[$i] = '';
            }
        }
    
        $GLOBALS['order'] = $order;
        $GLOBALS['marks'] = $marks;
        $GLOBALS['qr_images'] = $qr_images;
    
        ob_start();
        require APP . "/views/pdf/marks_pdf.php";
        $html = ob_get_clean();
    
        $dompdf = new \Dompdf\Dompdf([
            'chroot' => [ROOT . '/public'], // ✅ доступ к /public/*
        ]);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
    
        ob_clean();
        $dompdf->stream("marki-order-$order_id.pdf", ["Attachment" => true]);
        exit;
    }

    public function proxyPdfAction() {
        $userId = RequestGuard::requireAuth();
        $type = (string)($_GET['type'] ?? '');
        $guid = trim((string)($_GET['guid'] ?? ''));
    
        if (!$type || !$guid) {
            echo 'Ошибка параметров';
            exit;
        }
    
        $types = [
            'upd'   => 'print-upd',
            'order' => 'print-order',
        ];
    
        if (!isset($types[$type])) {
            echo 'Неверный тип документа';
            exit;
        }

        if (!preg_match('/^[a-f0-9-]{8,64}$/i', $guid)) {
            http_response_code(400);
            exit('Неверный идентификатор документа');
        }

        $ownedOrder = \R::findOne('order', 'guid_1c = ? AND user_id = ?', [$guid, $userId]);
        if (!$ownedOrder) {
            http_response_code(404);
            exit('Документ не найден');
        }
    
        $method = "order/{$guid}/{$types[$type]}";
    
        // ✅ Используем sendRawRequest (а не sendRequest!)
        $response = \app\helpers\ApiClient::sendRawRequest('api_orders.php', $method, 'GET');
    
        $httpCode = $response['http_code'] ?? 0;
        $rawPdf   = $response['body'] ?? null;
    
        if ($httpCode !== 200 || !is_string($rawPdf) || !str_starts_with($rawPdf, '%PDF-')) {
            echo 'Не удалось получить PDF';
            exit;
        }
    
        // 📄 Отдаём PDF-файл во вкладку браузера
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="document.pdf"');
        header('X-Content-Type-Options: nosniff');
        header('Content-Length: ' . strlen($rawPdf));
        echo $rawPdf;
        exit;
    }
   
}
