<?php

namespace app\controllers;

use app\models\AppModel;
use app\widgets\currency\Currency;
use ishop\App;
use ishop\base\Controller;
use ishop\Cache;

class AppController extends Controller {

    public function __construct($route) {
        parent::__construct($route);

        $routeKey = strtolower((string)($route['controller'] ?? '')) . ':' . strtolower((string)($route['action'] ?? 'index'));
        $publicRoutes = [
            'main:index',
            'user:login',
            'user:recover',
            'user:recoverpass',
            'api:savemarks',
        ];

        if (!in_array($routeKey, $publicRoutes, true) && empty($_SESSION['b2buser']['id'])) {
            $isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
                || str_contains(strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json');

            if ($isAjax) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => 'Authentication required'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            redirect('/');
        }

        new AppModel();

        App::$app->setProperty('currencies', Currency::getCurrencies());
        App::$app->setProperty('currency', Currency::getCurrency(App::$app->getProperty('currencies')));
        App::$app->setProperty('cats', self::cacheCategory());

        // 🔁 Прокидываем b2buser из сессии в шаблон
        if (isset($_SESSION['b2buser'])) {
            App::$app->setProperty('b2buser', $_SESSION['b2buser']);
        }
    }

    public static function cacheCategory() {
        $cache = Cache::instance();
        $cats = $cache->get('cats');
        if (!$cats) {
            $cats = \R::getAssoc("SELECT * FROM category");
            $cache->set('cats', $cats);
        }
        return $cats;
    }

}
