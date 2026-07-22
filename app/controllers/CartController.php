<?php

namespace app\controllers;

use app\models\Cart;
use app\models\Order;
use ishop\App;
use app\helpers\SessionHelper;

class CartController extends AppController {

    public function addAction() {
    $id = !empty($_GET['id']) ? (int)$_GET['id'] : null;
    $qty = isset($_GET['qty']) ? (int)$_GET['qty'] : null;
    $mod_id = !empty($_GET['mod']) ? (int)$_GET['mod'] : null;
    $max = !empty($_GET['max']) ? (int)$_GET['max'] : null;

    file_put_contents(
    ROOT . '/storage/logs/cart_debug.log',
    "[" . date('Y-m-d H:i:s') . "] addAction: id=" . ($_GET['id'] ?? '-') . " mod=" . ($_GET['mod'] ?? '-') . " qty=" . ($_GET['qty'] ?? '-') . "\n",
    FILE_APPEND
);

    $mod = null;
    $cart = new Cart();

    if ($id) {
        $product = \R::getRow("SELECT * FROM product WHERE id = ? LIMIT 1", [$id]);
        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Товар не найден']);
            exit;
        }

        // Тип компании
        $b2buser_id = $_SESSION['b2buser']['id'] ?? 0;
        $tip = \R::getCell("
            SELECT company.tip 
            FROM company 
            JOIN user ON user.comp_id = company.id 
            WHERE user.id = ?
        ", [$b2buser_id]);

        // Модификация
        if ($mod_id) {
            $mod = \R::getRow("SELECT * FROM modification WHERE id = ? AND product_id = ? LIMIT 1", [$mod_id, $id]);

            if ($mod) {
                // Подмена значений
                $product['article'] = $mod['article'];
                $product['name'] = $mod['name_modification'];
                $product['quantity'] = $mod['quantity'];
                $product['unit'] = $mod['unit'];
                $product['spec_price'] = $mod['spec_price'];
                $product['opt_price'] = $mod['opt_price'];
                $product['price'] = $mod['price'];
                $product['mod_id'] = $mod_id; // ✅ обязательно
            }
        }

        // Запрос в API
        $api_article = $mod ? $mod['article'] : $product['article'];
        $apiData = \app\services\Api1C::getProductData($api_article);

        $final_price = 0;

        if ($apiData) {
            if ($tip == 1) {
                $final_price = $apiData['price_rozn'] ?? $product['price'];
            } elseif ($tip == 2) {
                $final_price = $apiData['price_opt'] ?? $product['opt_price'];
            } elseif ($tip == 3) {
                $final_price = $apiData['price_spec'] ?? $product['spec_price'];
            }

            $product['quantity'] = $apiData['quantity'] ?? $product['quantity'];
            $product['wait'] = $apiData['wait'] ?? '';
            $product['wait_date'] = $apiData['wait_date'] ?? '';

            if (!empty($apiData['name'])) {
                $product['name'] = $apiData['name'];
            }
        } else {
            // Fallback без API
            if ($tip == 1) {
                $final_price = $product['price'];
            } elseif ($tip == 2) {
                $final_price = $product['opt_price'];
            } elseif ($tip == 3) {
                $final_price = $product['spec_price'];
            }
        }

        // ✅ Итоговая цена
        $product['final_price'] = $final_price;

        // ✅ mod_price, если есть модификация
        if ($mod) {
            $product['mod_price'] = $final_price;
        }

        // 🧹 Удаление мусорных ключей
        foreach ($_SESSION['cart'] ?? [] as $key => $item) {
            if (!is_numeric($key) && !preg_match('/^\d+(-\d+)?$/', (string)$key)) {
                unset($_SESSION['cart'][$key]);
            }
        }

        // ✅ Обновление корзины
        $cart->updateQty((object)$product, $qty, $max, $mod ? (object)$mod : null);
    }

    SessionHelper::cleanCart();

    // ✅ Ответ
    if ($this->isAjax()) {
        $cartData = $_SESSION['cart'] ?? [];
        $key = $mod ? "{$product['id']}-{$mod['id']}" : $product['id'];
        $productQty = $cartData[$key]['qty'] ?? 0;

        echo json_encode([
            'qty' => $_SESSION['cart.qty'] ?? 0,
            'product_id' => $product['id'],
            'product_qty' => $productQty,
            'sum' => $_SESSION['cart.sum'] ?? 0,
            'weight' => $_SESSION['cart.weight'] ?? 0,
            'volume' => $_SESSION['cart.volume'] ?? 0,
        ]);
        exit;
    }

    redirect();
}


