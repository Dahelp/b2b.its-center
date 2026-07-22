<?php

namespace app\controllers;

class CityController extends AppController
{
    /**
     * AJAX-поиск городов для Select2 (по начальным буквам).
     * GET /city/search?q=Мос
     * Ответ: [{"id":123,"text":"Москва"}, ...]
     */
    public function searchAction()
    {
        $this->layout = false;
        header('Content-Type: application/json; charset=utf-8');

        $q = trim($_GET['q'] ?? '');
        $q = mb_substr($q, 0, 50);

        if ($q === '' || mb_strlen($q) < 1) {
            echo json_encode([], JSON_UNESCAPED_UNICODE); exit;
        }

        // поиск по началу строки
        $like = $q . '%';
        $rows = \R::getAll(
            "SELECT id, city_name 
             FROM cities 
             WHERE city_name LIKE ? 
             ORDER BY city_name 
             LIMIT 30",
            [$like]
        );

        $res = array_map(function ($r) {
            return [
                'id'   => (int)$r['id'],
                'text' => $r['city_name'],
            ];
        }, $rows);

        echo json_encode($res, JSON_UNESCAPED_UNICODE); exit;
    }
}

