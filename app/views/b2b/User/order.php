<?php
use ishop\App;

/** @var array $order */
/** @var \RedBeanPHP\OODBBean $order_info */
/** @var \RedBeanPHP\OODBBean $status */
/** @var bool $is_editable */
/** @var array $dostavka */
/** @var array $transport_companies */
/** @var array $cities */

$today = date('Y-m-d');
$order_date = date('Y-m-d', strtotime($order_info->date ?? ''));
$is_editable = ($order_info->status == 1) && ($order_date == $today);

?>

<section class="py-5">
    <div class="container">
        <div class="d-flex align-items-start cab-inner">
            <div class="aiz-user-panel cart-block" style="width:100%">
                <div class="card">
                <div class="card-header register-top heading pb-4 aiz-table-order">
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <h5 class="mb-2 mb-md-0 h6 flex-grow-1">
            Заказ № <?= $order_info->inv ?? 'Б/Н' ?> от <?= App::contdate($order_info->date ?? '') ?>
        </h5>

        <?php
        $status_payment_name = \R::getCell("SELECT text_payment FROM order_status_payment WHERE id = ?", [$order_info->status_payment_id ?? 0]) ?? 'Неизвестно';
        $status_shipment_name = \R::getCell("SELECT text_shipment FROM order_status_shipment WHERE id = ?", [$order_info->status_shipment_id ?? 0]) ?? 'Неизвестно';
        
        $status_name = $status->status_name ?? 'Неизвестно';

        switch ($status_name) {
            case 'Новый':
                $badge_class = 'badge-info';
                break;
            case 'Обрабатывается':
                $badge_class = 'badge-success';
                break;
            case 'Отменён':
                $badge_class = 'badge-danger';
                break;
            default:
                $badge_class = 'badge-secondary';
                break;
        }

        switch ($status_payment_name) {
            case 'Оплачен':
                $badge_class_payment = 'badge-success';
                break;
            case 'Не оплачен':
                $badge_class_payment = 'badge-secondary';
                break;
            case 'Частично оплачен':
                $badge_class_payment = 'badge-info';
                break;
            case 'Отменён':
                $badge_class_payment = 'badge-danger';
                break;
            case 'Закрыт':
                $badge_class_payment = 'badge-danger';
                break;
            case 'Переплата':
                $badge_class_payment = 'badge-warning';
                break;
            default:
                $badge_class_payment = 'badge-secondary';
                break;
        }

        switch ($status_shipment_name) {
            case 'Отгружен':
                $badge_class_shipment = 'badge-success';
                break;
            case 'Не отгружен':
                $badge_class_shipment = 'badge-secondary';
                break;
            case 'Частично отгружен':
                $badge_class_shipment = 'badge-warning';
                break;
            case 'Ожидает отгрузки':
                $badge_class_shipment = 'badge-info';
                break;
            case 'Закрыт':
                $badge_class_shipment = 'badge-danger';
                break;
            default:
                $badge_class_shipment = 'badge-secondary';
                break;
        }

        ?>

        <div class="d-flex flex-wrap gap-2 justify-content-end badge-inner">
            <span class="badge badge-inline <?= $badge_class ?>">Статус: <?= $status_name ?></span>
            <span class="badge badge-inline <?= $badge_class_payment ?>">Оплата: <?= $status_payment_name ?></span>
            <span class="badge badge-inline <?= $badge_class_shipment ?>">Отгрузка: <?= $status_shipment_name ?></span>
        </div>
    </div>
