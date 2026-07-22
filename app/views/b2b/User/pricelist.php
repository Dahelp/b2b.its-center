<script>
function Selected(a) {
        var label = a.value;
        if (label==1) {
            document.getElementById("Block1").style.display='block';
            document.getElementById("Block2").style.display='none';
			document.getElementById("Block3").style.display='none';
        } else if (label==2) {
            document.getElementById("Block1").style.display='none';
            document.getElementById("Block2").style.display='block';  
			document.getElementById("Block3").style.display='none';			
        } else if (label==3) {
            document.getElementById("Block1").style.display='none';
            document.getElementById("Block2").style.display='none';
			document.getElementById("Block3").style.display='block';        
		} else if (label==4) {
            document.getElementById("Block1").style.display='block';
            document.getElementById("Block2").style.display='block';
			document.getElementById("Block3").style.display='none';
        }
		else {
            document.getElementById("Block1").style.display='none';
            document.getElementById("Block2").style.display='none';
			document.getElementById("Block3").style.display='none';
        }
         
}
</script>

<!--prdt-starts-->
<section class="py-5">
    <div class="container">
        <div class="d-flex align-items-start cab-inner">

			<div class="aiz-user-panel">
				<div class="card">
					<div class="card-header">
						<h5 class="mb-0 h6">Прайс-лист</h5>
					</div>
					<div class="card-body">
						<form action="user/pricelist" method="post" data-toggle="validator">
							<div class="box-body">
								<div class="form-group has-feedback mb-3">
									<label for="name">Формат</label>
									<select id="format" class="form-control" name="format">
										<option value= "" selected="selected">Выберите формат</option>
										<option value= "1">PDF</option>
									</select>
								</div>
								<div class="form-group has-feedback mb-3">
									<label for="name">Вывод данных</label>
									<select id="actSelect" class="form-control" name="actSelect" aria-required="true" onChange="Selected(this)">
										<option value="" selected="selected">Выберите что выгружать</option>
										<option value="5">Все товары</option>
										<option value="1">Определённую категорию</option>
										<option value="2">По производителю</option>
										<option value="4">Категория и производитель</option>
										<option value="3">Артикул товара</option>									
									</select>
								</div>						
								<div id="Block1" style="display: none;" class="form-group has-feedback mb-3">
									<label for="article">Категория товаров</label>								
									<select class="form-control" name="category_id">
										<option value="" selected="selected">Выберите категорию</option>
										<option value="1">Индустриальные шины</option>
										<option value="2">Шины для квадроциклов</option>
										<option value="25">Камеры, ободные ленты, уплотнительные кольца</option>
										<option value="3">Фильтры</option>
										<option value="4">Диски</option>										
									</select>								                                       
								</div>
								<div id="Block2" style="display: none;" class="form-group has-feedback mb-3">
									<label for="brand_id">Производитель</label>								
									<select id="brand_id" class="form-control" name="brand_id">
										<option value="" selected="selected">Выберите производителя</option>
										<option value="1">EKKA</option>
										<option value="2">CST</option>
										<option value="3">SUPERGUIDER</option>
										<option value="4">Forerunner</option>
										<option value="5">SUN.F</option>										
									</select>							                                       
								</div>							
								<div id="Block3" style="display: none;" class="form-group has-feedback mb-3">
									<label for="article">Артикул товара</label>								
									<input class="form-control" type="text" name="article" placeholder="Артикул товара">							                                       
								</div>
							</div>
							<div class="box-footer">
								<button type="submit" class="btn btn-primary">Создать выгрузку товаров</button>
							</div>
						</form>
						<?php if(!empty($product)): ?>
							<div class="table-responsive">
								<button class="btn-none" id="btnpdf" type="submit"><i class="fad fa-file-pdf"></i> Прайс-лист PDF от <?php echo \ishop\App::contdate(date("Y-m-d")); ?></button>
							</div>
						<?php else: ?>
							<p class="text-danger">Прайс-лист пока не сформирован.</p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>				
	</div>
</section>

<!--product-end-->
<?php

function urlimagesbase64($path) {
    if (!is_file($path)) {
        return '';
    }

    $type = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $data = file_get_contents($path);

    if ($data === false) {
        return '';
    }

    if ($type === 'jpg') {
        $type = 'jpeg';
    }

    return 'data:image/' . $type . ';base64,' . base64_encode($data);
}

$logo = urlimagesbase64('images/Logo_round.jpg');
$logos = urlimagesbase64('images/logo-2.png');

$companyTip = (string)($company['tip'] ?? '');
$isOptCompany = ($companyTip === '2');

$pdfRows = [];

