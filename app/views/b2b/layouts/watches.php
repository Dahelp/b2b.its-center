<?php $b2buser = \ishop\App::$app->getProperty('b2buser'); ?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<base href="<?=PATH?>/">
	<meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />	
	<?php if($this->route["controller"] == "Product") {
	if(!empty($product->hide =="lock")) { ?>
	<meta name="robots" content="noindex, nofollow" />
	<?php }else{ ?>
	<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
	<?php } ?>
	<?php }else{ ?>
	<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
	<?php } ?>
	<meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests"> 
	<link rel="icon" href="images/favicon.svg" type="image/svg" />
    <link rel="shortcut icon" href="images/favicon.svg" type="image/svg" />
    <?=$this->getMeta(); ?>	
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />
	<meta name="yandex-verification" content="881bb639ff32cffe" />
	<meta name="google-site-verification" content="YTzQMjO51p1Hu9bD8voK6ug0RLNL5sswqqgk55ECgV4" />
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">    
    <link rel="stylesheet" href="css/slider.css" type="text/css" media="screen" />
	<link rel="stylesheet" href="css/flexslider.css" type="text/css" media="all" />
	<link rel="stylesheet" href="css/aiz-core.css" type="text/css" media="all" />
    <link rel="stylesheet" href="css/style.css" type="text/css" media="all" />
	<link rel="stylesheet" href="public/adminlte/plugins/fontawesome-free/css/all.min.css" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<link rel="stylesheet" href="public/adminlte/plugins/select2/css/select2.min.css" />
	<link rel="stylesheet" href="public/adminlte/plugins/select2-bootstrap5-theme/select2-bootstrap-5-theme.min.css" />	
	<link rel="stylesheet" href="css/swiper-bundle.min.css" />

	<script src="js/imask.min.js"></script><!-- telefon -->
	<meta name="geo.placename" content="ул. Комунальная, 26, стр. 2, г.Подольск, мкр. Климовск, Московская область, Россия, 142184" />
	<meta name="geo.position" content="55.360413;37.562371" />
	<meta name="geo.region" content="RU-Московская область" />
	<meta name="ICBM" content="55.360413, 37.562371" />
	<?php $options = \R::getAll("SELECT znachenie, alt_name FROM options WHERE tip = 'Оформление'");
		foreach($options as $option){
			$znachenie = $option["znachenie"];
			$alt_name = $option["alt_name"];
			eval('$$alt_name = "$znachenie";');
		}

	?>
		<style type="text/css">	
			:root {
				--body: <?php if(!empty($body_background)) { echo "".$body_background.""; }else{ echo "#fff"; } ?>;
				--category: <?php if(!empty($font_background)) { echo "".$font_background.""; }else{ echo "#f2f3f8"; } ?>;
				--footer: <?php if(!empty($main_background)) { echo "".$main_background.""; }else{ echo "#2d2d39"; } ?>;
				--soft-footer: #a9bbcf;
				--a: <?php if(!empty($a_background)) { echo "".$a_background.""; }else{ echo "#000"; } ?>;
				--blue: #007bff;
				--indigo: #6610f2;
				--purple: #6f42c1;
				--pink: #e83e8c;
				--red: #dc3545;
				--orange: #fd7e14;
				--yellow: #ffc107;
				--green: #28a745;
				--teal: #20c997;
				--cyan: #17a2b8;
				--white: #fff;
				--gray: #6c757d;
				--gray-dark: #343a40;
				--primary: #C0392B;
				--hov-primary: #C0392B;
				--soft-primary: rgba(230,46,4,0.15);
				--secondary: #8f97ab;
				--soft-secondary: rgba(143, 151, 171, 0.15);
				--success: #198754;
				--soft-success: rgba(10, 187, 117, 0.15);
				--info: #25bcf1;
				--soft-info: rgba(37, 188, 241, 0.15);
				--warning: #ffc519;
				--soft-warning: rgba(255, 197, 25, 0.15);
				--danger: <?php if(!empty($knp_background)) { echo "".$knp_background.""; }else{ echo "#ef486a"; } ?>;
				--soft-danger: rgba(239, 72, 106, 0.15);
				--light: #f2f3f8;
				--dark: #111723;
				--soft-dark: rgba(42, 50, 66, 0.15);
				--breakpoint-xs: 0;
				--breakpoint-sm: 576px;
				--breakpoint-md: 768px;
				--breakpoint-lg: 992px;
				--breakpoint-xl: 1200px;
				--font-family-sans-serif: -apple-system, BlinkMacSystemFont, "Segoe UI",
					Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif,
					"Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol",
					"Noto Color Emoji";
				--font-family-monospace: SFMono-Regular, Menlo, Monaco, Consolas,
					"Liberation Mono", "Courier New", monospace;
			}
		</style>		
