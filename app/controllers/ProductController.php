<?php

namespace app\controllers;

use ishop\App;
use app\services\Api1C;

class ProductController extends AppController {
    
    public function modalAction() {
        $this->layout = false;

        $id = $_GET['id'] ?? null;		
        if (!$id) {
            echo '<div class="text-danger text-center">Товар не найден</div>';
            return;
        }

        // Используем getRow для избежания ошибки id
        $product = \R::getRow("SELECT * FROM product WHERE id = ? LIMIT 1", [$id]);
        if (!$product) {
            echo '<div class="text-danger text-center">Товар не найден</div>';
            return;
        }

        // Получаем группы атрибутов (по group_id)
        $attribute_group = \R::getAll("
            SELECT * FROM attribute 
            JOIN product_attribute ON product_attribute.attribute_group_id = attribute.id 
            WHERE product_attribute.product_id = ? 
            GROUP BY product_attribute.attribute_group_id
        ", [$id]);

        // Получаем тип компании текущего пользователя
        $user_id = $_SESSION['b2buser']['id'] ?? 0;
        $tip = \R::getCell("SELECT company.tip FROM company JOIN user ON user.comp_id = company.id WHERE user.id = ?", [$user_id]);

        // Получаем данные по API
        $apiData = Api1C::getProductData($product['article']);		
		if ($apiData) {
            // Выбор нужной цены в зависимости от tip
            if ($tip == 1) {
                $product['price'] = $apiData['price_rozn'] ?? $product['opt_price'];
            } elseif ($tip == 2) {
                $product['price'] = $apiData['price_opt'] ?? $product['opt_price'];
            } elseif ($tip == 3) {
                $product['price'] = $apiData['price_spec'] ?? $product['opt_price'];
            } else {
                $product['price'] = $product['opt_price']; // fallback
            }

            $product['quantity'] = $apiData['quantity'] ?? $product['quantity'];
            $product['wait'] = $apiData['wait'] ?? $product['wait'];
            $rawDate = $apiData['wait_date'] ?? $product['wait_date'];
            $product['wait_date'] = $rawDate ? App::getFormattedDeliveryDate($rawDate) : null;
        } else {
            $product['price'] = $product['opt_price']; // если нет API — цена по умолчанию
        }
        
        if ($this->isAjax()) {
            $this->loadView('modal', compact('product', 'attribute_group'));
        }
    }

}
