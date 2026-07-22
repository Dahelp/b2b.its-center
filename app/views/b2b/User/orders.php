<?php $curr = \ishop\App::$app->getProperty('currency'); ?>

<!--prdt-starts-->
<section class="py-5">
    <div class="container">
        <div class="d-flex align-items-start cab-inner">

			<div class="aiz-user-panel">
				<div class="card">
					<div class="card-header">
						<h5 class="col-md-4 mb-0 h6">Заказы</h5>
						<form method="get" class="col-md-3">
							<div class="form-group row">
								<div class="col-md-6 pt-2">
									<span for="status">Фильтр по статусу:</span>
								</div>
								<div class="col-md-6">
									<select name="status" id="status" class="form-control" onchange="this.form.submit()">
										<option value="">Все заказы</option>
										<option value="1" <?= (isset($status) && $status == 1) ? 'selected' : '' ?>>Новый</option>
										<option value="2" <?= (isset($status) && $status == 2) ? 'selected' : '' ?>>Обрабатывается</option>
										<option value="3" <?= (isset($status) && $status == 3) ? 'selected' : '' ?>>Ожидает оплаты</option>
										<option value="4" <?= (isset($status) && $status == 4) ? 'selected' : '' ?>>Оплачен</option>
										<option value="5" <?= (isset($status) && $status == 5) ? 'selected' : '' ?>>Доставляется</option>
										<option value="6" <?= (isset($status) && $status == 6) ? 'selected' : '' ?>>Получен</option>
										<option value="7" <?= (isset($status) && $status == 7) ? 'selected' : '' ?>>Отменён</option>
									</select>
								</div>
							</div>
						</form>
					</div>
					<div class="card-body">
						<?php if (!empty($orders)): ?>
							<div class="table-responsive">
								<table class="table aiz-table mb-0 footable footable-1 breakpoint-xl orders-table">
									<thead>
									<tr class="footable-header">
										<th class="footable-first-visible" style="display: table-cell;">Номер</th>
										<th data-breakpoints="md" style="display: table-cell;">Дата</th>
										<th data-breakpoints="md" style="display: table-cell;">Статус</th>
										<th style="display: table-cell;">Сумма</th>									
										<th class="text-right footable-last-visible" style="display: table-cell;">Действия</th>
									</tr>
									</thead>
									<tbody>
									<?php foreach($orders as $order): ?>
										<?php $status = \R::findOne('order_status', 'id = ?', [$order['status']]);
										if($order['status'] == '7'){
											$class = 'badge-danger';										
										}
										elseif($order['status'] == '1'){
											$class = 'badge-info';										
										}elseif($order['status'] == '2'){
											$class = 'badge-success';										
										}else{										
											$class = 'badge-secondary';										
										}
										$createdToday = (date('Y-m-d') == date('Y-m-d', strtotime($order['date'])));
										$needsRetry = empty($order['inv']);
										?>
										<tr>
											<td class="footable-first-visible" style="display: table-cell;">												
													<?= $order["inv"] ?: '<span class="text-danger">Не создан</span>'; ?>												
											</td>
											<td style="display: table-cell;"><?= date('d.m.Y', strtotime($order['date'])) ?></td>
											<td style="display: table-cell;">
											<?php if($createdToday && $needsRetry): ?>													
											<form method="post" action="/user/retry1c" class="d-inline">
												<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\app\helpers\RequestGuard::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
												<button type="submit" name="id" value="<?= (int)$order['id'] ?>" class="badge badge-inline badge-warning border-0">Повторить отправку</button>
											</form>
											<?php else: ?>
												<span class="badge badge-inline <?=$class;?>"><?=$status['status_name'];?></span>
											<?php endif; ?>	
											</td>
											<td style="display: table-cell;">
												<?=$curr['symbol_left'];?>
												<?= number_format((float)$order["sum"], 2, '.', ' '); ?>
												<?=$curr['symbol_right'];?>
											</td>
											<td class="btn_price text-right footable-last-visible" style="display: table-cell;">												
												<a href="user/order?id=<?=$order["id"];?>" class="btn btn-soft-info btn-icon btn-circle btn-sm" title="Детали заказа">
													<i class="far fa-eye"></i>
												</a>																					
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
							<p class="text-danger">Вы пока не совершали заказов.</p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<!--product-end-->