if ($isOptCompany) {
    $pdfRows[] = [
        ['text' => 'Артикул', 'fontSize' => 8, 'style' => 'tableHeader'],
        ['text' => 'Производитель', 'fontSize' => 8, 'style' => 'tableHeader'],
        ['text' => 'Модель', 'fontSize' => 8, 'style' => 'tableHeader'],
        ['text' => 'Наименование', 'fontSize' => 8, 'style' => 'tableHeader'],
        ['text' => 'Наличие', 'fontSize' => 8, 'style' => 'tableHeader'],
        ['text' => 'Опт', 'fontSize' => 8, 'style' => 'tableHeader'],
        ['text' => 'Розница', 'fontSize' => 8, 'style' => 'tableHeader'],
    ];
} else {
    $pdfRows[] = [
        ['text' => 'Артикул', 'fontSize' => 8, 'style' => 'tableHeader'],
        ['text' => 'Производитель', 'fontSize' => 8, 'style' => 'tableHeader'],
        ['text' => 'Модель', 'fontSize' => 8, 'style' => 'tableHeader'],
        ['text' => 'Наименование', 'fontSize' => 8, 'style' => 'tableHeader'],
        ['text' => 'Наличие', 'fontSize' => 8, 'style' => 'tableHeader'],
        ['text' => 'Розница', 'fontSize' => 8, 'style' => 'tableHeader'],
    ];
}

if (!empty($product) && is_iterable($product)) {
    foreach ($product as $prod) {
        $article = (string)($prod['article'] ?? '');
        $vendor = (string)($prod['vendor'] ?? '');
        $model = (string)($prod['model'] ?? '');
        $name = (string)($prod['name'] ?? '');
        $alias = (string)($prod['alias'] ?? '');
        $quantity = (string)($prod['quantity'] ?? '');
        $price = (string)($prod['price'] ?? '');
        $categoryId = (int)($prod['category_id'] ?? 0);

        $productLink = rtrim(PATH, '/') . '/product/' . $alias;

        if ($isOptCompany) {
            $optPrice = '';

            $ucompany = \R::getRow(
                'SELECT company.tip, company_typeprice.znachenie 
                 FROM company, company_typeprice 
                 WHERE company.id = company_typeprice.company_id 
                   AND company.user_id = ? 
                   AND company_typeprice.category_id = ?',
                [$_SESSION['user']['id'] ?? 0, $categoryId]
            );

            if (($ucompany['tip'] ?? '') === '2') {
                if (($ucompany['znachenie'] ?? '') === '') {
                    $optPrice = (string)($prod['opt_price'] ?? '');
                } else {
                    $discount = (float)$ucompany['znachenie'];
                    $basePrice = (float)($prod['price'] ?? 0);

                    $price_nds = round($basePrice - ($basePrice / 1.2), 0) * 6;
                    $price_opt = $price_nds - (($price_nds / 100) * $discount);
                    $optPrice = (string)(round($price_opt / 6) * 6);
                }
            }

            $pdfRows[] = [
                ['text' => $article, 'fontSize' => 8, 'style' => 'tableHeader'],
                ['text' => $vendor, 'fontSize' => 8, 'style' => 'tableHeader'],
                ['text' => $model, 'fontSize' => 8, 'style' => 'tableHeader'],
                [
                    'text' => $name,
                    'link' => $productLink,
                    'fontSize' => 8,
                    'style' => 'tableHeader',
                    'decoration' => 'underline',
                ],
                ['text' => $quantity, 'fontSize' => 8, 'style' => 'tableHeader'],
                ['text' => $optPrice, 'fontSize' => 8, 'style' => 'tableHeader'],
                ['text' => $price, 'fontSize' => 8, 'style' => 'tableHeader'],
            ];
        } else {
            $pdfRows[] = [
                ['text' => $article, 'fontSize' => 8, 'style' => 'tableHeader'],
                ['text' => $vendor, 'fontSize' => 8, 'style' => 'tableHeader'],
                ['text' => $model, 'fontSize' => 8, 'style' => 'tableHeader'],
                [
                    'text' => $name,
                    'link' => $productLink,
                    'fontSize' => 8,
                    'style' => 'tableHeader',
                    'decoration' => 'underline',
                ],
                ['text' => $quantity, 'fontSize' => 8, 'style' => 'tableHeader'],
                ['text' => $price, 'fontSize' => 8, 'style' => 'tableHeader'],
            ];
        }
    }
}

$pdfWidths = $isOptCompany
    ? [40, 70, 30, 200, 40, 30, 40]
    : [40, 70, 30, 230, 40, 40];

$pdfTitle = 'Прайс-лист ИТС-Центр';
$pdfDate = \ishop\App::contdate(date('Y-m-d'));

$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
?>

