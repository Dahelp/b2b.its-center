<?php
/** @var string $msg — можно передать сообщение из forbid() */
$msg = $msg ?? 'У вас нет прав для просмотра этой страницы.';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>403 — Доступ запрещён</title>
    <link rel="stylesheet" href="/adminlte/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="card card-outline card-danger">
        <div class="card-header text-center">
            <h1 class="h4 text-danger">Доступ закрыт!</h1>
        </div>
        <div class="card-body">
            <p class="login-box-msg"><?= h($msg) ?></p>
            <div class="text-center">
                <a href="<?= ADMIN; ?>" class="btn btn-primary">На главную</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
