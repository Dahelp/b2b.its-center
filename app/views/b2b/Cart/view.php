<?php
/** @var array $cart */
/** @var int $cartQty */
/** @var float $cartSum */
/** @var array $cartCurrency */
/** @var float $cartWeight */
/** @var float $cartVolume */
/** @var array|null $b2buser */
/** @var array $formData */
/** @var array $dostavka */
/** @var array $transport */
/** @var array $branch */
/** @var \RedBeanPHP\OODBBean|null $company */
?>

<!--prdt-starts-->
<section class="py-5">
    <div class="container">
        <div class="d-flex align-items-start cab-inner">
            <div class="aiz-user-panel cart-block" style="display: <?= !empty($cart) ? 'block' : 'none' ?>">
                <form method="post" action="cart/checkout" role="form" enctype="multipart/form-data" id="checkout-form">
                    <div class="card">
                        <div class="card-header register-top heading d-flex justify-content-between align-items-center pb-4">
                            <h5 class="mb-0 h6">Оформление заказа</h5>
                            <button type="button" class="btn btn-sm btn-outline-danger clear-cart">Очистить корзину</button>
                        </div>
                        <div class="card-body">
                            <div id="prodcart" class="casters-block table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead>
                                        <tr>
                                            <th>Фото</th>
                                            <th>Артикул</th>
                                            <th>Наименование</th>
                                            <th>Наличие</th>
                                            <th>Кол-во</th>
                                            <th>Цена</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($cart as $id => $item): ?>
                                        <?php $cartId = ($item['mod_id'] ?? 0) ? "{$item['id']}-{$item['mod_id']}" : $item['id']; ?>
                                        <tr class="product type-product" data-id="<?= $id ?>">
                                            <td>
                                                <img src="images/product/unload/<?= $item['unload_img'] ?? 'no-image.jpg' ?>" alt="<?= $item['name'] ?? '' ?>">
                                            </td>
                                            <td><?= $item['article'] ?? '' ?></td>
                                            <td><?= $item['name'] ?? '' ?></td>
                                            <td><?= $item['max'] ?? '' ?></td>
                                            <td class="btn_price btn-korz" style="text-align:left">
                                                <div class="quantity-block my_quant-<?= $cartId ?>" style="display:inline-flex">
                                                    <button type="button" class="btn btn-outline-secondary quantity-arrow-minus"
                                                        data-id="<?= $item['id'] ?>"
                                                        data-mod="<?= $item['mod_id'] ?>">-</button>

                                                    <span class="qty-item">
                                                        <input type="text"
                                                            class="form-control form-control-sm qty-input qty-item-<?= $cartId ?>"
                                                            data-id="<?= $item['id'] ?>"
                                                            data-mod="<?= $item['mod_id'] ?>"
                                                            value="<?= $item['qty'] ?? 1 ?>"
                                                            min="1"
                                                            max="<?= $item['max'] ?? 999 ?>">
                                                    </span>

                                                    <button type="button" class="btn btn-outline-secondary quantity-arrow-plus"
                                                        data-id="<?= $item['id'] ?>"
                                                        data-mod="<?= $item['mod_id'] ?>">+</button>
                                                </div>
                                            </td>
                                            <td><?= $item['final_price'] ?? 0 ?></td>
                                            <td>
                                                <span data-id="<?= $id ?>" class="btn btn-sm btn-danger del-item-cart">
                                                    <i class="fas fa-times"></i>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td>Итого:</td>
                                            <td></td>
                                            <td>Вес: <strong class="cart-weight"><?= $cartWeight ?> кг</strong>; Объём: <strong class="cart-volume"><?= $cartVolume ?> м<sup>3</sup></strong></td>
                                            <td></td>
                                            <td>Товаров: <strong class="cart-qty"><?= $cartQty ?></strong></td>
                                            <td>Сумма: <strong class="cart-sum"><?= $cartCurrency['symbol_left'] . $cartSum . $cartCurrency['symbol_right'] ?></strong></td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- Итого -->
                            <div class="pt-2 pb-4">
                                <input type="hidden" name="end_buyer" value="0">
                                <input class="form-check-input" name="end_buyer" type="checkbox" value="1" id="flexCheckDefault"> Вывести КМ из оборота
                            </div>
                            
                            <!-- Покупатель -->
                            <h4 class="pt-2">Покупатель</h4>
                            <div class="col-md-4 pb-4">
                                <select class="form-control" name="comp_id" required>
                                    <option value="">Выберите покупателя</option>
                                    <?php foreach ($company as $cp): ?>
                                        <option value="<?= $cp['id'] ?>"><?= $cp['comp_short_name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <h4 class="pt-2">Способ получения</h4>
                            <div class="col-md-4 pb-4">
                                <select id="dostavka_id" class="form-control" name="dostavka_id" required>
                                    <option value="">Выберите способ получения</option>
                                    <?php foreach ($dostavka as $d): ?>
                                        <option value="<?= $d['id'] ?>"><?= $d['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Эти блоки показываем динамически -->
                            <div id="another_sklad" class="col-md-6" style="display:none"></div>
                            <div id="another_transport" class="col-md-6 pb-3" style="display:none"></div>

                            <!-- Город: Select2 (ajax+tags). Отправляем либо city_id, либо city_name -->
                            <div id="another_city" class="col-md-6 pb-3" style="display:none;">
                                <label for="city_selector" class="mb-1">Город получения*</label>
                                <select id="city_selector" class="form-control" name="city_id"></select>
                                <input type="hidden" id="city_name" name="city_name" value="">
                                <small class="form-text text-muted">Начните вводить название. Если города нет в списке — добавьте свой и нажмите Enter.</small>
                            </div>

                            <div id="another_adress" class="col-md-6 pt-2" style="display:none"></div>

                            <h4 class="pt-4">Контактные данные</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <label>ФИО*</label>
                                    <input type="text" name="name" class="form-control" value="<?= $formData['name'] ?? '' ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label>Телефон*</label>
                                    <input type="text" name="telefon" class="form-control" value="<?= $formData['telefon'] ?? '' ?>" required>
                                </div>
                                <div class="col-md-6 pt-3">
                                    <label>Email*</label>
                                    <input type="email" name="email" class="form-control" value="<?= $formData['email'] ?? '' ?>" required>
                                </div>
                                <div class="col-md-6 pt-3">
                                    <label>Комментарий</label>
                                    <textarea name="note" class="form-control"></textarea>
                                </div>
                            </div>
                            
                            <div class="pt-4">
                                <button class="btn btn-primary" type="submit">Оформить заказ</button>
                            </div>

                        </div>
                    </div>
                </form>
            </div>

            <!-- Пустая корзина -->
            <div class="col-md-12 cart-no-product text-center" style="display: <?= empty($cart) ? 'block' : 'none' ?>">
                <div class="cart-no-title">В корзине нет товаров</div>
                <div class="cart-no-info">Найдите то, что вам нужно в каталоге или при помощи поиска</div>
                <div class="cart-no-button">
                    <a class="btn btn-outline-primary" href="<?= isset($b2buser) ? 'user/cabinet' : '/' ?>">Вернуться к покупкам</a>
                </div>
            </div>                            

        </div>
    </div>
</section>
<!--product-end-->

<!-- Вставка JS -->
<script>
document.addEventListener("DOMContentLoaded", function () {
  function waitForJquery(callback) {
    if (typeof window.jQuery === "undefined") setTimeout(() => waitForJquery(callback), 50);
    else callback(window.jQuery);
  }

  waitForJquery(function ($) {
    // Можно использовать как fallback для datalist:
    const cityList = <?= json_encode($cities, JSON_UNESCAPED_UNICODE) ?>; // [{id,city_name}, ...]

    function renderPickupBranch() {
      $('#another_sklad').show().html(`
        <label class="mb-1">Склад самовывоза*</label>
        <select class="form-control" name="branch_id" required>
          <option value="1" selected>Подольск, мкр. Климовск, ул. Коммунальная 26, стр. 2</option>
        </select>
      `);
    }

    function renderTransportCompany() {
      $('#another_transport').show().html(`
        <label class="mb-1">Транспортная компания*</label>
        <select class="form-control" name="transport_id" required>
          <option value="">Выберите транспортную компанию</option>
          <option value="1">ПЭК</option>
          <option value="2">Деловые Линии</option>
          <option value="3">Байкал</option>
          <option value="5">КИТ</option>
          <option value="8">Энергия</option>
          <option value="9">Другая</option>
        </select>
      `);
    }

    function renderCityBlock() {
    $('#another_city').show().html(`
        <label for="city_selector" class="mb-1">Город получения*</label>
        <select id="city_selector" class="form-control" name="city_id">
        <option></option> <!-- важно для placeholder/allowClear -->
        </select>
        <input type="hidden" id="city_name" name="city_name" value="">
        <small class="form-text text-muted">
        Начните вводить название. Если города нет в списке — введите свой и нажмите Enter.
        </small>
    `);

    initCitySelect();
    }


    function initCitySelect() {
        const $sel = $('#city_selector');
        $('#city_name').val('');

        if ($.fn.select2) {
            $sel.select2({
            language: 'ru',
            placeholder: 'Начните вводить город...',
            allowClear: true,              // покажет крестик
            tags: true,                    // можно свои значения
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
                // генерируем стабильный id для custom-значения
                return { id: '__custom__:' + term, text: term, newTag: true };
            },
            templateSelection: data => data.text || data.id
            });

            // Выбор значения
            $sel.on('select2:select', function (e) {
            const sel = e.params.data || {};
            const isCustom = sel.newTag || String(sel.id || '').indexOf('__custom__:') === 0;

            if (isCustom) {
                // «Свой» город: храним текст в скрытом поле, select2 оставляем как есть,
                // чтобы он отображал выбранный бейдж
                $('#city_name').val(sel.text);
                $sel.data('isCustom', true);
            } else {
                // Справочник: чистим кастомное имя
                $('#city_name').val('');
                $sel.removeData('isCustom');
            }
            });

            // Очистка крестиком
            $sel.on('select2:clear', function () {
            $('#city_name').val('');
            $sel.removeData('isCustom');
            });

            // Любое изменение: если значения нет — чистим скрытое поле
            $sel.on('change', function () {
            if (!$sel.val()) {
                $('#city_name').val('');
                $sel.removeData('isCustom');
            }
            });

            return;
        }

        // --- Fallback без Select2 (input + datalist) ---
        const datalistId = 'city_datalist';
        const options = (window.cityList || []).map(c => `<option data-id="${c.id}" value="${c.city_name}"></option>`).join('');
        $('#another_city').append(`
            <input list="${datalistId}" class="form-control mt-2" id="city_input_fallback" placeholder="Начните вводить город">
            <datalist id="${datalistId}">${options}</datalist>
        `);

        $('#city_input_fallback').on('change', function () {
            const val = this.value.trim();
            const match = (window.cityList || []).find(c => c.city_name === val);
            if (match) {
            $sel.html(`<option value="${match.id}" selected>${match.city_name}</option>`);
            $('#city_name').val('');
            } else {
            $sel.html(''); // не отправляем city_id
            $('#city_name').val(val);
            }
        });
    }

    function renderCourierAddress() {
      $('#another_adress').show().html(`
        <label class="mb-1">Адрес доставки*</label>
        <input type="text" name="address" class="form-control" placeholder="Адрес доставки товаров" required>
      `);
    }

    function showDeliveryFields(dostavka) {
      $('#another_sklad, #another_city, #another_transport, #another_adress').hide().empty();

      if (dostavka === '1') {
        // Самовывоз
        renderPickupBranch();
      } else if (dostavka === '2') {
        // Транспортная компания
        renderTransportCompany();
        renderCityBlock(); // <-- ВАЖНО: теперь вставляем разметку и инициализируем
      } else if (dostavka === '3') {
        // Курьерская доставка (если используешь)
        $('#another_city').show().html(`
          <label class="mb-1">Город*</label>
          <select class="form-control" name="city_id" required>
            <option value="5001" selected>Москва</option>
          </select>
        `);
        renderCourierAddress();
      }
    }

    // Слушатель
    $('#dostavka_id').on('change', function () {
      showDeliveryFields($(this).val());
    });

    // Первичная инициализация по уже выбранному способу
    const initialDostavka = $('#dostavka_id').val();
    if (initialDostavka) showDeliveryFields(initialDostavka);

    // Доп. фронт-валидация на submit: для ТК нужен city_id или city_name
    $('#checkout-form').on('submit', function (e) {
        const dost = $('#dostavka_id').val();
        if (dost === '2') {
            const $sel = $('#city_selector');
            const data = $.fn.select2 ? $sel.select2('data') : [];
            const selected = (data && data.length) ? data[0] : null;
            const cityIdVal = $sel.val();
            const cityName  = ($('#city_name').val() || '').trim();

            // Требуем заполнения
            if ((!cityIdVal || cityIdVal === '') && cityName === '') {
            e.preventDefault();
            alert('Укажите город получателя (выберите из списка или введите свой и нажмите Enter).');
            return false;
            }

            // Если выбран кастом — отключаем select (чтобы city_id не ушёл)
            const isCustom = (selected && (selected.newTag || String(selected.id || '').indexOf('__custom__:') === 0)) || $sel.data('isCustom');
            if (isCustom) {
            $sel.prop('disabled', true);
            } else {
            // если выбрали справочник — перестрахуемся, чтобы кастом не улетел
            $('#city_name').val('');
            }
        }
        return true;
    });

  });
});
</script>

