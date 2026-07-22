<?php

namespace ishop\base;

abstract class Controller{

    public $route;
    public $controller;
    public $model;
    public $view;
    public $prefix;
    public $layout;
    public $data = [];
    public $meta = ['title' => '', 'desc' => '', 'keywords' => ''];

    public function __construct($route){
        $this->route = $route;
        $this->controller = $route['controller'];
        $this->model = $route['controller'];
        $this->view = $route['action'];
        $this->prefix = $route['prefix'];
    }

    public function getView(){
        $viewObject = new View($this->route, $this->layout, $this->view, $this->meta);
        $viewObject->render($this->data);
    }

    public function set($data){
        $this->data = $data;
    }

    public function setMeta($title = '', $desc = '', $keywords = '', $shop_name = '', $shop_img = '', $shop_url = ''){
        $this->meta['title'] = h($title);
        $this->meta['desc'] = h($desc);
        $this->meta['keywords'] = h($keywords);
        $this->meta['shop_name'] = h($shop_name);
        $this->meta['shop_img'] = h($shop_img);
        $this->meta['shop_url'] = h($shop_url);
    }

    public function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    public function loadView($view, $vars = []){
        extract($vars);
        require APP . "/views/" . TEMPLATE . "/{$this->prefix}{$this->controller}/{$view}.php";
        die;
    }

    public function loadViewAdmin($view, $vars = []){
        extract($vars);
        require APP . "/views/" . TEMPLATE . "/admin/{$this->prefix}{$this->controller}/{$view}.php";
        die;
    }

    /* ====== ДОБАВЛЕНО: Guard/CSRF ====== */

    /** 403 + простой вывод ошибки (добавьте шаблон errors/403.php при желании) */
    protected function forbid(int $code = 403, string $message = 'Доступ запрещён'): void {
        http_response_code($code);
        // Если есть общий шаблон ошибки:
        $tpl = APP . "/views/errors/403.php";
        if (is_file($tpl)) {
            $msg = $message; // переменная для шаблона, если надо
            require $tpl;
        } else {
            echo $message;
        }
        exit;
    }

    /** Требует авторизацию как админ (groups = 1) */
    protected function requireAdmin(): void {
        $group = (int)($_SESSION['b2buser']['groups'] ?? 0);
        $uid   = (int)($_SESSION['b2buser']['id'] ?? 0);
        if ($uid <= 0 || $group !== 1) {
            $this->forbid(403, 'Доступ закрыт: только для администратора.');
        }
    }

    /** Гибкая проверка по ролям */
    protected function requireRole(array $allowedGroups): void {
        $group = (int)($_SESSION['b2buser']['groups'] ?? 0);
        $uid   = (int)($_SESSION['b2buser']['id'] ?? 0);
        if ($uid <= 0 || !in_array($group, $allowedGroups, true)) {
            $this->forbid(403, 'Доступ закрыт.');
        }
    }

    /** Утилита: это POST? */
    protected function isPost(): bool {
        return (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');
    }

    /** Выдать/закэшировать CSRF токен в сессии */
    protected function csrfToken(): string {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    /** Проверка CSRF */
    protected function verifyCsrf(?string $token): bool {
        return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
    }
}
