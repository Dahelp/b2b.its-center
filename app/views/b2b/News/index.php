<!--prdt-starts-->
<section class="py-5">
    <div class="container">
        <div class="d-flex align-items-start cab-inner">            
			<div class="aiz-user-panel">
				<div class="card">
					<div class="card-header">
						<h5 class="mb-0 h6"><?=$type->name;?></h5>
					</div>
					<div class="card-body">

						<div class="container">
							<div class="row">
								<div class="cont-blok">
									<?php foreach($conts as $item) { ?>
										<div class="col-md-3 cont-one">
											<div class="cont_ht border border-grey">
												<div class="cont_blok_img">
													<?php if($item["img"] !="") { ?>
														<img src="https://its-center.ru/images/contents/baseimg/<?=rawurlencode(basename((string)$item["img"]))?>" alt="<?=htmlspecialchars($item["name"], ENT_QUOTES, 'UTF-8')?>" title="<?=htmlspecialchars($item["name"], ENT_QUOTES, 'UTF-8')?>" loading="lazy" onerror="this.onerror=null;this.src='/images/no_image.jpg';" />
													<?php } else { ?>
														<img src="/images/no_image.jpg" alt="" loading="lazy" />
													<?php } ?>
												</div>
												<div class="cont_info">
													<?php if($type["hide_date_post"] == "show") { ?>
														<div class="cont_info_data">
															<?php echo \ishop\App::contdate($item["date_post"]); ?>
														</div>
													<?php } ?>
													<div class="cont_info_name">
														<a href="<?=$type->param_url;?>/<?=$item["alias"];?>"><?=$item["name"];?></a>
													</div>
													<div class="cont_info_anons">
														<?php echo mb_strimwidth($item["anons"], 0, 200, "...");?>
													</div>
												</div>
											</div>
										</div>
									<?php } ?>
								</div>
								<div class="clearfix"></div>
								<div class="pb-4">                            
									<?php if($pagination->countPages > 1): ?>
										<?=$pagination;?>
									<?php endif; ?>
								</div>
							</div>
						</div>				
					</div>
				</div>
			</div>
		</div>
	</div>									
</section>
<!--product-end-->
