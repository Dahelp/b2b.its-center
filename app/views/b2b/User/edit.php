<?php $user = \R::findOne('user', 'id = ?', [$_SESSION['b2buser']['id']]); ?>
<?php $company = \R::findOne('company', 'user_id = ?', [$_SESSION['b2buser']['id']]); 
if($company->tip == "1"){ $tip = "Розничная торговля";}
if($company->tip == "2"){ $tip = "Оптовая торговля";}
if($company->tip == "3"){ $tip = "Спец. торговля";}
?>
<!--prdt-starts-->
<section class="py-5">
    <div class="container">
        <div class="d-flex align-items-start cab-inner">

			<div class="aiz-user-panel">
				<div class="card">
					<div class="card-header">
						<h5 class="mb-0 h6">Профиль</h5>
					</div>
					<div class="card-body">
						<form action="user/edit" method="post" data-toggle="validator">
							<div class="box-body">
								<div class="form-group has-feedback mb-3">
									<label for="groups">Группа</label>
									<input type="text" class="form-control" name="name_groups" id="name_groups" value="B2B пользователь" disabled>
									<input type="hidden" class="form-control" name="groups" id="groups" value="5">
								</div>
								<div class="form-group has-feedback mb-3">
									<label for="tip">Тип взаимодействия</label>
									<input type="text" class="form-control" name="tip" id="tip" value="<?=$tip?>" disabled>									
								</div>
								<div class="form-group has-feedback mb-3">
									<label for="name">Имя</label>
									<input type="text" class="form-control" name="name" id="name" value="<?=$user->name?>" required>
									<span class="glyphicon form-control-feedback" aria-hidden="true"></span>
								</div>
								<div class="form-group has-feedback mb-3">
									<label for="email">Email</label>
									<input type="email" class="form-control" name="email" id="email" value="<?=$user->email?>" required>
									<span class="glyphicon form-control-feedback" aria-hidden="true"></span>
								</div>
								<div class="form-group has-feedback mb-3">
									<label for="email">Телефон</label>
									<input type="text" class="form-control" name="telefon" id="phone-input2" value="<?=$user->telefon?>" required>
									<span class="glyphicon form-control-feedback" aria-hidden="true"></span>
								</div>
								<div class="form-group mb-3">
									<label for="old_password">Старый пароль</label>
									<input type="password" class="form-control" name="old_password" id="old_password" placeholder="Введите старый пароль">
								</div>
								<div class="form-group mb-3">
									<label for="new_password">Новый пароль</label>
									<input type="password" class="form-control" name="new_password" id="new_password" placeholder="Введите новый пароль, если хотите его изменить">
								</div>
								<div class="form-group mb-3">
									<label for="confirm_password">Подтвердите пароль</label>
									<input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="Подтвердите пароль">
								</div>
							</div>
							<div class="box-footer">
								<button type="submit" class="btn btn-primary">Сохранить</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<!--product-end-->