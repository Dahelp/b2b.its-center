/* Фильтры товаров */
$(function () {
  // ✅ Инициализация Select2
  $(".js-select2").select2({
    closeOnSelect: false,
    placeholder: "Выберите значение...",
    allowClear: true,
    tags: false,
    ajax: {
      // ⚠️ ВЫБЕРИТЕ ПРАВИЛЬНЫЙ endpoint:
      // если filterSearchAction в FilterController → '/filter/filter-search'
      // если в PodborController → '/podbor/filter-search'
      url: '/filter/filter-search',
      dataType: 'json',
      delay: 250,
      data: function (params) {
        // безопасно получаем исходный <select>
        const $el = $(this.$element || this);
        const groupId = $el.closest('section').data('group-id');
        const type = $el.data('type');
        const categoryId =
          $('input[name="current_category_id"]').val()
          || new URLSearchParams(window.location.search).get('category');

        return {
          q: params.term || '',
          group: groupId,
          type: type,
          category: categoryId
        };
      },
      processResults: function (data) {
        return { results: data };
      },
      cache: true
    },
    language: {
      errorLoading: () => "Ошибка загрузки данных",
      noResults: () => "Ничего не найдено",
      searching: () => "Поиск...",
      inputTooShort: () => "Введите больше символов"
    }
  }).on('select2:select', function () {
    const s2 = $(this).data('select2');
    const searchField = (s2 && (s2.dropdown && s2.dropdown.$search)) || (s2 && s2.selection && s2.selection.$search);
    if (searchField) searchField.val('');
    if (s2) s2.close();
  });

  // ✅ Обработка фильтрации при выборе
  $('body').on('change', '.w_sidebar select', function () {
    const selected = $('.w_sidebar option:selected');
    const values = [];
    selected.each(function () {
      if (this.value) values.push(this.value);
    });

    const filterData = values.join(',');
    const params = new URLSearchParams();

    if (filterData) {
      params.set('filter', filterData);
    } else {
      // нет выбранных атрибутов — очищаем список и URL
      $('#products-list').html('<div class="text-center py-3">Выберите параметры фильтра для отображения товаров.</div>');
      history.replaceState({}, '', location.pathname);
      return;
    }

    const page = new URLSearchParams(location.search).get('page');
    if (page) params.set('page', page);

    const newUrl = location.pathname + '?' + params.toString();

    $.ajax({
      url: newUrl,
      type: 'GET',
      beforeSend: function () {
        $('.preloader').fadeIn(300, () => $('#products-list').hide());
      },
      success: function (res) {
        $('.preloader').delay(300).fadeOut('slow', () => {
          $('#products-list').html(res).fadeIn();
          $('#sort-block').removeClass('hidden').fadeIn();
          history.replaceState({}, '', newUrl);
        });
      }
    });
  });

  // ✅ Пагинация AJAX
  $('body').on('click', '.pagination a.page-link', function (e) {
    e.preventDefault();
    const link = $(this).attr('href');
    if (!link || link === '#') return;

    $.ajax({
      url: link,
      type: 'GET',
      beforeSend: function () {
        $('.preloader').fadeIn(300, () => $('#products-list').hide());
      },
      success: function (res) {
        $('.preloader').delay(300).fadeOut('slow', () => {
          $('#products-list').html(res).fadeIn();
          $('#sort-block').removeClass('hidden').fadeIn();
          history.replaceState({}, '', link);
        });
      },
      error: () => alert('Ошибка при загрузке страницы')
    });
  });

  // ✅ Первичная загрузка (filter / cross / find_pids)
  restoreFiltersAndLoad();
});

