<!--prdt-starts-->
<section class="py-5">
    <div class="container">
        <div class="d-flex align-items-start cab-inner">            
			<div class="aiz-user-panel">
				<div class="card">
					<div class="card-header">
						<h5 class="mb-0 h6"><?=$find->name;?></h5>
						<?php if($type["hide_date_post"] == "show") { ?>
							<div class="cont_info_data">
								<time datetime="<?=date("c", strtotime($find["date_post"]))?>"><?php echo \ishop\App::contdate($find["date_post"]); ?></time>
							</div>
						<?php } ?>
					</div>
					<div class="card-body">

						<div class="container">
							<div class="row">		
								<?php if(!empty($find)): ?>
								
									<div class="col-md-12">
										<div class="bg-light rounded-3">											
																														
												<div class="cont-inner">
													<?php if($find->img) { ?>
														<?php if($find->img_hide == "show") { ?>
															<div class="cont-img">
																<img src="images/contents/baseimg/<?=$find->img;?>" alt="" />
															</div>
														<?php } ?>
													<?php } ?>
													<div class="cont-desc">
														<?=$find->content;?>
													</div>
												</div>											
										</div>					
									</div>						
								<?php endif; ?>		
							</div>
						</div>	
					</div>
				</div>
			</div>
		</div>
	</div>									
</section>
<!--product-end-->