</head>
<body>
<div class="collapse navbar-collapse" id="navbarNavAltMarkup">
	<div class="mbl-menu">
		<div class="mbl-close">
			<div class="mbl-cl-icon"><button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation"><i class="fal fa-times"></i></button></div>
		</div>
		<div class="mbl-info">
			<ul>
				<li><a href="user/orders" title="">Заказы</a></li>
				<li><a href="" title="">Поиск товаров и услуг</a></li>
				<li><a href="" title="">Инфо</a></li>
				<li><a href="" title="">Полезные инструменты</a></li>				
			</ul>
		</div>
	</div>
</div>
<!--top-header-->
<div class="top-header">
	<header id="masthead" class="site-header">
		<div class="container">	
			<div class="top-header-main">
				<div class="storefront-primary-navigation">
					<div class="col-md-12 menu d-flex">  
						<div class=" menu-center">
							<div class="drop">
								<nav class="navbar navbar-expand-lg navbar-light bg-light">
									<div class="container">
										<div class="menu-inner navbar-nav">
											<a class="nav-link" href="/user/cabinet">Главная</a>
											<div class="nav-item dropdown">
												<a class="nav-link dropdown-toggle" href="#" id="ordersDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Заказы</a>
												<ul class="dropdown-menu" aria-labelledby="ordersDropdown">
													<li><a class="dropdown-item" href="/user/orders">Все заказы</a></li>
													<li><a class="dropdown-item" href="/user/orders?status=1">Принят</a></li>
													<li><a class="dropdown-item" href="/user/orders?status=2">Обрабатывается</a></li>
													<li><a class="dropdown-item" href="/user/orders?status=3">Ожидает оплаты</a></li>
													<li><a class="dropdown-item" href="/user/orders?status=4">Оплачен</a></li>
													<li><a class="dropdown-item" href="/user/orders?status=5">Доставляется</a></li>
													<li><a class="dropdown-item" href="/user/orders?status=6">Получен</a></li>
													<li><a class="dropdown-item" href="/user/orders?status=7">Отменён</a></li>
												</ul>
											</div>

											<div class="nav-item dropdown">
												<a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
													Поиск товаров и услуг
												</a>
												<ul class="dropdown-menu" aria-labelledby="searchDropdown">
													<li><a class="dropdown-item" href="/podbor/shiny">Шины</a></li>
													<li><a class="dropdown-item" href="/podbor/diski">Диски</a></li>
													<li><a class="dropdown-item" href="/podbor/kamery-i-obodnye-lenty">Камеры, ободные ленты</a></li>
													<li><a class="dropdown-item" href="/podbor/filtry">Фильтры</a></li>
													<li><a class="dropdown-item" href="/podbor/uslugi">Услуги</a></li>
												</ul>
											</div>
																					
											<div class="nav-item dropdown">
												<a class="nav-link dropdown-toggle" href="#" id="infoDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
													Инфо
												</a>
												<ul class="dropdown-menu" aria-labelledby="infoDropdown">
													<li><a class="dropdown-item" href="/news">Новости</a></li>
												</ul>
											</div>

											<div class="nav-item dropdown">
												<a class="nav-link dropdown-toggle" href="#" id="toolsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
													Полезные инструменты
												</a>
												<ul class="dropdown-menu" aria-labelledby="toolsDropdown">
													<li><a class="dropdown-item" href="/user/bookmarks">Закладки</a></li>
													<li><a class="dropdown-item" href="/user/pricelist">Прайс лист</a></li>
													<li><a class="dropdown-item" href="/user/newsletter">Подписки</a></li>
												</ul>
											</div>
											
											
											<div class="nav-item dropdown">
												<a class="nav-link dropdown-toggle" href="#" id="infoDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
													Личный кабинет
												</a>
												<ul class="dropdown-menu" aria-labelledby="infoDropdown">
													<li><a class="dropdown-item" href="/user/edit">Персональные данные</a></li>
													<li><a class="dropdown-item" href="/user/company">Компания</a></li>
													<li><a class="dropdown-item" href="/user/dogovor">Договор</a></li>
												</ul>
											</div>
											
										</div>
									</div>
								</nav>
							</div>
						</div><!-- menu2 -->
						
						<div class="menu-right d-flex justify-content-end align-items-center gap-3">
							<?php if (!empty($b2buser) && !empty($b2buser['id'])): ?>
								<?php $comp = \R::findOne("company", "user_id = ?", [$b2buser['id']]); ?>
								<?php if ($comp): ?>
									<div class="user-comp">
										<span><?= $comp["comp_short_name"] ?></span>
									</div>
								<?php endif; ?>
								<?php $cartQty = $_SESSION['cart.qty'] ?? 0; ?>
								<div id="cart" class="btn-group">
									<div class="btn-n0">
										<i class="fas fa-shopping-cart"></i>
										<a href="/cart/show" class="position-relative">
											Корзина
											<?php if ($cartQty > 0): ?>
												<span id="cart-total" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
													<?= $cartQty ?>
												</span>
											<?php else: ?>
												<span id="cart-total" class="d-none"></span>
											<?php endif; ?>
										</a>
									</div>
								</div>
								<div class="user-auth text-end">
									<a href="<?= PATH ?>/user/logout" title=""><span>Выход</span></a>
								</div>
							<?php endif; ?>
						</div>              
					</div>
				</div><!-- blk2 -->
				<div class="col-md-12 str_logo">
					<div class="col-md-3 ftr-blk-left">
						<div class="drop">
							<div id="logo">			
								<?php if($this->route["controller"] != "Main") { ?><a href="/"><?php }else {} ?>							
									<img src="../images/logo.png" title="ИТС-Центр" alt="ИТС-Центр" class="img-responsive">
								<?php if($this->route["controller"] != "Main") { ?></a><?php }else {} ?>
							</div>
							<div class="clearfix"></div>
						</div>
					</div><!-- blk1 -->
					<div class="col-md-6 ftr-blk-center">
						<!--<div class="text-center pt-1"><a href="https://its-center.ru/promo/chernaya-pyatnica-do-novogo-goda" title="Чёрная пятница"><img src="../images/black-friday.jpg" alt="" title=""></a></div>-->
					</div><!-- blk2 -->
					<div class="col-md-3 ftr-blk-right">
						<div class="drop">
							<?php $tell_zv = \ishop\App::options('option_telefon'); ?>
							<div class="tel-navbar">
								<div class="tel"><span class="tel-icon"><i class="fas fa-phone fa-flip-horizontal"></i></span> <span class="tel-inner"><?=$tell_zv?></span></div><div class="tel-priem">Приём звонков: Пн-Пт 9:00 - 17:00</div>
							</div>
							<div class="m-tel">
								<a href="tel:<?php $telefon=str_replace("(","",$tell_zv); $telefon=str_replace(")","",$telefon); $telefon=str_replace(" ","",$telefon); $telefon=str_replace("-","",$telefon); echo "$telefon";	?>">
									<i class="fas fa-phone"></i>
								</a>
							</div>
							<div class="menu-inner-navbar">
								<nav class="navbar navbar-expand-lg navbar-light">																	
									<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
										<span class="navbar-toggler-icon"></span>
									</button>								
								</nav>							
							</div>
						</div>					
					</div><!-- blk3 -->
				</div>	
				
			</div>
		</div>
	</header>
	<!--top-header-->
	<div class="content">
		<div class="container">
			<div class="row">			
				<div class="col-md-12">
					<noindex>
					<?php if(isset($_SESSION['error'])): ?>
						<div class="alert alert-danger">
							<?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
						</div>
					<?php endif; ?>					
					<?php if(isset($_SESSION['success'])): ?>
						<div class="alert alert-success">
							<?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
						</div>
					<?php endif; ?>
					</noindex>
				</div>
			</div>
		</div>
		<?php if(!empty($content)) { echo $content; } ?>
	</div>