    public function statusAction() {
        $this->layout = false;
        header('Content-Type: application/json');

        SessionHelper::cleanCart();

        $cart = $_SESSION['cart'] ?? [];

        $totalQty = 0;
        $totalSum = 0;
        $totalWeight = 0;
        $totalVolume = 0;

        foreach ($cart as $item) {
            $qty = (int)($item['qty'] ?? 0);
            $price = (float)($item['final_price'] ?? 0);
            $weight = (float)($item['weight'] ?? 0);
            $volume = (float)($item['volume'] ?? 0);

            $totalQty += $qty;
            $totalSum += $qty * $price;
            $totalWeight += $qty * $weight;
            $totalVolume += $qty * $volume;
        }

        $_SESSION['cart.qty'] = $totalQty;
        $_SESSION['cart.sum'] = $totalSum;
        $_SESSION['cart.weight'] = $totalWeight;
        $_SESSION['cart.volume'] = $totalVolume;

        if (ob_get_length()) {
            ob_clean();
        }

        echo json_encode([
            'cart' => $cart,
            'totalQty' => $totalQty,
            'totalSum' => $_SESSION['cart.sum'] ?? 0,
            'totalWeight' => $_SESSION['cart.weight'] ?? 0,
            'totalVolume' => $_SESSION['cart.volume'] ?? 0,
            'currency' => $_SESSION['cart.currency'] ?? ['symbol_left' => '', 'symbol_right' => ''],
        ]);
        exit;
    }

    public function clearAction() {
        $cart = new Cart();
        $cart->clearCart();

        if ($this->isAjax()) {
            echo json_encode([
                'qty' => 0,
                'sum' => 0,
                'weight' => 0,
                'volume' => 0
            ]);
            exit;
        }

        redirect();
    }

    public function viewAction() {
        SessionHelper::cleanCart();

        $path_controller = !empty($this->route["controller"]) ? '/' . mb_strtolower($this->route["controller"]) : '';
        $path_alias = !empty($this->route["alias"]) ? '/' . $this->route["alias"] : '';

        $this->setMeta(
            'Корзина',
            'Корзина',
            '',
            App::$app->getProperty('shop_name'),
            PATH . '/images/' . App::$app->getProperty('og_logo'),
            PATH . $path_controller . $path_alias
        );

        $cart = $_SESSION['cart'] ?? [];
        $cartQty = $_SESSION['cart.qty'] ?? 0;
        $cartSum = $_SESSION['cart.sum'] ?? 0;
        $cartCurrency = $_SESSION['cart.currency'] ?? ['symbol_left' => '', 'symbol_right' => '', 'value' => 1];
        $cartWeight = $_SESSION['cart.weight'] ?? 0;
        $cartVolume = $_SESSION['cart.volume'] ?? 0;

        if (empty($_SESSION['form_data']) && !empty($_SESSION['b2buser'])) {
            $_SESSION['form_data'] = [
                'name' => $_SESSION['b2buser']['name'] ?? '',
                'telefon' => $_SESSION['b2buser']['telefon'] ?? '',
                'email' => $_SESSION['b2buser']['email'] ?? '',
            ];
        }

        $formData = $_SESSION['form_data'] ?? [];

        $b2buser = $_SESSION['b2buser'] ?? null;
        $company = $b2buser ? \R::getAll("SELECT * FROM company WHERE user_id = '".$b2buser['id']."'") : null;

        $dostavka = \R::getAll("SELECT * FROM dostavka WHERE id IN (1, 2) AND hide='show'");
        $transport = \R::getAll("SELECT * FROM transport_company WHERE hide='show'");
        $branch = \R::getAll("SELECT * FROM branch_office WHERE hide='show'");
        $cities = \R::getAll("SELECT id, city_name FROM cities ORDER BY city_name");

        $this->set(compact(
            'cart', 'cartQty', 'cartSum', 'cartCurrency', 'cartWeight', 'cartVolume',
            'formData', 'dostavka', 'transport', 'branch', 'company', 'b2buser', 'cities'
        ));
    }