function restoreFiltersAndLoad() {
  const params = new URLSearchParams(window.location.search);
  let filterStr = (params.get('filter') || '').trim();
  const crossStr = (params.get('cross') || '').trim();
  const findStr  = (params.get('find_pids') || '').trim();
  const pageStr  = (params.get('page') || '').trim();

  // Восстановим выбранные атрибуты в select2
  if (filterStr) {
    const values = filterStr.split(',').filter(Boolean);
    $('.js-select2 option').each(function () {
      if (values.includes($(this).val())) $(this).prop('selected', true);
    });
    $('.js-select2').trigger('change'); // это само сделает AJAX и подставит список
    return; // дальше ничего не грузим
  }

  // ⛔ Если страница > 1 — не грузим сами
  if (pageStr && parseInt(pageStr, 10) > 1) return;

  // Если есть cross или find_pids — грузим их partial
  const out = new URLSearchParams();
  if (crossStr) out.set('cross', crossStr);
  if (findStr)  out.set('find_pids', findStr);

  if ([...out.keys()].length === 0) return;

  const ajaxUrl = location.pathname + '?' + out.toString();

  $.ajax({
    url: ajaxUrl,
    type: 'GET',
    beforeSend: function () {
      $('.preloader').fadeIn(300, () => $('#products-list').hide());
    },
    success: function (res) {
      $('.preloader').delay(300).fadeOut('slow', () => {
        $('#products-list').html(res).fadeIn();
        $('#sort-block').removeClass('hidden').fadeIn();
      });
    },
    error: () => alert('Ошибка при загрузке товаров')
  });
}
/* Фильтры товаров */

/*Modal product*/
$('body').on('click', '.view-product', function(e) {
    e.preventDefault();
    const productId = $(this).data('id');

    $('#productModalBody').html('<div class="text-center">Загрузка...</div>');

    $.ajax({
        url: '/product/modal',
        type: 'GET',
        data: { id: productId },
        success: function(res) {
            $('#productModalBody').html(res);

        },
        error: function() {
            $('#productModalBody').html('<div class="text-danger text-center">Ошибка загрузки данных</div>');
        }
    });
});

/* Sort product */
$(document).ready(function () {
 $(".sort-inner span").click(function () {
	var id = $(this).attr('id');
	
	$('.sort-inner span').toggleClass('active', false);
	$('.sort-inner span#'+$(this).attr('id')+'').toggleClass('active');
	$.ajax({
	    url: location.href,
            data: 'sort='+id,
            type: 'GET',
            beforeSend: function(){
                $('.preloader').fadeIn(300, function(){
                    $('.product-one').hide();
                });
		
            },
            success: function(res){
                $('.preloader').delay(500).fadeOut('slow', function(){
                    $('.product-one').html(res).fadeIn();
                    $('#sort-block').removeClass('hidden').fadeIn();
                    var url = location.search.replace(/sort(.+?)(&|$)/g, ''); //$2
                    var newURL = location.pathname + url + (location.search ? "&" : "?") + "sort=" + id;
                    newURL = newURL.replace('&&', '&');
                    newURL = newURL.replace('?&', '?');
                    history.pushState({}, '', newURL);
			
                });
		
            },
            error: function () {
                alert('Ошибка!');
            }
        });
		
 });   
});

// ✅ Cart scripts

$(function() {
    updateCartInfo();
});

// Добавление в корзину
$('body').on('click', '.add-to-cart-link', function(e){
    e.preventDefault();

    const id = $(this).data('id');
    const mod = $(this).data('mod') || 0;
    const cartKey = mod > 0 ? `${id}-${mod}` : `${id}`;
    const max = $(this).data('max');
    const qty = $('.qty-item-' + cartKey).val() || 1;

    $.get('/cart/add', { id, qty, max, mod }, function(res) {
        const data = JSON.parse(res);

        $('#cart-total').text(data.qty);
        $('.korzina-' + cartKey).hide();
        $('.my_quant-' + cartKey).css('display', 'inline-flex');
        $('.qty-item-' + cartKey).val(data.product_qty || qty);

        showCartBadge(data.qty);
        animateCartFeedback("✓ Товар добавлен");
        updateCartInfo();
    });
});

// Увеличение
$('body').on('click', '.quantity-arrow-plus', function(){
    const id = $(this).data('id');
    const mod = $(this).data('mod') || 0;
    const cartKey = mod > 0 ? `${id}-${mod}` : `${id}`;
    const input = $('input.qty-item-' + cartKey);

    let val = parseInt(input.val()) || 1;
    const max = parseInt(input.attr('max')) || val;

    if (val < max) {
        val++;
        input.val(val);
        updateCartQuantity(id, val, max, 'increased', mod);
        if (typeof recalcTotalSum === 'function') recalcTotalSum();
    }
});

