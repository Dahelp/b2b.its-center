<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
    $sessionPath = dirname(__DIR__) . '/storage/sessions';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0750, true);
    }
    session_save_path($sessionPath);
    session_name('b2b_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

$appEnv = getenv('APP_ENV') ?: 'production';
$isDebug = $appEnv === 'local' || $appEnv === 'development';
error_reporting($isDebug ? E_ALL : 0);
ini_set('display_errors', $isDebug ? '1' : '0');
ini_set('log_errors', '1');

// запрет прямого обращения
define('BASEPATH', TRUE);

if($_SERVER['REQUEST_URI'] == "/main") {
	header( "Location: /", TRUE, 301 );
	exit();
}

if (strpos($_SERVER['REQUEST_URI'], 'public')) {
	$public = str_replace("/public",'', $_SERVER['REQUEST_URI']);

	if ($public) {
		header("HTTP/1.1 301 Moved Permanently");
		header('Location: https://'.$_SERVER['SERVER_NAME'].''.$public.'');
		exit();
	}
}

// Убираем слеш в конце ссылок
$uri = preg_replace("/\?.*/i",'', $_SERVER['REQUEST_URI']);
 
if ((!strpos($uri, 'simpla'))  && (strlen($uri)>1)) {
  if (rtrim($uri,'/')!=$uri) {
    header("HTTP/1.1 301 Moved Permanently");
    header('Location: https://'.$_SERVER['SERVER_NAME'].str_replace($uri, rtrim($uri,'/'), $_SERVER['REQUEST_URI']));
    exit();    
  }
} 

require_once dirname(__DIR__) . '/config/init.php';
require_once LIBS . '/functions.php';
require_once CONF . '/routes.php';

new \ishop\App();