</div> <!-- Закрытие .content -->

<!--footer-starts-->
<div class="footer-inner site-footer">
	<div class="container">
		<div class="row">
			<div class="col-lg-7">
				<div class="copyright">B2B ИТС-Центр © 2007- <?php $year = date("Y"); echo $year; ?> <span>Шины Диски Фильтры</span></div>
			</div>
			<div class="col-lg-5 row copyright">
				
			</div>	
		</div>
	</div>
</div>
             
<!--footer-end-->

<!-- panel bottom -->
<div class="aiz-mobile-bottom-nav d-xl-none fixed-bottom bg-white shadow-lg border-top rounded-top" style="box-shadow: 0px -1px 10px rgb(0 0 0 / 15%)!important; ">
    <div class="row align-items-center gutters-5">
        <div class="col">
            <a href="/user/cabinet" class="text-reset d-block text-center pb-2 pt-3">
                <i class="fas fa-home-alt fs-20 opacity-60 "></i>
                <span class="d-block fs-10 fw-600 opacity-60 ">Главная</span>
            </a>
        </div>
        <div class="col">
            <a href="/user/orders" class="text-reset d-block text-center pb-2 pt-3">
                <i class="fas fa-list fs-20 opacity-60 opacity-100 text-danger"></i>
                <span class="d-block fs-10 fw-600 opacity-60 opacity-100 fw-600">Заказы</span>
            </a>
        </div>
        <div class="col-auto">
            <a href="cart/show" onclick="getCart(); return false;" class="text-reset d-block text-center pb-2 pt-3">
                <span class="align-items-center bg-danger border border-white border-width-4 d-flex justify-content-center position-relative rounded-circle size-50px" style="margin-top: -33px;box-shadow: 0px -5px 10px rgb(0 0 0 / 15%);border-color: #fff !important;">
                    <i class="fas fa-shopping-bag la-2x text-white"></i>
                </span>
                <span class="d-block mt-1 fs-10 fw-600 opacity-60 ">
                    <?php if(!empty($_SESSION['cart'])): ?>								
						Корзина (<span class="simpleCart_qty" id="cart-total"><?=$_SESSION['cart.qty']?></span>)						
					<?php else: ?>
						Корзина (<span class="simpleCart_qty" id="cart-total">0</span>)
					<?php endif; ?>
                </span>
            </a>
        </div>
        <div class="col">
            <a href="user/notifications" class="text-reset d-block text-center pb-2 pt-3">
                <span class="d-inline-block position-relative px-2">
                    <i class="fas fa-bell fs-20 opacity-60 "></i>
                                    </span>
                <span class="d-block fs-10 fw-600 opacity-60 ">Сообщения</span>
            </a>
        </div>
        <div class="col">
			<?php if(!empty($_SESSION['b2buser']['id'])): ?>
				<a href="javascript:void(0)" class="text-reset d-block text-center pb-2 pt-3 mobile-side-nav-thumb" data-toggle="class-toggle" data-backdrop="static" data-target=".aiz-mobile-side-nav">
			<?php else: ?>
				<a data-toggle="modal" data-target="#Modallogin" href="javascript:;" class="text-reset d-block text-center pb-2 pt-3">		
			<?php endif; ?>
                <span class="d-block mx-auto">
                    <img src="images/avatar-place.png" class="rounded-circle size-20px">
                </span>
                <span class="d-block fs-10 fw-600 opacity-60">Профиль</span>
            </a>
        </div>
    </div>