// Уменьшение
$('body').on('click', '.quantity-arrow-minus', function(){
    const id = $(this).data('id');
    const mod = $(this).data('mod') || 0;
    const cartKey = mod > 0 ? `${id}-${mod}` : `${id}`;
    const input = $('input.qty-item-' + cartKey);

    let val = parseInt(input.val()) || 1;
    const max = parseInt(input.attr('max')) || val;

    if (val > 1) {
        val--;
        input.val(val);
        updateCartQuantity(id, val, max, 'decreased', mod);
    } else {
        updateCartQuantity(id, 0, max, 'removed', mod);
        $('.my_quant-' + cartKey).hide();
        $('.korzina-' + cartKey).show();
    }

    if (typeof recalcTotalSum === 'function') recalcTotalSum();
});

// Ручной ввод количества
$('body').on('keypress', '.qty-input', function(e) {
    if (e.which === 13) {
        e.preventDefault();
        const id = $(this).data('id');
        const mod = $(this).data('mod') || 0;
        const cartKey = mod > 0 ? `${id}-${mod}` : `${id}`;
        const val = parseInt($(this).val()) || 1;
        const max = parseInt($(this).attr('max')) || val;

        updateCartQuantity(id, val, max, 'updated', mod);
    }
});

// Проверка на max количества
$('body').on('input', '.order-qty-input', function () {
    const $input = $(this);
    const max = parseFloat($input.attr('max'));
    let val = parseFloat($input.val());

    if (isNaN(val) || val < 1) {
        $input.val(1);
    } else if (val > max) {
        $input.val(max);
        alert('Нельзя выбрать больше, чем есть в наличии');
    }
});

// Удаление товара
$('body').on('click', '.del-item-cart', function(){
    const id = $(this).data('id');
    const mod = $(this).data('mod') || 0;
    const cartKey = mod > 0 ? `${id}-${mod}` : `${id}`;

    updateCartQuantity(id, 0, null, 'removed', mod);
    $('.my_quant-' + cartKey).hide();
    $('.korzina-' + cartKey).show();
});

// Очистка корзины
$('body').on('click', '.clear-cart', function(){
    $.get('/cart/clear', function(){
        $('#cart-total').text('0').addClass('d-none');
        $('.cart-block').hide();
        $('.cart-no-product').show();
        $('.my_quant-qty').hide();     // скрыть все input-блоки
        $('.btn.korzina-qty').show();  // показать все кнопки
        animateCartFeedback("⛔ Корзина очищена", "danger");
        updateCartInfo();
    });
});

// Обновление количества
function updateCartQuantity(id, qty, max = null, action = 'updated', mod = 0) {
    const cartKey = mod > 0 ? `${id}-${mod}` : `${id}`;
    const $input = $('.qty-item-' + cartKey);

    $.get('/cart/add', { id, qty, max, mod }, function(res){
        const data = JSON.parse(res);

        $('#cart-total').text(data.qty);
        $('.cart-qty').text(data.qty);
        $('.cart-sum').text(data.sum);
        $input.val(data.product_qty || qty);

        if (data.weight !== undefined) {
            $('.cart-weight').text(parseFloat(data.weight).toFixed(2) + ' кг');
        }

        if (data.volume !== undefined) {
            $('.cart-volume').text(parseFloat(data.volume).toFixed(2) + ' м³');
        }

        let msg = "✓ Обновлено", type = "success";
        if (action === "increased") msg = "➕ Кол-во увеличено";
        if (action === "decreased") msg = "➖ Кол-во уменьшено";
        if (action === "removed") msg = "⛔ Товар удалён", type = "danger";

        animateCartFeedback(msg, type);
        updateCartInfo();

        if (qty === 0) {
            $('tr[data-id="' + cartKey + '"]').remove();
            if ($('tr[data-id]').length === 0) {
                $('.cart-block').hide();
                $('.cart-no-product').show();
            }
        }

        if (typeof recalcTotalSum === 'function') recalcTotalSum();
    });
}

