<?php

namespace app\models;

use ishop\App;

class Cart extends AppModel {

    public $rules = [
        'required' => [         
            ['comp_id'],   
            ['name'],
            ['email'],
			['telefon'],
        ],
        'email' => [
            ['email'],
        ],
		'telefon' => [
            ['telefon'],
        ]
    ];

    public function updateQty($product, $qty = 1, $max = null, $mod = null) {
    if (!isset($_SESSION['cart.currency'])) {
        $_SESSION['cart.currency'] = \ishop\App::$app->getProperty('currency');
    }

    $ID = $mod ? "{$product->id}-{$mod->id}" : $product->id;
    $name = $mod ? $product->name : $product->name;
    $article = $mod ? $mod->article : $product->article;
    $unit = $mod ? $mod->unit : $product->unit;
    $weight = $product->weight;
    $volume = $product->volume;

    // ✅ Используем правильную цену — из final_price
    $final_price = $product->final_price ?? 0;
    $price_converted = $final_price * $_SESSION['cart.currency']['value'];

    if ((int)$qty === 0) {
        unset($_SESSION['cart'][$ID]);
        $this->recountCart();
    } else {
        $_SESSION['cart'][$ID] = [
            'id' => $product->id,
            'mod_id' => $mod ? $mod->id : 0, // ✅ Добавлено!
            'external' => 0,
            'qty' => (int)$qty,
            'unit' => $unit,
            'weight' => (float)$weight,
            'volume' => (float)$volume,
            'max' => $max,
            'name' => $name,
            'article' => $article,
            'alias' => $product->alias,
            'final_price' => $price_converted,
            'unload_img' => $product->unload_img,
        ];
        $this->recountCart();
    }
}



    protected function recountCart() {
        $qty = $sum = $weight = $volume = 0;

        if (!empty($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $key => $item) {
                if ($key === '' || empty($item['qty'])) {
                    unset($_SESSION['cart'][$key]);
                    continue;
                }

                $itemQty = (int)$item['qty'];
                $qty += $itemQty;
                $sum += $itemQty * (float)$item['final_price'];
                $weight += $itemQty * (float)$item['weight'];
                $volume += $itemQty * (float)$item['volume'];
            }
        }

        $_SESSION['cart.qty'] = $qty;
        $_SESSION['cart.sum'] = $sum;
        $_SESSION['cart.weight'] = round($weight, 2);
		$_SESSION['cart.volume'] = round($volume, 2);
    }

    public function clearCart(): void {
        unset($_SESSION['cart'], $_SESSION['cart.qty'], $_SESSION['cart.sum'], $_SESSION['cart.weight'], $_SESSION['cart.volume']);
    }
}
