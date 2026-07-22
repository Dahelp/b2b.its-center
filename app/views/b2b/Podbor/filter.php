<?php
$curr = \ishop\App::$app->getProperty('currency');

$isServicesPage = !empty($main_category_id) && (int)$main_category_id === 37;
?>

<?php if (!empty($products)): ?>
    <div class="product-one">
        <div class="casters-block table-responsive">
            <table class="tbl_podbor">
                <thead>
                    <tr class="tab-list footable-header">
                        <th>Фото</th>
                        <th>Артикул</th>
                        <th>Наименование</th>
                        <th>Наличие (шт.)</th>
                        <th class="c_price">РРЦ</th>
                        <th class="c_price">Ваша цена</th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($products as $product): ?>
                        <?php
                        $is_mod = !empty($product->is_mod);
                        $cart_key = $is_mod ? "{$product->id}-{$product->mod_id}" : $product->id;
                        $in_cart = isset($_SESSION['cart'][$cart_key]);
                        $q = $in_cart ? (int)$_SESSION['cart'][$cart_key]['qty'] : 1;

                        $productId = (int)($product->id ?? 0);
                        $modId = $is_mod ? (int)($product->mod_id ?? 0) : 0;

                        $name = $product->name ?? '';
                        $article = $product->article ?? '';

                        $quantity = isset($product->quantity) ? (int)$product->quantity : 0;
                        if ($isServicesPage && $quantity <= 0) {
                            $quantity = 999;
                        }

                        $price = isset($product->price) ? (float)$product->price : 0;
                        $rrsPrice = isset($product->rrs_price) ? (float)$product->rrs_price : $price;

                        $stockStatusId = isset($product->stock_status_id) ? (int)$product->stock_status_id : 1;

                        $img = '';
                        if (!empty($product->unload_img)) {
                            $img = 'https://its-center.ru/images/product/unload/' . $product->unload_img;
                        } elseif (!empty($product->img)) {
                            $img = 'https://its-center.ru/images/product/mini/' . $product->img;
                        } else {
                            $img = 'https://its-center.ru/images/no_image.jpg';
                        }

                        $uid = (int)($_SESSION['b2buser']['id'] ?? 0);
                        $is_saved = 0;

                        if ($uid && !$isServicesPage) {
                            $is_saved = \R::count(
                                'product_bookmarks',
                                'product_id = ? AND mod_id = ? AND user_id = ?',
                                [$productId, $modId, $uid]
                            );
                        }

                        $btnId = $modId ? "wishlist-{$productId}-{$modId}" : "wishlist-{$productId}";
                        ?>

                        <tr class="product type-product">
                            <td>
                                <img src="<?= $img; ?>" alt="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
                            </td>

                            <td><?= htmlspecialchars($article, ENT_QUOTES, 'UTF-8'); ?></td>

                            <td><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></td>

                            <td>
                                <?php if ($isServicesPage): ?>
                                    Услуга
                                <?php else: ?>
                                    <?= $quantity; ?>

                                    <?php if (!empty($product->wait_date)): ?>
                                        <br>
                                        Приход <?= (int)($product->wait ?? 0); ?> шт. <?= $product->wait_date; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>

                            <td class="c_price">
                                <?= $rrsPrice * $curr['value']; ?> <?= $curr['symbol_right']; ?>
                            </td>

                            <td class="c_price">
                                <?= $price * $curr['value']; ?> <?= $curr['symbol_right']; ?>
                            </td>

                            <td class="btn_price btn-korz">
                                <?php if ($quantity > 0): ?>
                                    <div class="quantity-block my_quant-<?= $cart_key; ?>" style="display: <?= $in_cart ? 'inline-flex' : 'none'; ?>;">
                                        <button type="button"
                                                class="btn btn-outline-secondary quantity-arrow-minus"
                                                data-id="<?= $productId; ?>"
                                                <?= $is_mod ? 'data-mod="' . $modId . '"' : ''; ?>>-</button>

                                        <span class="qty-item">
                                            <input type="text"
                                                   class="form-control form-control-sm qty-input qty-item-<?= $cart_key; ?>"
                                                   data-id="<?= $productId; ?>"
                                                   <?= $is_mod ? 'data-mod="' . $modId . '"' : ''; ?>
                                                   value="<?= $q; ?>"
                                                   min="1"
                                                   max="<?= $quantity; ?>">
                                        </span>

                                        <button type="button"
                                                class="btn btn-outline-secondary quantity-arrow-plus"
                                                data-id="<?= $productId; ?>"
                                                <?= $is_mod ? 'data-mod="' . $modId . '"' : ''; ?>>+</button>
                                    </div>

                                    <div class="btn btn-green-back korzina-<?= $cart_key; ?>" style="display: <?= $in_cart ? 'none' : 'inline-block'; ?>;">
                                        <a href="#"
                                           class="add-to-cart-link"
                                           data-id="<?= $productId; ?>"
                                           data-max="<?= $quantity; ?>"
                                           <?= $is_mod ? 'data-mod="' . $modId . '"' : ''; ?>>
                                            В корзину
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <?php if ($stockStatusId === 0): ?>
                                        <div class="text-dark">Нет в наличии</div>
                                    <?php elseif ($stockStatusId === 2): ?>
                                        <div class="text-dark">Под заказ</div>
                                    <?php elseif ($stockStatusId === 3): ?>
                                        <div class="text-dark">Ожидается поступление</div>
                                    <?php else: ?>
                                        <div class="badge bg-secondary">Нет данных</div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>

                            <td class="btn_price">
                                <div class="d-inline-flex align-items-center gap-2">
                                    <?php if ($uid && !$isServicesPage): ?>
                                        <button id="<?= $btnId; ?>"
                                                class="btn btn-icon btn-circle btn-sm <?= $is_saved ? 'btn-wishlist2 btn-soft-danger' : 'btn-wishlist btn-soft-secondary'; ?>"
                                                type="button"
                                                data-id="<?= $productId; ?>"
                                                data-mod="<?= $modId; ?>">
                                            <i class="far fa-heart"></i>
                                        </button>
                                    <?php elseif (!$isServicesPage): ?>
                                        <button class="btn btn-soft-secondary btn-icon btn-circle btn-sm"
                                                type="button"
                                                disabled
                                                title="Войдите, чтобы добавлять в избранное">
                                            <i class="far fa-heart"></i>
                                        </button>
                                    <?php endif; ?>

                                    <a href="#"
                                       class="btn btn-soft-info btn-icon btn-circle btn-sm view-product"
                                       data-bs-toggle="modal"
                                       data-bs-target="#productModal"
                                       data-id="<?= $productId; ?>"
                                       title="Просмотр товара">
                                        <i class="far fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="text-center">
            <p>(<?= count($products); ?> товара(ов) из <?= (int)$total; ?>)</p>

            <?php if (!empty($pagination) && $pagination->countPages > 1): ?>
                <?= $pagination; ?>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <h3 class="text-center">Товаров не найдено...</h3>
<?php endif; ?>