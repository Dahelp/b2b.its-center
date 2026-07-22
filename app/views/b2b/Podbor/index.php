<!--prdt-starts-->
<section class="py-5">
    <div class="container">
        <div class="d-flex align-items-start cab-inner">
            
			<div class="aiz-user-panel">
				<div class="card">
					<div class="card-header">
						<h5 class="mb-0 h6"><?= $pdr_name ?? ($category['name'] ?? 'Подбор') ?></h5>
					</div>
					<div class="card-body">
						<?php if ((int)$main_category_id !== 37): ?>
							<section class="d-md-flex justify-content-between align-items-center pb-4">
								<div class="w_sidebar col-md-12 fltr">
									<?php new \app\widgets\filter\Filter($ids, null, '', $main_category_id); ?>
								</div>
							</section>

							<div id="sort-block" class="d-flex align-items-center col-md-12 pb-4 sort-inner hidden">
								<div class="sort-inner">
									<div class="sort-name">Сортировать по:</div>

									<span class="nav-link" id="nal">Наличию</span>
									<span class="nav-link" id="price">Цене</span>
									<span class="nav-link" id="rate">Рейтингу</span>
								</div>
							</div>
						<?php endif; ?> 
						<div class="prdt-top">
							<div class="col-md-12">
								<div id="products-list" class="product-one"></div>
								
							</div>
							<div class="clearfix"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<!--product-end-->
<?php if ((int)$main_category_id === 37): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var productsList = document.getElementById('products-list');

    if (!productsList) {
        return;
    }

    productsList.innerHTML = '<div class="text-center py-4">Загрузка услуг...</div>';

    fetch(window.location.href, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
        .then(function (response) {
            return response.text();
        })
        .then(function (html) {
            productsList.innerHTML = html;
        })
        .catch(function () {
            productsList.innerHTML = '<h3 class="text-center">Не удалось загрузить услуги...</h3>';
        });
});
</script>
<?php endif; ?>