</div>
<!-- /panel bottom -->

<!-- Modal korzina -->
<div class="modal fade" id="exampleModalLive" tabindex="-1"  role="dialog" aria-labelledby="exampleModalLiveLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
            <div class="modal-header">
				<h5 class="modal-title" id="exampleModalLiveLabel">Корзина</h5>
				<button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
			</div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Продолжить покупки</button>
                <a href="cart/view" type="button" class="btn btn-danger">Оформить заказ</a>
                <button type="button" class="btn btn-primary" onclick="clearCart()">Очистить корзину</button>
            </div>
        </div>
  </div>
</div>

<!-- Modal для просмотра товара -->

<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="productModalLabel">Просмотр товара</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
      </div>
      <div class="modal-body" id="productModalBody">
        <div class="text-center">
			<?php foreach($product as $item) { ?>
				<div class=""><?=$item["name"]?></div>
			<?php } ?>
		</div>
      </div>
    </div>
  </div>
</div>


<div class="preloader"><img src="images/ring.svg" alt=""></div>

<?php $curr = \ishop\App::$app->getProperty('currency'); ?>
<script>
    var path = '<?=PATH;?>',
        course = <?=$curr['value'];?>,
        symboleLeft = '<?=$curr['symbol_left'];?>',
        symboleRight = '<?=$curr['symbol_right'];?>';