    public function checkoutAction() {
    $this->view = false;

    if (!empty($_POST)) {

        if (empty($_SESSION['cart'])) {
            $_SESSION['error'] = 'Корзина пуста. Заказ не может быть оформлен.';
            redirect(); exit;
        }

        $b2buser = $_SESSION['b2buser'] ?? null;
        if (empty($b2buser['id'])) {
            $_SESSION['error'] = 'Ошибка: пользователь не авторизован.';
            redirect(); exit;
        }

        $usok = \R::findOne('user', 'id = ?', [$b2buser['id']]);
        if (!$usok) {
            $_SESSION['error'] = 'Ошибка: пользователь не найден.';
            redirect(); exit;
        }

        try {
            // --- Компания
            $comp_id = $_POST['comp_id'] ?? null;
            if (!$comp_id) throw new \Exception('Компания не выбрана');

            $comp = \R::findOne('company', 'id = ?', [$comp_id]);
            if (!$comp) throw new \Exception('Компания не найдена');

            // --- Параметры доставки
            $dostavka_id  = (int)($_POST['dostavka_id'] ?? 0);
            $transport_id = (int)($_POST['transport_id'] ?? 0);
            $branch_id    = (int)($_POST['branch_id'] ?? 0);
            $address      = trim($_POST['address'] ?? '');
            $note         = $_POST['note'] ?? '';

            $dostavka = \R::findOne('dostavka', 'id = ?', [$dostavka_id]);

            // --- Город: либо id из справочника, либо свободный текст
            $city_id   = (int)($_POST['city_id'] ?? 0);
            $city_name = trim($_POST['city_name'] ?? '');

            // Санитизация своего города (если передан)
            if ($city_name !== '') {
                $city_name = mb_substr($city_name, 0, 100);
                // Разрешаем буквы, пробел, дефис, точку, апостроф
                $city_name = preg_replace("/[^\\p{L}\\s\\-\\.\\']+/u", '', $city_name);
                $city_name = preg_replace('/\\s+/u', ' ', $city_name);
                $city_name = trim($city_name);
            }

            // Если выбрана "Транспортная компания" — обязателен город (id или name)
            $isTransportCompany = false;
            if ($dostavka) {
                // Проверяем по названию и по id на всякий случай
                $isTransportCompany = ($dostavka->name === 'Транспортная компания') || ($dostavka_id === 2);
            }
            if ($isTransportCompany) {
                if ($city_id <= 0 && $city_name === '') {
                    $_SESSION['error'] = 'Укажите город для доставки ТК (выберите из списка или введите свой и нажмите Enter).';
                    redirect('/cart/checkout'); exit;
                }
            }

            // Валидация city_id, если он пришёл и доставка не "Самовывоз"
            if ($city_id > 0 && $dostavka && $dostavka->name !== 'Самовывоз') {
                $cityBean = \R::load('cities', $city_id);
                if (!$cityBean || !$cityBean->id) {
                    throw new \Exception("Город не найден. Проверьте список городов.");
                }
            }

            // --- Создание заказа
            $order = \R::dispense('order');
            $order->user_id    = $b2buser['id'];
            $order->admin_id   = $usok['admin_id'] ?? 0;
            $order->comp_id    = $comp_id;
            $order->dostavka_id  = $dostavka_id ?: null;
            $order->transport_id = $transport_id ?: null;
            $order->branch_id    = $branch_id ?: null;
            $order->address      = $address ?: null;
            $order->note         = $note ?? '';
            $order->status       = 1;
            $order->currency     = $_SESSION['cart.currency']['code'] ?? 'RUB';
            $order->end_buyer    = !empty($_POST['end_buyer']) ? 1 : 0;

            // --- Логика города: сохраняем либо city_id, либо city_text
            if ($city_id > 0) {
                $order->city_id   = $city_id;
                $order->city_text = null;
            } elseif ($city_name !== '') {
                // Требуется колонка order.city_text (VARCHAR(100))
                $order->city_id   = null;
                $order->city_text = $city_name;

                // Опционально: предложение в city_suggest (без влияния на оформление)
                try {
                    $normalized = mb_strtolower($city_name);
                    $normalized = preg_replace('/\\s+/u', ' ', $normalized);
                    $exists = \R::getCell("SELECT id FROM city_suggest WHERE normalized = ? LIMIT 1", [$normalized]);
                    if (!$exists) {
                        $sug = \R::dispense('city_suggest');
                        $sug->city_name  = $city_name;
                        $sug->normalized = $normalized;
                        $sug->status     = 'pending';
                        $sug->created_at = date('Y-m-d H:i:s');
                        \R::store($sug);
                    }
                } catch (\Throwable $e) {
                    // ничего, оформление не должно падать из-за этого
                }
            } else {
                // Ни id, ни name не переданы — значит город не обязателен (например, Самовывоз)
                $order->city_id   = null;
                $order->city_text = null;
            }

            // --- Продавец/НДС
            if ($comp['nds'] == 1) $order->seller = 3;
            if ($comp['nds'] == 2) $order->seller = 1;
            $dogovor = ($comp['dogovor'] ?? 0) == 1 ? 'Договор' : 'Счет-договор';

            $order_id = \R::store($order);

            file_put_contents(ROOT . '/storage/logs/checkout_debug.log', "✅ Заказ создан, ID = $order_id\n", FILE_APPEND);

            // --- Позиции
            Order::saveOrderProduct($order_id);

            file_put_contents(ROOT . '/storage/logs/checkout_debug.log', "✅ saveOrderProduct вызван\n", FILE_APPEND);

            // --- Город для письма
            $city_for_mail = '';
            if (!empty($order->city_id)) {
                $cityBean = \R::load('cities', (int)$order->city_id);
                $city_for_mail = $cityBean && $cityBean->id ? ($cityBean->city_name ?? '') : '';
            } elseif (!empty($order->city_text)) {
                $city_for_mail = $order->city_text;
            }           

            // --- Отправка в 1С
            $result1C = Order::sendTo1C($order_id);

            if (!empty($result1C['number'])) {

                // --- Письмо
                Order::mailOrder(
                    $order_id,
                    $usok['email'] ?? '',
                    $usok['name'] ?? '',
                    $usok['telefon'] ?? '',
                    $usok['admin_id'] ?? 0,
                    $note,
                    date('Y-m-d H:i:s'),
                    $dostavka->name ?? '',
                    \R::findOne('branch_office', 'branch_id = ?', [$branch_id ?: 0])->branch_name ?? '',
                    $address,
                    \R::load('transport_company', $transport_id ?: 0)->name ?? '',
                    $city_for_mail,
                    ($comp['nds'] == 1) ? 'c НДС' : 'без НДС',
                    $comp['comp_name'] ?? '',
                    $comp['nds'] ?? '',
                    $dogovor,
                    (!empty($_POST['end_buyer']) ? 'Да' : '')
                );

                $_SESSION['success'] = "Заказ №{$result1C['number']} успешно оформлен!";
            }

            redirect('/user/orders'); exit;

        } catch (\Throwable $e) {
            $log = date('Y-m-d H:i:s') . " | Ошибка оформления заказа:\n" .
                   $e->getMessage() . "\n" .
                   var_export($_POST, true) . "\n\n";

            file_put_contents(ROOT . '/storage/logs/order_store_error.log', $log, FILE_APPEND);

            $_SESSION['error'] = 'Ошибка при оформлении заказа: ' . $e->getMessage();
            redirect('/cart/checkout'); exit;
        }
    }

    redirect(); exit;
}
  

}