// Обновление информации в корзине
function updateCartInfo() {
    $.ajax({
        url: '/cart/status',
        method: 'GET',
        dataType: 'json',
        success: function (data) {
            const badge = $('#cart-total');

            if (data.totalQty !== undefined) {
                badge.text(data.totalQty);
                showCartBadge(data.totalQty);
                $('.cart-qty').text(data.totalQty);
            }

            if (data.totalSum !== undefined && data.currency) {
                const symbolLeft = data.currency.symbol_left || '';
                const symbolRight = data.currency.symbol_right || '';
                $('.cart-sum').html(symbolLeft + data.totalSum + symbolRight);
            }

            if (data.totalWeight !== undefined) {
                $('.cart-weight').text(data.totalWeight + ' кг');
            }

            if (data.totalVolume !== undefined) {
                $('.cart-volume').text(data.totalVolume + ' м³');
            }
        },
        error: function () {
            console.error('Ошибка при получении данных корзины');
        }
    });
}

// Показ badge
function showCartBadge(qty) {
    const badge = $('#cart-total');
    if (parseInt(qty) > 0) {
        badge.removeClass('d-none')
              .addClass('position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger')
              .text(qty);
    } else {
        badge.text('0').addClass('d-none');
    }
}

// Уведомление
function animateCartFeedback(message = "✓ Добавлено", type = "success") {
    const cart = $('#cart');
    cart.addClass('cart-animate');
    setTimeout(() => cart.removeClass('cart-animate'), 600);

    let notify = $('#cart-notify');
    if (!notify.length) {
        notify = $(`<div id="cart-notify" class="alert alert-${type}" style="position:fixed;top:80px;right:20px;z-index:9999;display:none;min-width:250px;padding:12px 20px;font-size:14px;border-radius:6px;"></div>`).appendTo('body');
    }

    notify
        .removeClass('alert-success alert-warning alert-danger')
        .addClass(`alert-${type}`)
        .html(message)
        .stop(true, true).fadeIn(200).delay(1200).fadeOut(500);
}
// ✅ Конец Cart.js



$('#currency').change(function(){
    window.location = 'currency/change?curr=' + $(this).val();
});

$('.available select').on('change', function(){
    var modId = $(this).val(),
        color = $(this).find('option').filter(':selected').data('title'),
        price = $(this).find('option').filter(':selected').data('price'),
		quantity = $(this).find('option').filter(':selected').data('quantity'),
        basePrice = $('#base-price').data('base');
		baseQuantity = $('#base-quantity').data('basequant');
    if(price){
        $('#base-price').text(symboleLeft + price + symboleRight);
    }else{
        $('#base-price').text(symboleLeft + basePrice + symboleRight);
    }
	if(quantity){
		$('.detail-quantity').attr('data-max', quantity);
		$('.add-to-cart-link').attr('data-max', quantity);
		$('.detail-quantity').attr('max', quantity);
	}else{
		$('.detail-quantity').attr('data-max', baseQuantity);
		$('.add-to-cart-link').attr('data-max', baseQuantity);
		$('.detail-quantity').attr('max', baseQuantity);
	}
});

/* Zakladki */
$('body').on('click', '.btn-wishlist, .btn-wishlist2', function (e) {
  e.preventDefault();
  e.stopPropagation(); // чтобы клик по <i> не вызывал второй обработчик

  const btn = $(this);
  if (btn.data('busy')) return;        // защита от дабл-клика
  btn.data('busy', true);

  const product_id = Number(btn.attr('data-id') || 0);
  const mod_id     = Number(btn.attr('data-mod') || 0); // ← КЛЮЧЕВОЕ

  $.ajax({
    url: '/user/bookmarks',
    type: 'GET',
    data: { product_id, mod_id },      // ← всегда два параметра
    complete: function(){ btn.data('busy', false); },
    success: function(res){
      // можно без парсинга, просто переключаем вид
      if (btn.hasClass('btn-wishlist')) {
        btn.removeClass('btn-wishlist btn-soft-secondary')
           .addClass('btn-wishlist2 btn-soft-danger')
           .attr('title', 'Уже в избранном');
      } else {
        btn.removeClass('btn-wishlist2 btn-soft-danger')
           .addClass('btn-wishlist btn-soft-secondary')
           .attr('title', 'Добавить в избранное');
      }
    },
    error: function(){
      alert('Ошибка при изменении избранного');
    }
  });
});



