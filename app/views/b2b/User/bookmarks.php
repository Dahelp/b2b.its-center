<?php $curr = \ishop\App::$app->getProperty('currency'); ?>

<!--prdt-starts-->
<section class="py-5">
    <div class="container">
        <div class="d-flex align-items-start cab-inner">

			<div class="aiz-user-panel">
				<div class="card">
					<div class="card-header">
						<h5 class="mb-0 h6">Закладки</h5>
					</div>
					<div class="card-body">
					<?php if($bookmarks): ?>
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
								<?php foreach($bookmarks as $item): ?>									
								<?php
									$mod_id   = isset($item['mod_id']) ? (int)$item['mod_id'] : 0;
									$cart_key = $mod_id ? "{$item['product_id']}-$mod_id" : $item['product_id'];
									$in_cart  = isset($_SESSION['cart'][$cart_key]);
									$q        = $in_cart ? (int)$_SESSION['cart'][$cart_key]['qty'] : 1;

									$rrs   = (float)($item['price_rrs'] ?? 0);
									$price = (float)($item['client_price'] ?? 0);
								?>
								<tr>
									<td class="footable-first-visible">
										<?php if(!empty($item['unload_img'])): ?>
											<img src="https://its-center.ru/images/product/unload/<?=$item['unload_img']?>" />
										<?php else: ?>
											<img src="https://its-center.ru/images/product/mini/<?=$item['img']?>" />
										<?php endif; ?>
									</td>

									<td><?=$item['article']?></td>

									<td>
										<a href="/product/<?=$item['alias']?>" target="_blank"><?=$item['name']?></a>
									</td>

									<td><?=$item['quantity']?></td>

									<!-- РРЦ -->
									<td class="c_price">
										
											<?=$curr['symbol_left']?> <?= ($rrs * $curr['value']) ?> <?=$curr['symbol_right']?>
										
									</td>

									<!-- Ваша цена -->
									<td class="c_price">
										
											<?=$curr['symbol_left']?> <?= ($price * $curr['value']) ?> <?=$curr['symbol_right']?>
										
									</td>

									<td class="btn_price btn-korz">
									<?php
										$curr   = \ishop\App::$app->getProperty('currency');
										$mod_id = isset($item['mod_id']) ? (int)$item['mod_id'] : 0;
										$cart_key = $mod_id ? "{$item['product_id']}-$mod_id" : $item['product_id'];
										$in_cart  = isset($_SESSION['cart'][$cart_key]);
										$q        = $in_cart ? (int)$_SESSION['cart'][$cart_key]['qty'] : 1;
									?>

									<?php if ($item['quantity'] > 0): ?>
										<div class="quantity-block my_quant-<?=$cart_key?>" style="display: <?=$in_cart ? 'inline-flex' : 'none'?>;">
											<button type="button" class="btn btn-outline-secondary quantity-arrow-minus btn-sm"
													data-id="<?=$item['product_id']?>" <?= $mod_id ? 'data-mod="'.$mod_id.'"' : '' ?>>-</button>
											<span class="qty-item">
												<input type="text" class="form-control form-control-sm qty-input qty-item-<?=$cart_key?>"
													data-id="<?=$item['product_id']?>" <?= $mod_id ? 'data-mod="'.$mod_id.'"' : '' ?>
													value="<?=$q?>" min="1" max="<?=$item['quantity']?>">
											</span>
											<button type="button" class="btn btn-outline-secondary quantity-arrow-plus btn-sm"
													data-id="<?=$item['product_id']?>" <?= $mod_id ? 'data-mod="'.$mod_id.'"' : '' ?>>+</button>
										</div>

										<div class="btn btn-green-back korzina-<?=$cart_key?>" style="display: <?=$in_cart ? 'none' : 'inline-block'?>;">
											<a href="#" class="add-to-cart-link"
											data-id="<?=$item['product_id']?>" data-max="<?=$item['quantity']?>"
											<?= $mod_id ? 'data-mod="'.$mod_id.'"' : '' ?>>
											В корзину
											</a>
										</div>
									<?php else: ?>
										<?php if ($item['stock_status_id'] == 0): ?>
											<div class="text-dark">Нет в наличии</div>
										<?php elseif ($item['stock_status_id'] == 2): ?>
											<div class="text-dark">Под заказ</div>
										<?php elseif ($item['stock_status_id'] == 3): ?>
											<div class="text-dark">Ожидается поступление</div>
										<?php else: ?>
											<div class="badge bg-secondary">Нет данных</div>
										<?php endif; ?>
									<?php endif; ?>
								</td>

								<!-- Последний столбец: просмотр + удалить (иконка норм) -->
								<td class="btn_price text-right footable-last-visible">
									<div class="d-inline-flex align-items-center gap-2">
										<a href="#"
										class="btn btn-soft-info btn-icon btn-circle btn-sm view-product"
										data-bs-toggle="modal"
										data-bs-target="#productModal"
										data-id="<?=$item['product_id']?>"
										title="Просмотр товара">
											<i class="far fa-eye"></i>
										</a>

										<form method="post" action="/user/bookmarks-delete" class="d-inline">
										<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\app\helpers\RequestGuard::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
										<button type="submit" name="id" value="<?= (int)$item['id'] ?>"
										class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete"
										title="Удалить закладку">
											<i class="fas fa-trash"></i>
										</button>
										</form>
									</div>
								</td>
								</tr>
							<?php endforeach; ?>


								</tbody>															
							</table>
						</div>
						<div class="clearfix"></div>
						<div class="text-center">                            
							<?php if($pagination->countPages > 1): ?>
								<?=$pagination;?>
							<?php endif; ?>
						</div>
					<?php else: ?>
						<p class="text-danger">Вы пока не добавляли товары в закладки.</p>
					<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<!--product-end-->
