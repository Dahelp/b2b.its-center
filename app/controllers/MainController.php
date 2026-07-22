<?php

namespace app\controllers;

use ishop\Cache;
use ishop\App;

class MainController extends AppController {

    public $layout = 'login';

    public function indexAction() {
        // 🔒 Проверка: если пользователь уже авторизован — редирект в личный кабинет
        if (isset($_SESSION['b2buser']) && is_array($_SESSION['b2buser'])) {
            redirect('/user/cabinet');
            exit;
        }

        // Получаем SEO-настройки как массивы (без Beans)
        $main_title = \R::getRow("SELECT * FROM options WHERE tip = 'seo' AND alt_name = 'option_name' LIMIT 1");
        $main_desc = \R::getRow("SELECT * FROM options WHERE tip = 'seo' AND alt_name = 'option_description' LIMIT 1");
        $main_keywords = \R::getRow("SELECT * FROM options WHERE tip = 'seo' AND alt_name = 'option_keywords' LIMIT 1");

        // SEO
        $path_controller = !empty($this->route["controller"]) && $this->route["controller"] != "Main"
            ? "/" . mb_strtolower($this->route["controller"])
            : "";
        $path_alias = isset($this->route["alias"]) ? "/" . $this->route["alias"] : "";

        $this->setMeta(
            $main_title['znachenie'] ?? '',
            $main_desc['znachenie'] ?? '',
            $main_keywords['znachenie'] ?? '',
            App::$app->getProperty('shop_name'),
            PATH . '/images/' . App::$app->getProperty('og_logo'),
            PATH . $path_controller . $path_alias
        );

        $this->set(compact('main_title', 'main_desc', 'main_keywords'));
    }
}