</div>



                    <div class="card-body">
                        <div class="casters-block table-responsive">
                            <table class="table table-hover table-striped">
                                <thead>
                                <tr>
                                    <th>Фото</th>
                                    <th>Артикул</th>
                                    <th>Наименование</th>
                                    <?php if ($is_editable): ?>
                                        <th>Наличие</th>
                                    <?php endif; ?> 
                                    <th>Кол-во</th>
                                    <th>Ваша цена</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody id="order-products-body">
                                <?php foreach ($order as $item): ?>
                                    <tr class="product type-product" data-article="<?= $item['article'] ?>" data-order-id="<?= $item['order_id'] ?>" data-product-id="<?= $item['product_id'] ?>" data-mod-id="<?= $item['mod_id'] ?? 0 ?>">
                                        <td>
                                            <img src="https://its-center.ru/images/product/unload/<?= $item['unload_img'] ?? 'no-image.jpg' ?>" width="50">
                                        </td>
                                        <td><?= $item['article'] ?></td>
                                        <td><?= $item['name'] ?><br>
                                            <?php if ($item['external']): ?>
                                                <span class="badge badge-warning">Внешний товар из 1С</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php if ($is_editable): ?>
                                            <td><?= $item['rest'] ?></td>
                                        <?php endif; ?> 
                                        <td>
                                        <?php if ($is_editable): ?>
                                            <?php
                                                // Уникальный идентификатор товара: product_id или product_id-mod_id
                                                $order_uid = ($item['mod_id'] ?? null) ? $item['product_id'] . '-' . $item['mod_id'] : $item['product_id'];
                                            ?>
                                            <div class="quantity-block" style="display: inline-flex;">
                                                <button type="button"
                                                        class="btn btn-outline-secondary order-quantity-minus"
                                                        data-order-id="<?= $order_uid ?>">-</button>
                                                <span class="order-qty-item">
                                                    <input type="text"
                                                        class="form-control form-control-sm order-qty-input order-qty-item-<?= $order_uid ?>"
                                                        data-order-id="<?= $order_uid ?>"
                                                        data-product-id="<?= $item['product_id'] ?>"
                                                        data-mod="<?= $item['mod_id'] ?? 0 ?>"
                                                        value="<?= $item['qty'] ?>"
                                                        min="1"
                                                        max="<?= $item['rest'] ?>"
                                                        data-price="<?= $item['price'] ?>"
                                                        data-weight="<?= $item['weight'] ?>"
                                                        data-volume="<?= $item['volume'] ?>">
                                                </span>
                                                <button type="button"
                                                        class="btn btn-outline-secondary order-quantity-plus"
                                                        data-order-id="<?= $order_uid ?>">+</button>
                                            </div>
                                        <?php else: ?>
                                            <?= $item['qty'] ?>
                                        <?php endif; ?>
   
                                        </td>
                                        <td><?= App::format_price($item['price']) ?></td>
                                        <td>
                                        <?php if ($is_editable): ?>   
                                            <span class="btn btn-sm btn-danger del-item-order" data-order-id="<?= $order_uid ?>"><i class="fas fa-times"></i></span>                                        
                                        <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td>Итого:</td>
                                    <td></td>
                                    <td>Вес: <strong class="order-weight"><?= round($order_info->weight, 1) ?> кг</strong>; Объём: <strong class="order-volume"><?= round($order_info->volume, 3) ?> м³</strong></td>
                                    <?php if ($is_editable): ?>
                                        <td></td>
                                    <?php endif; ?>
                                    <td>Товаров: <strong class="order-qty"><?= $order_info->total_qty ?></strong></td>
                                    <td>Сумма: <strong class="order-sum"><?= App::format_price($order_info->sum) ?> ₽</strong></td>
                                    <td></td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <?php if ($is_editable): ?>                    
                        <div class="pt-2 pb-4">
                            <input type="hidden" name="end_buyer" value="0">
                            <input class="form-check-input" name="end_buyer" type="checkbox" value="1" id="flexCheckDefault" <?= $order_info->end_buyer ? 'checked' : '' ?> <?= !$is_editable ? 'data-disabled="1"' : '' ?>> Вывести КМ из оборота
                        </div>
                        <?php endif; ?>

                        <?php if (in_array((int)$order_info->status_1c, [1, 7], true)): ?>
                            <!-- Статус 1 или 7 — показывать ничего НЕ надо -->
                        <?php else: ?>
                            <hr>
                            <h4 class="pt-2">Документы по заказу</h4>

                            <?php
                            $guid = $order_info->guid_1c ?? '';
                            $updAvailable = false;
                            $chetAvailable = false;
                            $updUrl = '#';
                            $chetUrl = '#';

                            if ($guid) {
                                $methodUpd = "order/{$guid}/print-upd";
                                $responseUpd = \app\helpers\ApiClient::sendRawRequest('api_orders.php', $methodUpd, 'GET');
                                $updAvailable = ($responseUpd['http_code'] ?? 0) === 200;
                                $updUrl = "/user/proxy-pdf?type=upd&guid={$guid}";

                                $methodChet = "order/{$guid}/print-order";
                                $responseChet = \app\helpers\ApiClient::sendRawRequest('api_orders.php', $methodChet, 'GET');
                                $chetAvailable = ($responseChet['http_code'] ?? 0) === 200;
                                $chetUrl = "/user/proxy-pdf?type=order&guid={$guid}";
                            }
                            ?>

                            <div class="table-responsive">
                                <div class="d-table w-100 border text-center" style="table-layout: fixed;">
                                    <div class="d-table-row font-weight-bold bg-light">
                                        <div class="d-table-cell p-2 border">Счёт</div>
                                        <div class="d-table-cell p-2 border">УПД</div>
                                        <div class="d-table-cell p-2 border">Марки</div>
                                    </div>
                                    <div class="d-table-row">
                                        <div class="d-table-cell p-2 border">
                                            <?php if ($chetAvailable): ?>
                                                <a class="btn btn-sm btn-outline-primary" href="<?= $chetUrl ?>" target="_blank">Распечатать</a>
                                            <?php else: ?>
                                                <span class="text-muted">Нет</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-table-cell p-2 border">
                                            <?php if ($updAvailable): ?>
                                                <a class="btn btn-sm btn-outline-primary" href="<?= $updUrl ?>" target="_blank">Распечатать</a>
                                            <?php else: ?>
                                                <span class="text-muted">Нет</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-table-cell p-2 border">
                                            <?php if (!empty($order_marks)): ?>
                                                <a class="btn btn-sm btn-outline-primary" href="/user/download-marks?id=<?= $order_info->id ?>" target="_blank">Скачать</a>
                                            <?php else: ?>
                                                <span class="text-muted">Нет</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>



                        <?php if ($is_editable): ?> 
                        <!-- ✅ Select2 + кнопка -->
                        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> 
                        <div class="row g-2 mt-3">
                            <div class="col-md-8">
                                <select id="select-product" class="form-select select2" style="width: 100%;">
                                    <option value="">Поиск по названию или артикулу...</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="button" id="add-product-btn" class="btn btn-success w-100">➕ Добавить товар</button>
                            </div>
                        </div>
                        <?php endif; ?>

                        <hr>
                        <h4 class="pt-2">Способ получения</h4>
                        <?php if ($is_editable): ?>
                        <form id="edit-order-form" action="/user/updateOrder" method="post">
                            <input type="hidden" name="order_id" value="<?= $order_info->id ?>">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <div class="col-md-4 mb-3">
                                <select id="dostavka_id" class="form-control" name="dostavka_id" required>
                                    <option value="">Выберите способ получения</option>
                                    <?php foreach ($dostavka as $d): ?>
                                        <option value="<?= $d['id'] ?>" <?= $order_info->dostavka_id == $d['id'] ? 'selected' : '' ?>>
                                            <?= $d['name'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div id="another_sklad" class="col-md-4 mb-3" style="display:none">
                                <select class="form-control" name="branch_id">
                                    <option value="1" <?= $order_info->branch_id == 1 ? 'selected' : '' ?>>Подольск, мкр. Климовск, ул. Коммунальная 26, стр. 2</option>
                                </select>
                            </div>

                            <div id="another_transport" class="col-md-4 mb-3" style="display:none">
                                <select class="form-control" name="transport_id">
                                    <option value="">Выберите транспортную компанию</option>
                                    <?php foreach ($transport_companies as $t): ?>
                                        <option value="<?= $t['id'] ?>" <?= $order_info->transport_id == $t['id'] ? 'selected' : '' ?>>
                                            <?= $t['name'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div id="another_city"
                                class="col-md-4 mb-3"
                                style="display:none"
                                data-current-city-id="<?= (int)($order_info->city_id ?? 0) ?>"
                                data-current-city-text="<?= htmlspecialchars($order_info->city_text ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <label for="order_city_selector" class="mb-1">Город*</label>
                                <select id="order_city_selector" class="form-control" name="city_id">
                                    <option></option> <!-- требуется для placeholder/allowClear -->
                                </select>
                                <input type="hidden" id="order_city_name" name="city_name" value="">
                                <small class="form-text text-muted">
                                    Начните вводить название. Если города нет в списке — введите свой и нажмите Enter.
                                </small>
                            </div>

                            <div id="another_adress" class="col-md-4 mb-3" style="display:none">
                                <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($order_info->address) ?>" placeholder="Адрес доставки товаров">
                            </div>

                            <div class="form-group pt-3">
                                <label for="note">Комментарий</label>
                                <textarea name="note" id="note" class="form-control"><?= htmlspecialchars($order_info->note) ?></textarea>
                            </div>

                            <!-- Кнопка -->
                            <div class="pt-4">
                                <input type="hidden" name="order_id" value="<?= $order_info->id ?>">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <button type="submit" class="btn btn-success">Сохранить заказ</button>
                                <?php if ($is_editable && !empty($order_info->guid_1c)): ?>
                                <button type="submit" formaction="/user/delete-orders" formmethod="post"
                                name="id" value="<?= (int)$order_info->id ?>" class="btn btn-danger"
                                onclick="return confirm('Вы уверены, что хотите отменить заказ?');">
                                    Отмена
                                </button>
                            <?php endif; ?>
                            </div>                            
                        </form>

                        <!-- JS Блок -->
                        
                        <script>
                            
                            let deletedKeys = []; // ключи статуса удаления вида "article-modId"

                            $(document).on('click', 'input[data-disabled="1"]', function (e) {
                                e.preventDefault(); // блокируем клик
                                return false;
                            });

                            $(document).ready(function () {
                                $('#select-product').select2({
                                    placeholder: 'Поиск по названию или артикулу...',
                                    ajax: {
                                        url: '/user/productSearch',
                                        dataType: 'json',
                                        delay: 250,
                                        data: function (params) {
                                            return { q: params.term, request: 1 };
                                        },
                                        processResults: function (data) {
                                            return { results: data.items };
                                        },
                                        cache: true
                                    },
                                    minimumInputLength: 1
                                });

                                // Обработка добавления товара
                                $('#add-product-btn').on('click', function () {
                                    const productId = $('#select-product').val();
                                    if (!productId) return;

                                    $.ajax({
                                        type: 'POST',
                                        url: '/user/productSearch?code=' + productId,
                                        data: { request: 2 },
                                        success: function (html) {
                                            $('#order-products-body').append(html);
                                            $('#select-product').val(null).trigger('change');
                                            recalcOrderTotal();

                                            // Если вернули строку обратно — убираем её из deletedKeys
                                            try {
                                                // в HTML добавленного товара должны быть data-article и data-mod-id
                                                const $last = $('#order-products-body tr').last();
                                                const a = String($last.data('article') || '').trim();
                                                const m = Number($last.data('mod-id') || 0);
                                                const kk = `${a}-${m}`;
                                                deletedKeys = deletedKeys.filter(x => x !== kk);
                                            } catch(e) {}

                                        },
                                        error: function () {
                                            alert('Ошибка при добавлении товара');
                                        }
                                    });
                                });

                                function recalcOrderTotal() {
                                    let totalSum = 0, totalWeight = 0, totalVolume = 0, totalQty = 0;
                                    $('.order-qty-input').each(function () {
                                        const qty = parseInt($(this).val()) || 0;
                                        const price = parseFloat($(this).data('price')) || 0;
                                        const weight = parseFloat($(this).data('weight')) || 0;
                                        const volume = parseFloat($(this).data('volume')) || 0;

                                        totalSum += qty * price;
                                        totalWeight += qty * weight;
                                        totalVolume += qty * volume;
                                        totalQty += qty;
                                    });

                                    $('.order-sum').text(totalSum.toLocaleString('ru-RU', {minimumFractionDigits: 2}) + ' ₽');
                                    $('.order-weight').text(totalWeight.toFixed(2) + ' кг');
                                    $('.order-volume').text(totalVolume.toFixed(2) + ' м³');
                                    $('.order-qty').text(totalQty);
                                }

                                // Обработка изменения количества
                                $('#order-products-body').on('click', '.order-quantity-plus, .order-quantity-minus', function () {
                                    setTimeout(recalcOrderTotal, 100);
                                });

                                $(document).on('keypress', '.order-qty-input', function (e) {
                                    if (e.which === 13) {
                                        e.preventDefault();
                                        recalcOrderTotal();
                                    }
                                });
                               
                                // Удаление товара (явно помечаем, что строка удалена)
                                $('#order-products-body').on('click', '.del-item-order', function () {
                                    const $tr = $(this).closest('tr');
                                    const article = String($tr.data('article') || '').trim();
                                    const modId = Number($tr.data('mod-id') || 0);

                                    if (article !== '') {
                                        const k = `${article}-${modId}`;
                                        if (!deletedKeys.includes(k)) deletedKeys.push(k);
                                    }
                                    $tr.remove();
                                    recalcOrderTotal();
                                });


                                $('#dostavka_id').on('change', function () {
                                    const val = $(this).val();
                                    $('#another_sklad, #another_transport, #another_city, #another_adress').hide();

                                    if (val === '1') {
                                        $('#another_sklad').show();
                                    } else if (val === '2') {
                                        $('#another_transport, #another_city').show();
                                    } else if (val === '3') {
                                        $('#another_city, #another_adress').show();
                                    }
                                });

                                $('#dostavka_id').trigger('change');

                                // Отправка формы                               
                                $('#edit-order-form').on('submit', function (e) {
                                    e.preventDefault();
                                    $('.preloader').fadeIn();

                                    const form = this;
                                    const data = new FormData(form);

                                    const isChecked = $('#flexCheckDefault').is(':checked') ? '1' : '0';
                                    data.append('end_buyer', isChecked);

                                    $(form).serializeArray().forEach(({ name, value }) => {
                                        if (name !== 'end_buyer') data.append(name, value);
                                    });

                                    // товары
                                    $('#order-products-body tr').each(function () {
                                        const article  = $(this).data('article');
                                        const productId= $(this).data('product-id');
                                        const modId    = $(this).data('mod-id') || 0;
                                        const qty      = parseInt($(this).find('input.order-qty-input').val()) || 0;

                                        const key = modId ? `${productId}-${modId}` : `${productId}`;

                                        if (article && qty > 0) {
                                            data.append(`products[${key}][itemCode]`, article);
                                            data.append(`products[${key}][product_id]`, productId);
                                            data.append(`products[${key}][mod_id]`, modId);
                                            data.append(`products[${key}][qnt]`, qty);
                                        }
                                    });

                                    // явные удаления
                                    deletedKeys.forEach(k => data.append('deleted[]', k));

                                    $.ajax({
                                        url: $(form).attr('action'),
                                        method: 'POST',
                                        data: data,
                                        processData: false,
                                        contentType: false,
                                        dataType: 'json',
                                        success: function (res) {
                                            $('.preloader').fadeOut();
                                            if (res && res.success) {
                                                location.reload();
                                            } else {
                                                console.error(res);
                                                location.reload();
                                            }
                                        },
                                        error: function (xhr) {
                                            $('.preloader').fadeOut();
                                            console.error(xhr.responseText);
                                            location.reload();
                                        }
                                    });
                                });

                                $('body').on('click', '.order-quantity-plus', function(){
                                    const id = $(this).data('order-id');
                                    const input = $('.order-qty-item-' + id);
                                    let val = parseInt(input.val()) || 1;
                                    const max = parseInt(input.attr('max')) || 999;

                                    if (val < max) {
                                        val++;
                                        input.val(val);
                                        recalcOrderTotal(); // кастомная функция
                                    }
                                });

                                $('body').on('click', '.order-quantity-minus', function(){
                                    const id = $(this).data('order-id');
                                    const input = $('.order-qty-item-' + id);
                                    let val = parseInt(input.val()) || 1;

                                    if (val > 1) {
                                        val--;
                                        input.val(val);
                                        recalcOrderTotal();
                                    }
                                });

                            });
                            
                        </script>
                        <?php else: ?>
                            <div class="mt-3">
                            <?php
                                // Название способа доставки
                                $dostavka_name = '';
                                foreach ($dostavka as $d) {
                                    if ($order_info->dostavka_id == $d['id']) {
                                        $dostavka_name = $d['name'];
                                        break;
                                    }
                                }

                                echo '<strong>Способ получения:</strong> ' . $dostavka_name . '<br>';

                                if ($order_info->dostavka_id == 1) {
                                    echo '<strong>Самовывоз:</strong> Подольск, мкр. Климовск, ул. Коммунальная 26, стр. 2';
                                } elseif ($order_info->dostavka_id == 2) {
                                    // Транспортная компания и город
                                    $tc = null;
                                    foreach ($transport_companies as $t) {
                                        if ($order_info->transport_id == $t['id']) {
                                            $tc = $t['name'];
                                            break;
                                        }
                                    }

                                    // Город: либо из справочника, либо текст
                                    $city = null;
                                    if ((int)$order_info->city_id > 0) {
                                        foreach ($cities as $c) {
                                            if ((int)$order_info->city_id == (int)$c['id']) {
                                                $city = $c['city_name'];
                                                break;
                                            }
                                        }
                                    } elseif (!empty($order_info->city_text)) {
                                        $city = $order_info->city_text;
                                    }

                                    echo '<strong>Транспортная:</strong> ' . ($tc ?: '—');
                                    if ($city) echo ', ' . htmlspecialchars($city);

                                } elseif ($order_info->dostavka_id == 3) {
                                    echo '<strong>Курьерская доставка:</strong>';
                                    $address_parts = [];

                                    foreach ($cities as $c) {
                                        if ($order_info->id == $c['id']) {
                                            $address_parts[] = $c['city_name'];
                                            break;
                                        }
                                    }

                                    if (!empty($order_info->address)) {
                                        $address_parts[] = $order_info->address;
                                    }

                                    echo ' ' . htmlspecialchars(implode(', ', $address_parts));
                                }
                                ?>
                                <br /><strong>Комментарий:</strong> <?= htmlspecialchars($order_info->note) ?>
                            </div>                                            
                        <?php endif; ?>    
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
// ======== Город (редактирование заказа) ========
function renderCitySelect2ForOrder() {
    const $wrap = $('#another_city');
    const $sel  = $('#order_city_selector');
    const $name = $('#order_city_name');

    // локализация сообщений Select2 (без подключения ru.js)
    const decl = (n, forms) => {
        n = Math.abs(n) % 100; const n1 = n % 10;
        if (n > 10 && n < 20) return forms[2];
        if (n1 > 1 && n1 < 5) return forms[1];
        if (n1 === 1) return forms[0];
        return forms[2];
    };

    $name.val('');

    if ($.fn.select2) {
        $sel.select2({
            placeholder: 'Начните вводить город...',
            allowClear: true,
            tags: true,
            minimumInputLength: 1,
            ajax: {
                url: '/city/search',
                dataType: 'json',
                delay: 250,
                data: params => ({ q: params.term }),
                processResults: data => ({ results: data }),
                cache: true
            },
            createTag: params => {
                const term = (params.term || '').trim();
                if (!term) return null;
                return { id: '__custom__:' + term, text: term, newTag: true };
            },
            templateSelection: d => d.text || d.id,
            language: {
                inputTooShort: args => {
                    const n = args.minimum - (args.input ? args.input.length : 0);
                    return 'Введите ещё ' + n + ' ' + decl(n, ['символ', 'символа', 'символов']);
                },
                noResults:     () => 'Ничего не найдено',
                searching:     () => 'Поиск…',
                loadingMore:   () => 'Загрузка ещё…',
                errorLoading:  () => 'Ошибка загрузки',
                removeAllItems:() => 'Удалить все',
                maximumSelected: args => 'Можно выбрать не более ' + args.maximum
            }
        });

        // предзаполнение текущим значением
        const currentId   = Number($wrap.data('current-city-id') || 0);
        const currentText = String($wrap.data('current-city-text') || '').trim();

        if (currentId > 0) {
            // справочник
            const opt = new Option('Загрузка…', currentId, true, true);
            $sel.append(opt).trigger('change');

            // подменим «Загрузка…» на реальное имя из $cities (если есть в шаблоне),
            // либо оставим как есть — сервер всё равно примет id
            // Можно подтянуть через AJAX, но обычно список уже загружен ранее.
        } else if (currentText !== '') {
            // кастомный город
            $name.val(currentText);
            const opt = new Option(currentText, '__custom__:' + currentText, true, true);
            $sel.append(opt).trigger('change');
            $sel.data('isCustom', true);
        }

        // пользовательский выбор
        $sel.on('select2:select', function (e) {
            const sel = e.params.data || {};
            const isCustom = sel.newTag || String(sel.id || '').indexOf('__custom__:') === 0;
            if (isCustom) {
                $name.val(sel.text);
                $sel.data('isCustom', true);
            } else {
                $name.val('');
                $sel.removeData('isCustom');
            }
        });

        // очистка крестиком
        $sel.on('select2:clear', function () {
            $name.val('');
            $sel.removeData('isCustom');
        });

        $sel.on('change', function () {
            if (!$sel.val()) {
                $name.val('');
                $sel.removeData('isCustom');
            }
        });
    }
}

// показать нужные поля по доставке + смонтировать Select2 для города
function showDeliveryFieldsOrder(dostavkaVal) {
    $('#another_sklad, #another_transport, #another_city, #another_adress').hide().empty();

    if (dostavkaVal === '1') {
        // самовывоз
        $('#another_sklad').show().html(`
            <select class="form-control" name="branch_id">
                <option value="1" <?= (int)$order_info->branch_id === 1 ? 'selected' : '' ?>>Подольск, мкр. Климовск, ул. Коммунальная 26, стр. 2</option>
            </select>
        `);
    } else if (dostavkaVal === '2') {
        // ТК
        $('#another_transport').show().html(`
            <select class="form-control" name="transport_id">
                <option value="">Выберите транспортную компанию</option>
                <?php foreach ($transport_companies as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= (int)$order_info->transport_id === (int)$t['id'] ? 'selected' : '' ?>>
                        <?= $t['name'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        `);

        // Город
        $('#another_city').show().html(`
            <label for="order_city_selector" class="mb-1">Город*</label>
            <select id="order_city_selector" class="form-control" name="city_id">
                <option></option>
            </select>
            <input type="hidden" id="order_city_name" name="city_name" value="">
            <small class="form-text text-muted">
                Начните вводить название. Если города нет в списке — введите свой и нажмите Enter.
            </small>
        `);
        renderCitySelect2ForOrder();
    } else if (dostavkaVal === '3') {
        // Курьерка (пример)
        $('#another_city').show().html(`
            <label class="mb-1">Город*</label>
            <select class="form-control" name="city_id" required>
                <option value="5001" <?= (int)$order_info->city_id === 5001 ? 'selected' : '' ?>>Москва</option>
            </select>
        `);
        $('#another_adress').show().html(`
            <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($order_info->address ?? '') ?>" placeholder="Адрес доставки товаров">
        `);
    }
}

// инициализация при загрузке
$(function () {
    const initVal = $('#dostavka_id').val();
    showDeliveryFieldsOrder(initVal);

    $('#dostavka_id').on('change', function () {
        showDeliveryFieldsOrder($(this).val());
    });

    // фронт-валидация на submit (как в корзине)
    $('#edit-order-form').on('submit', function (e) {
        const dost = $('#dostavka_id').val();
        if (dost === '2') {
            const $sel = $('#order_city_selector');
            const data = $.fn.select2 ? $sel.select2('data') : [];
            const selected = (data && data.length) ? data[0] : null;
            const cityIdVal = $sel.val();
            const cityName  = ($('#order_city_name').val() || '').trim();

            if ((!cityIdVal || cityIdVal === '') && cityName === '') {
                e.preventDefault();
                alert('Укажите город получателя (выберите из списка или введите свой и нажмите Enter).');
                return false;
            }

            // если выбран кастом — отключим select, чтобы city_id не ушёл
            const isCustom = (selected && (selected.newTag || String(selected.id || '').indexOf('__custom__:') === 0)) || $sel.data('isCustom');
            if (isCustom) $sel.prop('disabled', true);
            else $('#order_city_name').val(''); // если справочник — очищаем текст
        }
        return true;
    });
});
</script>
