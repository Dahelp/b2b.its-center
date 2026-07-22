<?php

namespace app\controllers;

use app\models\User;
use app\models\Category;
use app\widgets\filter\Filter;
use ishop\App;
use ishop\libs\Pagination;
use app\services\Api1C;

class PodborController extends AppController
{
    public function indexAction()
    {
        if (!User::checkAuth()) {
            redirect('/');
            exit;
        }

        $alias = $this->route['alias'] ?? '';

        if ($alias === 'uslugi') {
            $category = \R::getRow("SELECT * FROM category WHERE id = ? LIMIT 1", [37]);
        } else {
            $category = \R::getRow("SELECT * FROM category WHERE alias = ? LIMIT 1", [$alias]);
        }

        if (!$category) {
            throw new \Exception('Страница не найдена', 404);
        }

        $user_id = (int)($_SESSION['b2buser']['id'] ?? 0);

        $tip = \R::getCell("
            SELECT company.tip
            FROM company
            JOIN user ON user.comp_id = company.id
            WHERE user.id = ?
            LIMIT 1
        ", [$user_id]);

        $cat_model = new Category();

        if ((int)$category['id'] === 37) {
            // Услуги = 37, подразделы услуг = 38 и 42
            $ids_array = [37, 38, 42];
        } else {
            $ids_array = $cat_model->getIdsArray($category['id']);
            $ids_array[] = $category['id'];
        }

        $ids_array = array_unique(array_map('intval', $ids_array));
        $ids = implode(',', $ids_array);
        $main_category_id = (int)$category['id'];

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $page = max(1, $page);

        $perpage = (int)App::$app->getProperty('pagination');
        if ($perpage <= 0) {
            $perpage = 12;
        }

        $sql_sort = 'ORDER BY stock_status_id DESC';

        if (!empty($_GET['sort'])) {
            if ($_GET['sort'] === 'price') {
                $sql_sort = 'ORDER BY price ASC';
            } elseif ($_GET['sort'] === 'nal') {
                $sql_sort = 'ORDER BY stock_status_id DESC';
            } elseif ($_GET['sort'] === 'rate') {
                $sql_sort = 'ORDER BY hit DESC';
            }
        }

        if ((int)$category['id'] === 37) {
            $sql_sort = 'ORDER BY category_id ASC, id ASC';
        }

        $applyPricing = function (&$p) use ($tip) {
            $db_opt  = isset($p->opt_price) ? $p->opt_price : null;
            $db_spec = isset($p->spec_price) ? $p->spec_price : null;
            $db_rrs  = isset($p->rrs_price) ? $p->rrs_price : null;

            $article = isset($p->article) ? trim((string)$p->article) : '';
            $api = $article !== '' ? Api1C::getProductData($article) : null;

            if ($api && isset($api['price_rozn'])) {
                $p->rrs_price = $api['price_rozn'];
            } else {
                $p->rrs_price = $db_rrs !== null ? $db_rrs : (isset($p->price) ? $p->price : 0);
            }

            if ($api) {
                if ($tip == 1) {
                    $p->price = isset($api['price_rozn']) ? $api['price_rozn'] : $db_opt;
                } elseif ($tip == 2) {
                    $p->price = isset($api['price_opt']) ? $api['price_opt'] : $db_opt;
                } elseif ($tip == 3) {
                    if (isset($api['price_spec'])) {
                        $p->price = $api['price_spec'];
                    } elseif ($db_spec !== null) {
                        $p->price = $db_spec;
                    } else {
                        $p->price = $db_opt;
                    }
                } else {
                    $p->price = $db_opt;
                }

                if (isset($api['name']) && $api['name'] !== '') {
                    $p->name = $api['name'];
                }

                if (!empty($p->is_mod)) {
                    $p->quantity = isset($api['quantity']) ? (int)$api['quantity'] : 0;
                    $p->wait = isset($api['wait']) ? $api['wait'] : null;

                    $rawDate = isset($api['wait_date']) ? $api['wait_date'] : null;
                    $p->wait_date = $rawDate ? \ishop\App::getFormattedDeliveryDate($rawDate) : null;
                } else {
                    if (isset($api['quantity'])) {
                        $p->quantity = (int)$api['quantity'];
                    }

                    if (isset($api['wait'])) {
                        $p->wait = $api['wait'];
                    }

                    $rawDate = isset($api['wait_date']) ? $api['wait_date'] : (isset($p->wait_date) ? $p->wait_date : null);
                    $p->wait_date = $rawDate ? \ishop\App::getFormattedDeliveryDate($rawDate) : null;
                }
            } else {
                if ($tip == 3) {
                    $p->price = $db_spec !== null ? $db_spec : $db_opt;
                } elseif ($tip == 1 || $tip == 2) {
                    $p->price = $db_opt;
                } else {
                    $p->price = isset($p->price) ? $p->price : $db_opt;
                }
            }

            if ($p->price === null || $p->price === '') {
                $p->price = 0;
            }

            if ($p->rrs_price === null || $p->rrs_price === '') {
                $p->rrs_price = $p->price;
            }
        };

        $prepareServiceProduct = function (&$p) {
            if (empty($p->name) && !empty($p->title)) {
                $p->name = $p->title;
            }

            if (!isset($p->article)) {
                $p->article = '';
            }

            if (!isset($p->quantity) || (int)$p->quantity <= 0) {
                // Для услуг не используем реальные остатки 1С
                $p->quantity = 999;
            }

            if (!isset($p->stock_status_id) || $p->stock_status_id === null || $p->stock_status_id === '') {
                $p->stock_status_id = 1;
            }

            if (!isset($p->price) || $p->price === null || $p->price === '') {
                if (isset($p->opt_price) && $p->opt_price !== null && $p->opt_price !== '') {
                    $p->price = $p->opt_price;
                } elseif (isset($p->rrs_price) && $p->rrs_price !== null && $p->rrs_price !== '') {
                    $p->price = $p->rrs_price;
                } else {
                    $p->price = 0;
                }
            }

            if (!isset($p->rrs_price) || $p->rrs_price === null || $p->rrs_price === '') {
                $p->rrs_price = $p->price;
            }

            if (!isset($p->wait)) {
                $p->wait = null;
            }

            if (!isset($p->wait_date)) {
                $p->wait_date = null;
            }
        };

        $loadServiceProducts = function () use ($ids, $page, $perpage, $tip) {
			/*
			 * Услуги:
			 * - товары берём из БД: category_id IN (37,38,42)
			 * - цены берём из 1С: /trade/hs/goods/services
			 * - сопоставление:
			 *   product.article = 304
			 *   service.code = 00000000304
			 */

			$servicesByCode = Api1C::getServicesByCode();
			
			if (empty($servicesByCode)) {
				$total = 0;
				$products = [];
				$pagination = new Pagination($page, $perpage, $total);

				return [$products, $total, $pagination];
			}

			$where = "hide = 'show' AND category_id IN ($ids)";

			$rowsAll = \R::getAll("
				SELECT *
				FROM product
				WHERE $where
				ORDER BY category_id ASC, id ASC
			");
			
			$matchedRows = [];

			foreach ($rowsAll as $row) {
				$dbArticle = trim((string)($row['article'] ?? ''));
				$normalizedArticle = Api1C::normalizeCode($dbArticle);

				if (isset($servicesByCode[$dbArticle]) || isset($servicesByCode[$normalizedArticle])) {
					$matchedRows[] = $row;
				}
			}

			$total = count($matchedRows);

			$pagination = new Pagination($page, $perpage, $total);
			$start = $pagination->getStart();

			$pagedRows = array_slice($matchedRows, $start, $perpage);

			$products = [];

			foreach ($pagedRows as $row) {
				$p = (object)$row;

				$dbArticle = trim((string)$p->article);
				$normalizedArticle = Api1C::normalizeCode($dbArticle);

				$service = $servicesByCode[$dbArticle] ?? $servicesByCode[$normalizedArticle] ?? null;

				if (!$service) {
					continue;
				}

				if (empty($service['isservice'])) {
					continue;
				}

				if (!empty($service['name'])) {
					$p->name = $service['name'];
				} elseif (empty($p->name) && !empty($p->title)) {
					$p->name = $p->title;
				}

				$p->article = $dbArticle;

				// Услуга не имеет складского остатка, но кнопка корзины требует quantity > 0
				$p->quantity = 1;
				$p->stock_status_id = 1;
				$p->wait = null;
				$p->wait_date = null;

				$priceRozn = isset($service['price_rozn']) ? (float)$service['price_rozn'] : 0;
				$priceOpt  = isset($service['price_opt']) ? (float)$service['price_opt'] : $priceRozn;
				$priceSpec = isset($service['price_spec']) ? (float)$service['price_spec'] : $priceOpt;

				$p->rrs_price = $priceRozn;

				if ((int)$tip === 1) {
					$p->price = $priceRozn;
				} elseif ((int)$tip === 2) {
					$p->price = $priceOpt;
				} elseif ((int)$tip === 3) {
					$p->price = $priceSpec;
				} else {
					$p->price = $priceRozn;
				}

				$p->isservice = true;
				$p->is_mod = false;
				$p->mod_id = 0;

				$products[] = $p;
			}

			return [$products, $total, $pagination];
		};

        if ($this->isAjax()) {
			if ((int)$category['id'] === 37) {
				[$products, $total, $pagination] = $loadServiceProducts();

				$this->loadView('filter', compact(
					'products',
					'total',
					'pagination',
					'ids',
					'category',
					'main_category_id'
				));

				return;
			}

            if (!empty($_GET['find_pids'])) {
                $pid = (int)explode(',', (string)$_GET['find_pids'])[0];

                $products = [];
                $total = 0;

                if ($pid > 0) {
                    $row = \R::getRow("
                        SELECT *
                        FROM product
                        WHERE id = ?
                          AND hide = 'show'
                          AND category_id IN ($ids)
                        LIMIT 1
                    ", [$pid]);

                    if ($row) {
                        $p = (object)$row;
                        $applyPricing($p);
                        $products[] = $p;
                        $total = 1;
                    }
                }

                $pagination = new Pagination(1, 1, $total);

                $this->loadView('filter', compact(
                    'products',
                    'total',
                    'pagination',
                    'ids',
                    'category',
                    'main_category_id'
                ));

                return;
            }

            if (!empty($_GET['cross'])) {
                $crossId = (int)$_GET['cross'];

                $productId = (int)\R::getCell("
                    SELECT product_id
                    FROM plagins_cross
                    WHERE id = ?
                    LIMIT 1
                ", [$crossId]);

                $products = [];
                $total = 0;

                if ($productId > 0) {
                    $row = \R::getRow("
                        SELECT *
                        FROM product
                        WHERE id = ?
                          AND hide = 'show'
                          AND category_id IN ($ids)
                        LIMIT 1
                    ", [$productId]);

                    if ($row) {
                        $p = (object)$row;
                        $applyPricing($p);
                        $products[] = $p;
                        $total = 1;
                    }
                }

                $pagination = new Pagination(1, 1, $total);

                $this->loadView('filter', compact(
                    'products',
                    'total',
                    'pagination',
                    'ids',
                    'category',
                    'main_category_id'
                ));

                return;
            }

            if (!empty($_GET['filter'])) {
                $_GET['filter'] = rtrim($_GET['filter'], ',');
                $filter = Filter::getFilter();

                $where = "hide = 'show' AND category_id IN ($ids)";

                if ($filter) {
                    $cnt = Filter::getCountGroups($filter);

                    $product_ids = \R::getCol("
                        SELECT ap.product_id
                        FROM attribute_product ap
                        JOIN attribute_value av ON av.id = ap.attr_id
                        WHERE ap.attr_id IN ($filter)
                        GROUP BY ap.product_id
                        HAVING COUNT(DISTINCT av.attr_group_id) = $cnt
                    ");

                    if (empty($product_ids)) {
                        $product_ids = [0];
                    }

                    $filtered_ids_str = implode(',', array_map('intval', $product_ids));
                    $where .= " AND id IN ($filtered_ids_str)";
                }

                $total = \R::count('product', $where);

                $pagination = new Pagination($page, $perpage, $total);
                $start = $pagination->getStart();

                $rows = \R::getAll("
                    SELECT *
                    FROM product
                    WHERE $where
                    $sql_sort
                    LIMIT $start, $perpage
                ");

                $products = [];

                foreach ($rows as $row) {
                    $p = (object)$row;
                    $applyPricing($p);
                    $products[] = $p;

                    $mods = \R::findAll('modification', 'product_id = ?', [$p->id]);

                    foreach ($mods as $mod) {
                        $m = new \stdClass();

                        $m->is_mod = true;
                        $m->mod_id = (int)$mod['id'];
                        $m->id = (int)$p->id;
                        $m->article = ltrim((string)$mod['article'], '0');
                        $m->name = !empty($mod['title']) ? $mod['title'] : $p->name;
                        $m->img = $p->img;
                        $m->unload_img = $p->unload_img ?? '';
                        $m->opt_price = !empty($mod['price']) ? $mod['price'] : ($p->opt_price ?? 0);
                        $m->spec_price = $p->spec_price ?? null;
                        $m->price = $m->opt_price;
                        $m->stock_status_id = 1;
                        $m->quantity = 0;
                        $m->wait = null;
                        $m->wait_date = null;
                        $m->rrs_price = $p->rrs_price ?? null;

                        $applyPricing($m);
                        $products[] = $m;
                    }
                }

                usort($products, function ($a, $b) {
                    $qa = isset($a->quantity) ? (int)$a->quantity : 0;
                    $qb = isset($b->quantity) ? (int)$b->quantity : 0;

                    if ($qa === $qb) {
                        return 0;
                    }

                    return ($qa < $qb) ? 1 : -1;
                });

                $this->loadView('filter', compact(
                    'products',
                    'total',
                    'pagination',
                    'ids',
                    'category',
                    'main_category_id'
                ));

                return;
            }

            $products = [];
            $total = 0;
            $pagination = new Pagination($page, $perpage, $total);

            $this->loadView('filter', compact(
                'products',
                'total',
                'pagination',
                'ids',
                'category',
                'main_category_id'
            ));

            return;
        }

        switch ((string)$category['id']) {
            case '3':
                $pdr_name = 'Подбор дисков на спецтехнику по параметрам';
                break;

            case '25':
                $pdr_name = 'Подбор камер на спецтехнику по параметрам';
                break;

            case '34':
                $pdr_name = 'Подбор шин на спецтехнику по параметрам';
                break;

            case '4':
                $pdr_name = 'Подбор фильтров на спецтехнику по параметрам или кросс-номеру';
                break;

            case '37':
                $pdr_name = 'Услуги';
                break;

            default:
                $pdr_name = !empty($category['h1']) ? $category['h1'] : $category['name'];
                break;
        }

        if (!empty($_GET['cross'])) {
            $pdr_name .= ' по кросс-номеру';
        }

        $canonicalAlias = $alias === 'uslugi' ? 'uslugi' : ($this->route['alias'] ?? '');

        $metaTitle = !empty($category['title']) ? $category['title'] : $pdr_name;
        $metaDesc = !empty($category['description']) ? $category['description'] : $pdr_name;
        $metaKeywords = !empty($category['keywords']) ? $category['keywords'] : '';

        $this->setMeta(
            $metaTitle,
            $metaDesc,
            $metaKeywords,
            \ishop\App::$app->getProperty('shop_name'),
            PATH . '/images/' . \ishop\App::$app->getProperty('og_logo'),
            PATH . '/' . mb_strtolower($this->route['controller']) . '/' . $canonicalAlias
        );

        if ((int)$category['id'] === 37) {
			$products = [];
			$total = 0;
			$pagination = new Pagination($page, $perpage, $total);

			$this->set(compact(
				'products',
				'pagination',
				'total',
				'category',
				'ids',
				'pdr_name',
				'main_category_id'
			));

			return;
		}

        $products = [];
        $total = 0;
        $pagination = new Pagination($page, $perpage, $total);

        $this->set(compact(
            'products',
            'pagination',
            'total',
            'category',
            'ids',
            'pdr_name',
            'main_category_id'
        ));

        return;
    }
}