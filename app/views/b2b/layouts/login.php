<!DOCTYPE html>
<html lang="ru">
<head>
	<meta name="csrf-token" content="<?= htmlspecialchars(\app\helpers\RequestGuard::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
	<base href="<?=PATH?>/">
	<meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>ИТС-Центр B2B продажи</title>
	<meta name="description" content="" />
	<meta name="keywords" content="" />
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" />
	<meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests"> 
	<link rel="icon" href="images/favicon.svg" type="image/svg" />
    <link rel="shortcut icon" href="images/favicon.svg" type="image/svg" />
	<link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/login.css" type="text/css" media="all" />	
	<meta name="geo.placename" content="ул. Комунальная, 26, стр. 2, Климовск, Московская область, Россия, 142184" />
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
<body class="hold-transition login-page">
	<?php if (!empty($error)): ?>
		<div class="alert alert-danger">
			<?php echo $error; ?>
		</div>
	<?php endif; ?>
<div id="root">
	<div class="app ant-layout">		
		<div class="ant-layout-content">
			<div class="Login ant-layout">
				<div class="Login__form">
					<div class="Login__logo">
						<img src="../images/logo.png" alt="ИТС-Центр">
					</div>
					<div class="ant-spin-nested-loading">
						<div class="ant-spin-container">
							
							<!-- Форма авторизации -->
							<div id="loginForm">
								<form action="/user/login" method="post">
									<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\app\helpers\RequestGuard::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
									<div class="ant-row Login__form-row">
										<span class="ant-input-affix-wrapper">
											<span class="ant-input-prefix"><i class="anticon anticon-user"></i></span>
											<input placeholder="E-mail пользователя" type="text" name="email" class="ant-input" id="loginEmail">
										</span>
									</div>
									<div class="ant-row Login__form-row">
										<span class="ant-input-affix-wrapper">
											<span class="ant-input-prefix"><i class="anticon anticon-lock"></i></span>
											<input placeholder="Пароль" type="password" name="password" class="ant-input" id="loginPassword">
										</span>
									</div>
									<div class="ant-row Login__form-row">
										<button disabled type="submit" class="ant-btn Login__form-button ant-btn-primary" id="loginButton">
											<span>Войти</span>
										</button>
									</div>
								</form>
								<div class="ant-divider ant-divider-horizontal ant-divider-with-text">
									<span class="ant-divider-inner-text">Забыли пароль?</span>
								</div>
								<div style="text-align: center;">
									<a href="#" id="showRecoverForm">Восстановление пароля</a>
								</div>
							</div>

							<!-- Форма восстановления пароля (скрыта по умолчанию) -->
							<div id="recoverForm" style="display: none;">
								<form action="/user/recover" method="post">
									<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\app\helpers\RequestGuard::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
									<div class="ant-row Login__form-row">
										<input type="email" name="email" placeholder="E-mail пользователя" class="ant-input" id="recoverEmail">
									</div>
									<br>
									<div class="ant-row Login__form-row">
										<button disabled type="submit" class="ant-btn Login__form-button ant-btn-primary" id="recoverButton">
											<span>Восстановить пароль</span>
										</button>
									</div>
								</form>
								<div class="ant-divider ant-divider-horizontal"></div>
								<div style="text-align: center;">
									<a href="#" id="showLoginForm">Вернуться к авторизации</a>
								</div>
							</div>

						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
	document.addEventListener('DOMContentLoaded', function() {
		const loginForm = document.getElementById('loginForm');
		const recoverForm = document.getElementById('recoverForm');
		const showRecoverForm = document.getElementById('showRecoverForm');
		const showLoginForm = document.getElementById('showLoginForm');

		const loginEmail = document.getElementById('loginEmail');
		const loginPassword = document.getElementById('loginPassword');
		const loginButton = document.getElementById('loginButton');

		const recoverEmail = document.getElementById('recoverEmail');
		const recoverButton = document.getElementById('recoverButton');

		// Функция проверки ввода для включения кнопки "Войти"
		function checkLoginInputs() {
			if (loginEmail.value.trim() !== '' && loginPassword.value.trim() !== '') {
				loginButton.removeAttribute('disabled');
			} else {
				loginButton.setAttribute('disabled', 'disabled');
			}
		}

		// Функция проверки ввода для включения кнопки "Восстановить пароль"
		function checkRecoverInputs() {
			if (recoverEmail.value.trim() !== '') {
				recoverButton.removeAttribute('disabled');
			} else {
				recoverButton.setAttribute('disabled', 'disabled');
			}
		}

		// Слушатели событий для авторизации
		loginEmail.addEventListener('input', checkLoginInputs);
		loginPassword.addEventListener('input', checkLoginInputs);

		// Слушатели событий для восстановления пароля
		recoverEmail.addEventListener('input', checkRecoverInputs);

		// Переключение на форму восстановления пароля
		showRecoverForm.addEventListener('click', function(event) {
			event.preventDefault();
			loginForm.style.display = 'none';
			recoverForm.style.display = 'block';
		});

		// Переключение обратно на форму авторизации
		showLoginForm.addEventListener('click', function(event) {
			event.preventDefault();
			recoverForm.style.display = 'none';
			loginForm.style.display = 'block';
		});
	});
