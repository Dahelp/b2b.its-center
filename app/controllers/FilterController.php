<?php

namespace app\controllers;

use ishop\App;

class FilterController extends AppController {

    public function crossSearchAction() {
        header('Content-Type: application/json');
        $term = $_GET['term'] ?? '';
        $term = preg_replace('/[\/\.\-\sb]/u', '', $term);
        $term = mb_strtolower(trim($term));

        if (strlen($term) < 2) {
            echo json_encode([]);
            exit;
        }

        $results = \R::getAll("
            SELECT id, cross_abbreviated_name 
            FROM plagins_cross 
            WHERE REPLACE(REPLACE(REPLACE(REPLACE(LOWER(cross_abbreviated_name), '/', ''), '.', ''), '-', ''), 'b', '') 
                LIKE ? 
            LIMIT 20
        ", ['%' . $term . '%']);

        $data = [];
        foreach ($results as $row) {
            $data[] = [
                'id' => $row['id'],
                'name' => $row['cross_abbreviated_name'],
            ];
        }

        echo json_encode($data);
        exit;
    }

     public function findSearchAction() {
    header('Content-Type: application/json');

    $term = trim($_GET['term'] ?? '');
    $category_id = (int)($_GET['category'] ?? 0);
    if (mb_strlen($term) < 2) { echo json_encode([]); exit; }

    // Нормализация артикула: сравнение без ведущих нулей (только если цифры)
    $normalizeArticle = function(string $s) {
        $s = trim($s);
        if ($s !== '' && preg_match('/^\d+$/', $s)) {
            $s = ltrim($s, '0');
            if ($s === '') $s = '0';
        }
        return $s;
    };

    $articleNorm = $normalizeArticle(preg_replace('/[^0-9A-Za-zА-Яа-яЁё\-\. ]/u', '', $term));
    $nameLike = '%' . mb_strtolower($term) . '%';

    // Ограничение по дереву текущей категории
    $idsClause = '1=1';
    $idsParams = [];
    if ($category_id > 0) {
        // получаем все id подкатегорий
        $catIds = [];
        try {
            $catModel = new \app\models\Category();
            $arr = $catModel->getIdsArray($category_id);
            if (!is_array($arr)) $arr = [];
            $arr[] = $category_id;
            $arr = array_unique(array_map('intval', $arr));
            if (!$arr) $arr = [0];
            $idsClause = 'p.category_id IN (' . implode(',', $arr) . ')';
        } catch (\Throwable $e) {
            // если что-то не так с моделью — ограничим только основной категорией
            $idsClause = 'p.category_id = ?';
            $idsParams[] = $category_id;
        }
    }

    // Два запроса: 1) точное совпадение артикула (с приоритетом), 2) поиск по имени/арт.
    $res = [];

    // 1) Точный артикул
    if ($articleNorm !== '') {
        $sql1 = "
            SELECT p.id, p.name, p.article, 1 AS prio
            FROM product p
            WHERE p.hide = 'show'
              AND {$idsClause}
              AND (
                    TRIM(LEADING '0' FROM p.article) = ?
                 OR p.article = ?
              )
            ORDER BY p.id DESC
            LIMIT 10
        ";
        $res1 = \R::getAll($sql1, array_merge($idsParams, [$articleNorm, $articleNorm]));
        $res = array_merge($res, $res1);
    }

    // 2) LIKE по имени и артикулу (без ведущих нулей)
    $sql2 = "
        SELECT p.id, p.name, p.article, 2 AS prio
        FROM product p
        WHERE p.hide = 'show'
          AND {$idsClause}
          AND (
                LOWER(p.name) LIKE ?
             OR TRIM(LEADING '0' FROM p.article) LIKE ?
          )
        ORDER BY p.id DESC
        LIMIT 30
    ";
    $res2 = \R::getAll($sql2, array_merge($idsParams, [mb_strtolower($nameLike), '%'.$articleNorm.'%']));
    $res = array_merge($res, $res2);

    // Убираем дубликаты по id и сортируем по приоритету
    $seen = [];
    $out = [];
    foreach ($res as $row) {
        $pid = (int)$row['id'];
        if (isset($seen[$pid])) continue;
        $seen[$pid] = true;
        $label = trim(($row['article'] ? ('['.$row['article'].'] ') : '') . $row['name']);
        $out[] = [
            'id'    => $pid,
            'name'  => $label,
            'prio'  => (int)$row['prio'],
        ];
        if (count($out) >= 30) break;
    }

    // Приоритет сначала prio=1 (точный артикул), потом остальные
    usort($out, function($a,$b){
        if ($a['prio'] === $b['prio']) return $a['id'] < $b['id'] ? 1 : -1;
        return $a['prio'] <=> $b['prio'];
    });

    echo json_encode(array_map(function($row){
        return ['id' => $row['id'], 'name' => $row['name']];
    }, $out));
    exit;
}


public function filterSearchAction() {
        $this->layout = false;
        header('Content-Type: application/json');

        $q = $_GET['q'] ?? '';
        $group = (int) ($_GET['group'] ?? 0);
        $type = $_GET['type'] ?? 'text';
        $category_id = (int) ($_GET['category'] ?? 0);

        function normalize($str) {
            $str = mb_strtolower($str);
            $str = str_replace(',', '.', $str); // <-- добавим эту строку
            $str = str_replace(['x', 'х'], '*', $str);
            $str = str_replace(['.', '/', '-', ' '], '', $str);
            return $str;
        }

        function normalize_text($str) {
            return preg_replace('/[^a-zа-яё0-9]/ui', '', mb_strtolower($str));
        }

        if (empty($q) || !$group || !$category_id) {
            echo json_encode([]);
            exit;
        }

        // ✅ получаем все связанные категории
        $cat_model = new \app\models\Category();
        $ids_array = $cat_model->getIdsArray($category_id);
        $ids_array[] = $category_id;
        $ids_str = implode(',', array_map('intval', $ids_array));

        // ✅ аналог Filter::getAttrs
        $data = \R::getAll("
            SELECT attribute_value.id, attribute_value.value
            FROM attribute_value, attribute_product, product
            WHERE attribute_value.id = attribute_product.attr_id
            AND product.id = attribute_product.product_id
            AND product.category_id IN ($ids_str)
            AND attribute_value.attr_group_id = ?
            GROUP BY attribute_value.value
            ORDER BY attribute_value.value
        ", [$group]);

        $results = [];

        $norm_q = normalize($q);
        $norm_text_q = normalize_text($q);

        foreach ($data as $row) {
            $val = $row['value'];
            $norm_val = normalize($val);
            $norm_text_val = normalize_text($val);

            if ($type === 'size') {
                if (strpos($norm_val, $norm_q) !== false || strpos($norm_q, $norm_val) !== false) {
                    $results[] = ['id' => $row['id'], 'text' => $val];
                }
            } else {
                if (
                    mb_stripos($val, $q) !== false ||
                    strpos($norm_text_val, $norm_text_q) !== false
                ) {
                    $results[] = ['id' => $row['id'], 'text' => $val];
                }
            }
        }

        echo json_encode($results);
        exit;
    }

}