/*Dostavka*/
    
$('form[action="cart/checkout"]').on('submit', function(e) {
    let isValid = true;

    // Удаляем старые сообщения
    $('.form-error').remove();
    $(this).find('.is-invalid').removeClass('is-invalid');

    $(this).find('select, input, textarea').each(function () {
        const $el = $(this);

        if ($el.is(':visible') && $el.prop('required') && !$el.val().trim()) {
            isValid = false;

            // Добавляем Bootstrap-подсветку и сообщение
            $el.addClass('is-invalid');

            // Если нет блока с сообщением об ошибке — добавляем
            if (!$el.next('.invalid-feedback').length) {
                $el.after(`<div class="invalid-feedback">Это поле обязательно для заполнения</div>`);
            }
        }
    });

    if (!isValid) {
        e.preventDefault();

        // Добавим общее сообщение
        if (!$('.form-error').length) {
            $(this).find('.product-cart').prepend(`
                <div class="form-error alert alert-danger mb-3">
                    Пожалуйста, заполните все обязательные поля перед отправкой формы.
                </div>
            `);
        }

        // Прокрутим к сообщению
        $('html, body').animate({
            scrollTop: $(".form-error").offset().top - 100
        }, 300);
    }
});

// Автоудаление ошибок при вводе
$('form[action="cart/checkout"]').on('input change', 'input, select, textarea', function() {
    if ($(this).val().trim()) {
        $(this).removeClass('is-invalid');
        $(this).next('.invalid-feedback').remove();

        // Если все ошибки исчезли — удалим общее сообщение
        if ($('.is-invalid').length === 0) {
            $('.form-error').fadeOut(200, function () { $(this).remove(); });
        }
    }
});

/*Dostavka*/



$(document).on('click','[data-toggle="class-toggle"]',function () {
	var $this = $(this);
	var target = $this.data("target");
	var sameTriggers = $this.data("same");
	var backdrop = $(this).data("backdrop");

	if ($(target).hasClass("active")) {
		$(target).removeClass("active");
		$(sameTriggers).removeClass("active");
		$this.removeClass("active");
		$('body').removeClass("overflow-hidden");
	} else {
		$(target).addClass("active");
		$this.addClass("active");
		if(backdrop == 'static'){
			$('body').addClass("overflow-hidden");
		}
	}
});

$('[data-toggle="aiz-side-menu"] a').each(function () {
	var pageUrl = window.location.href.split(/[?#]/)[0];
	if (this.href == pageUrl || $(this).hasClass("active")) {
		$(this).addClass("active");
		$(this).closest(".aiz-side-nav-item").addClass("mm-active");
		$(this)
			.closest(".level-2")
			.siblings("a")
			.addClass("level-2-active");
		$(this)
			.closest(".level-3")
			.siblings("a")
			.addClass("level-3-active");
	}
});

$('body').on('click', '.newsletter_checked', function(){
	var newsletter_id = $(this).data('newsletter_id');
	var checked = $(this).data('checked');
    $.ajax({
        url: '/user/addnewsletter',
        data: {newsletter_id: newsletter_id, checked: checked},
        type: 'GET',
        success: function(res){
			$('.form-newsletter').html(res);
		},
        error: function(){
            alert('Ошибка!');
        }
    });
});

$('body').on('click', '.switch-newsletter', function(){
	var checked = $(this).data('checked');
    $.ajax({
        url: '/user/deletenewsletter',
        data: {checked: checked},
        type: 'GET',
        success: function(res){
			$('.form-newsletter').html(res);
		},
        error: function(){
            alert('Ошибка!');
        }
    });
});