</script>
<?php if (!empty($_GET['token'])): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            sessionStorage.setItem("reset_token", "<?php echo htmlspecialchars($_GET['token'], ENT_QUOTES, 'UTF-8'); ?>");
            window.history.replaceState({}, document.title, window.location.pathname);
            showResetPasswordForm();
        });
    </script>
<?php endif; ?>
<script>
	function showResetPasswordForm() {
    const loginForm = document.getElementById("loginForm");
    if (!loginForm) return; 

    loginForm.innerHTML = `
        <form id="resetPasswordForm">
            <div class="ant-row Login__form-row">
                <span class="ant-input-affix-wrapper">
                    <span class="ant-input-prefix"><i class="anticon anticon-lock"></i></span>
                    <input placeholder="Новый пароль" type="password" name="password" class="ant-input" id="resetPassword" autocomplete="new-password">
                </span>
            </div>
            <div class="ant-row Login__form-row">
                <button disabled type="submit" class="ant-btn Login__form-button ant-btn-primary" id="resetPasswordButton">
                    <span>Сохранить пароль</span>
                </button>
            </div>
        </form>
        <div class="ant-divider ant-divider-horizontal"></div>
        <div style="text-align: center;">
            <a href="#" onclick="showLoginForm(); return false;">Вернуться ко входу</a>
        </div>
    `;

    // Добавляем обработчик ввода для активации кнопки
    document.getElementById("resetPassword").addEventListener("input", checkResetPasswordInput);
    
    // Добавляем обработчик отправки формы
    document.getElementById("resetPasswordForm").addEventListener("submit", function(e) {
        e.preventDefault();
        submitNewPassword();
    });
}

// Функция активации кнопки при вводе пароля
function checkResetPasswordInput() {
    const resetPassword = document.getElementById("resetPassword").value;
    const resetPasswordButton = document.getElementById("resetPasswordButton");

    if (resetPassword.trim() !== "") {
        resetPasswordButton.removeAttribute("disabled");
    } else {
        resetPasswordButton.setAttribute("disabled", "disabled");
    }
}

function submitNewPassword() {
    const password = document.getElementById("resetPassword").value;
    const token = sessionStorage.getItem("reset_token");

    if (!password.trim()) {
        alert("Введите пароль!");
        return;
    }

    fetch("/user/recover-pass", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `password=${encodeURIComponent(password)}&token=${encodeURIComponent(token)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Пароль успешно изменён!");
            sessionStorage.removeItem("reset_token"); // Удаляем токен
            window.location.href = "/"; // Перенаправляем на главную для авторизации
        } else {
            alert(data.error);
        }
    })
    .catch(error => console.error("Ошибка:", error));
}

</script>
<script src="js/main-b2b.js"></script>
</body>
</html>