</script>
<script src="js/jquery-1.11.0.min.js"></script>
<script src="js/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="public/adminlte/plugins/select2/js/select2.full.min.js"></script>
<script src="public/adminlte/plugins/select2/js/i18n/ru.js"></script>

<script src="js/swiper-bundle.min.js"></script>
<script>
$(document).ready(function(){
     $(window).scroll(function () {
            if ($(this).scrollTop() > 50) {
                $('#back-to-top').fadeIn();
            } else {
                $('#back-to-top').fadeOut();
            }
        });
        // scroll body to 0px on click
        $('#back-to-top').click(function () {
           
            $('body,html').animate({
                scrollTop: 0
            }, 500);
            
        });
        
});
</script>

<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
<script>
	Fancybox.bind('[data-fancybox="gallery"]', {
	Thumbs : {
		type: "classic"
	}
	});    
</script>	
<script src="/js/main.js?v=<?= time() ?>"></script>
<!--End-slider-script-->
<!-- upTop -->  
<div class="btn btn-danger back-to-top hidden-xs" id="back-to-top" role="button" data-toggle="tooltip" data-placement="left"><i class="fas fa-arrow-up"></i></div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const headerHeight = 42; // высота .storefront-primary-navigation
    let fixedHeader = null;

    function cloneTableHeader() {
        const table = document.querySelector(".casters-block table.tbl_podbor");
        if (!table) return;

        const originalThead = table.querySelector("thead");
        if (!originalThead) return;

        // Удалить старую фиксированную шапку, если есть
        const existing = document.querySelector(".fixed-table-header");
        if (existing) existing.remove();

        // Клонируем заголовок
        const clone = originalThead.cloneNode(true);

        // Создаём обёртку
        const wrapper = document.createElement("div");
        wrapper.className = "fixed-table-header";

        // Создаём таблицу и вставляем клон
        const fixedTable = document.createElement("table");
        fixedTable.appendChild(clone);
        wrapper.appendChild(fixedTable);

        // Добавляем в body
        document.body.appendChild(wrapper);

        // Копируем точную ширину колонок
        const originalThs = originalThead.querySelectorAll("th");
        const clonedThs = clone.querySelectorAll("th");

        originalThs.forEach((th, index) => {
            const width = th.getBoundingClientRect().width + "px";
            if (clonedThs[index]) {
                clonedThs[index].style.width = width;
            }
        });

        fixedHeader = wrapper;
    }

    function updateStickyHeader() {
		const scrollTop = window.scrollY;
		const table = document.querySelector(".casters-block table.tbl_podbor");

		if (!table) return;

		if (scrollTop > 370) {
			if (!fixedHeader) {
				cloneTableHeader();
				table.classList.add('table-hide-thead'); // ⬅️ Скрываем оригинальный заголовок
			}
		} else {
			if (fixedHeader) {
				fixedHeader.remove();
				fixedHeader = null;
				table.classList.remove('table-hide-thead'); // ⬅️ Показываем обратно
			}
		}
	}


    // следим за загрузкой таба через ajax
    const observer = new MutationObserver(() => {
        const tabList = document.querySelector('.tab-list');
        if (tabList && !fixedHeader) {
            updateStickyHeader();
        }
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
    });

    window.addEventListener("scroll", updateStickyHeader);
});
</script>

</body>
</html>