<?php
$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

$pdfTitle = 'Прайс-лист ИТС-Центр';
?>

<script src="/js/pdfmake.js"></script>
<script src="/js/vfs_fonts.js"></script>

<script>
(function () {
    var btn = document.getElementById('btnpdf');

    if (!btn) {
        return;
    }

    btn.onclick = function () {
        if (typeof pdfMake === 'undefined') {
            alert('Ошибка: библиотека pdfMake не подключена.');
            return;
        }

        var logo = <?= json_encode($logo, $jsonFlags); ?>;
        var footerLogo = <?= json_encode($logos, $jsonFlags); ?>;
        var tableRows = <?= json_encode($pdfRows, $jsonFlags); ?>;
        var tableWidths = <?= json_encode($pdfWidths, $jsonFlags); ?>;

        if (!tableRows || !tableRows.length) {
            alert('Нет товаров для формирования прайс-листа.');
            return;
        }

        var headerColumns = [];

        if (logo) {
            headerColumns.push({
                image: logo,
                width: 80
            });
        } else {
            headerColumns.push({
                text: 'ИТС-Центр',
                width: 80,
                bold: true,
                fontSize: 14
            });
        }

        headerColumns.push([
            {
                text: 'Общество с ограниченной ответственностью «ИТС-Центр»',
                fontSize: 14,
                alignment: 'center',
                margin: [0, 0, 0, 10]
            },
            {
                text: '142117, Московская область, г. Подольск, деревня Коледино, ул. Троицкая, д.1Г, стр.1, помещение В-348/49,\nтел./факс +7 (495) 424-98-90, e-mail: info@its50.ru, ИНН/КПП 5036103305/503601001, р/с 40702810901080002314\nв филиале «Центральный Банк ВТБ (ПАО), корр/с 30101810145250000411, БИК 044525411',
                alignment: 'center',
                fontSize: 8,
                margin: [0, 0, 0, 15]
            }
        ]);

        var footerColumns = [];

        if (footerLogo) {
            footerColumns.push({
                image: footerLogo,
                width: 110,
                margin: [30, 0]
            });
        } else {
            footerColumns.push({
                text: 'ИТС-Центр',
                width: 110,
                margin: [30, 0],
                bold: true
            });
        }

        footerColumns.push([
            {
                text: 'Телефон: +7 (495) 424-98-90\nWhatsApp: +7 (916) 562-52-79',
                fontSize: 10,
                alignment: 'left',
                margin: [90, 0, 0, 10],
                width: 280
            }
        ]);

        footerColumns.push([
            {
                text: 'Email: info@its-center.ru\nСайт: its-center.ru',
                fontSize: 10,
                alignment: 'left',
                margin: [100, 0, 0, 10],
                width: 250
            }
        ]);

        var docDefinition = {
            info: {
                title: <?= json_encode($pdfTitle, $jsonFlags); ?>,
                author: 'ИТС-Центр',
                subject: 'Товары',
                keywords: 'Шины, диски, фильтры, на спецтехнику'
            },

            pageSize: 'A4',
            pageOrientation: 'portrait',
            pageMargins: [30, 30, 30, 30],

            content: [
                {
                    columns: headerColumns
                },
                {
                    canvas: [
                        {
                            type: 'line',
                            x1: 0,
                            y1: 0,
                            x2: 535,
                            y2: 0,
                            lineWidth: 1,
                            lineColor: '#00ffff'
                        }
                    ]
                },
                {
                    text: <?= json_encode('Прайс-лист от ' . $pdfDate, $jsonFlags); ?>,
                    fontSize: 16,
                    alignment: 'center',
                    margin: [0, 20, 0, 20],
                    bold: true
                },
                {
                    layout: 'lightHorizontalLines',
                    table: {
                        widths: tableWidths,
                        body: tableRows
                    }
                },
                {
                    text: '',
                    margin: [0, 50, 0, 30],
                    fontSize: 8
                }
            ],

            footer: function () {
                return {
                    columns: footerColumns
                };
            },

            styles: {
                tableHeader: {
                    fontSize: 8
                },
                footer: {
                    margin: [30, 0, 30, 0],
                    background: '#cccccc'
                }
            }
        };

        var win = window.open('', '_blank');

        try {
            pdfMake.createPdf(docDefinition).open({}, win);
        } catch (e) {
            console.error(e);

            if (win) {
                win.document.write(
                    '<pre style="white-space:pre-wrap;font-family:Arial;padding:20px;">Ошибка формирования PDF:\n' +
                    e.message +
                    '</pre>'
                );
            } else {
                alert('Не удалось открыть окно PDF. Проверьте блокировку всплывающих окон.');
            }
        }
    };
})();
</script>