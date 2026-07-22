<!--prdt-starts-->
<section class="py-5">
    <div class="container">
        <div class="d-flex align-items-start cab-inner">

			<div class="aiz-user-panel">
				<div class="card">
					<div class="card-header">
						<h5 class="mb-0 h6">Договор</h5>
					</div>
					<div class="card-body">
					<?php if(!empty($company)): ?>
						<div class="table-responsive">
							Номер договора: <?=$company->dogovor_number;?> от <?php echo \ishop\App::formatDate($company->data_dogovor); ?>
						</div>
					<?php else: ?>
						<p class="text-danger">Договор пока не заключён.</p>
					<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<!--product-end-->