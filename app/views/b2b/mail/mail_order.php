<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заказ №<?= htmlspecialchars($order_inv ?? '', ENT_QUOTES, 'UTF-8') ?> на сайте <?= htmlspecialchars($namecomp ?? '', ENT_QUOTES, 'UTF-8') ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>
<?php
$PATH        = defined('PATH') ? PATH : '';
$namecomp    = $namecomp ?? (\ishop\App::$app->getProperty('shop_name') ?? '');
$tell_site   = $tell_site ?? (\ishop\App::options('option_telefon') ?? '');
$order_inv   = $order_inv ?? '';
$uname       = $uname ?? '';
$telefon     = $telefon ?? '';
$user_email  = $user_email ?? '';
$note        = $note ?? '';
$date        = $date ?? date('Y-m-d H:i:s');

$dostavka_name     = $dostavka_name ?? '';
$branch_name       = $branch_name ?? '';
$address           = $address ?? '';
$transport_company = $transport_company ?? '';
$city_name         = $city_name ?? '';

$vid        = $vid ?? '';
$compname   = $shortCompany ?? ($compname ?? '');
$nds_text   = $nds_text ?? '';
$dogovor    = $dogovor ?? '';
$km_out     = $km_out_text ?? ((!empty($end_buyer_text) && mb_strtolower(trim($end_buyer_text)) !== 'нет') ? 'Да' : 'Нет');

$items   = $items ?? [];
$qtyAll  = (int)($qtyAll ?? 0);
$sumAll  = (float)($sumAll ?? 0);
$symL    = $symL ?? '';
$symR    = $symR ?? '';

$fmt = function($n){ return number_format((float)$n, 0, '.', ' '); };
$isPickup = (mb_stripos($dostavka_name, 'самовывоз') !== false);
?>
<table style="width:740px;background-color:#f4f6f9;font-family:Tahoma, Helvetica, sans-serif;color:#212529;font-size:13px;border:1px solid #eee;margin:0 auto">
    <tr>
        <td style="padding:20px;width:300px">
            <img src="<?= htmlspecialchars($PATH, ENT_QUOTES, 'UTF-8') ?>/images/logo.png"
                 alt="<?= htmlspecialchars($namecomp, ENT_QUOTES, 'UTF-8') ?>"
                 style="width:260px;height:50px">
        </td>
        <td style="padding:20px;width:440px;font-weight:bold" align="right">
            <a href="<?= htmlspecialchars($PATH, ENT_QUOTES, 'UTF-8') ?>" style="color:#2C3E50">Главная</a> |
            <a href="<?= htmlspecialchars($PATH, ENT_QUOTES, 'UTF-8') ?>/catalog" style="color:#2C3E50">Каталог</a> |
            <a href="<?= htmlspecialchars($PATH, ENT_QUOTES, 'UTF-8') ?>/services/dostavka" style="color:#2C3E50">Доставка</a> |
            <a href="<?= htmlspecialchars($PATH, ENT_QUOTES, 'UTF-8') ?>/pages/contacts" style="color:#2C3E50">Контакты</a>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <table cellspacing="0" cellpadding="0" style="width:700px;background:#fff;font-size:13px" align="center">
                <tr>
                    <td>
                        <table cellspacing="0" cellpadding="0" style="width:660px;padding:20px;font-family:Tahoma, Helvetica, sans-serif;color:#212529;font-size:13px" align="center">
                            <tr>
                                <td style="padding:20px 0 20px 0">
                                    <p>Здравствуйте, <?= htmlspecialchars($uname, ENT_QUOTES, 'UTF-8') ?>.<br><br>
                                        Благодарим Вас за заказ!<br>
                                        <?php $dayofweek = date('w', strtotime($date));
                                        if($dayofweek > 0 && $dayofweek < 6){
                                            echo 'Ваш заказ на сайте ' . htmlspecialchars($namecomp, ENT_QUOTES, 'UTF-8') . ' оформлен. Для согласования заказа с Вами свяжется менеджер в рабочее время ПН-ПТ с 09:00 до 17:00';
                                        } else {
                                            echo 'Ваш заказ на сайте ' . htmlspecialchars($namecomp, ENT_QUOTES, 'UTF-8') . ' оформлен. Для согласования заказа с Вами свяжется менеджер в понедельник в рабочее время с 09:00 до 17:00';
                                        } ?>
                                    </p>

                                    <p><strong>Ваш заказ: № <?= htmlspecialchars($order_inv, ENT_QUOTES, 'UTF-8') ?> от <?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?></strong></p>

                                    <table style="border:1px solid #ddd; border-collapse:collapse; width:100%;">
                                        <thead>
                                        <tr style="background:#f9f9f9;">
                                            <th style="padding:8px; border:1px solid #ddd; text-align:left;">Наименование</th>
                                            <th style="padding:8px; border:1px solid #ddd; text-align:center;">Кол-во</th>
                                            <th style="padding:8px; border:1px solid #ddd; text-align:right;">Цена</th>
                                            <th style="padding:8px; border:1px solid #ddd; text-align:right;">Сумма</th>
                                        </tr>
                                        </thead>
                                        <tbody>
										<?php
										$fmt = function($n){ return number_format((float)$n, 0, '.', ' '); };
										foreach ($items as $it):
											$name  = $it['name']  ?? '';
											$qty   = (int)($it['qty'] ?? 0);
											$price = (float)($it['price'] ?? 0);
											$sum   = $price * $qty;
										?>
										<tr>
											<td style="padding:8px; border:1px solid #ddd;"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></td>
											<td style="padding:8px; border:1px solid #ddd; text-align:center;"><?= $qty ?></td>
											<td style="padding:8px; border:1px solid #ddd; text-align:right;"><?= $symL . $fmt($price) . ($symR ? ' '.$symR : '') ?></td>
											<td style="padding:8px; border:1px solid #ddd; text-align:right;"><?= $symL . $fmt($sum)   . ($symR ? ' '.$symR : '') ?></td>
										</tr>
										<?php endforeach; ?>
										<tr>
											<td colspan="3" style="padding:8px; border:1px solid #ddd; text-align:right;"><strong>Итого товаров:</strong></td>
											<td style="padding:8px; border:1px solid #ddd; text-align:right;"><?= (int)$qtyAll ?></td>
										</tr>
										<tr>
											<td colspan="3" style="padding:8px; border:1px solid #ddd; text-align:right;"><strong>На сумму:</strong></td>
											<td style="padding:8px; border:1px solid #ddd; text-align:right;"><?= $symL . $fmt($sumAll) . ($symR ? ' '.$symR : '') ?></td>
										</tr>
										</tbody>
                                    </table>

                                    <br>

                                    <div style="line-height:1.6">
                                        <p><strong>Вывести КМ из оборота:</strong> <?= htmlspecialchars($km_out, ENT_QUOTES, 'UTF-8') ?><br><br>

                                        <?php if ($isPickup): ?>
                                            <strong>Способ доставки:</strong> Самовывоз<br>
                                               <strong>Пункт выдачи:</strong> <?= htmlspecialchars($branch_name, ENT_QUOTES, 'UTF-8') ?><br>
                                               <?php if (!empty($address)): ?>
                                                   <strong>Адрес:</strong> <?= htmlspecialchars($address, ENT_QUOTES, 'UTF-8') ?><br>
                                               <?php endif; ?>
                                            
                                        <?php else: ?>
                                            <strong>Способ доставки:</strong> Транспортная компания<br>
                                               <strong>Название ТК:</strong> <?= htmlspecialchars($transport_company, ENT_QUOTES, 'UTF-8') ?><br>
                                               <?php if (!empty($city_name)): ?>
                                                   <strong>Город:</strong> <?= htmlspecialchars($city_name, ENT_QUOTES, 'UTF-8') ?><br>
                                               <?php endif; ?>
                                            
                                        <?php endif; ?>

                                        <?php if (!empty($vid)): ?>
                                            <strong>Вид клиента:</strong> <?= htmlspecialchars($vid, ENT_QUOTES, 'UTF-8') ?><br>
                                        <?php endif; ?>
                                        <?php if (!empty($compname)): ?>
                                            <strong>Компания (зарегистрирована):</strong> <?= htmlspecialchars($compname, ENT_QUOTES, 'UTF-8') ?><br>
                                        <?php endif; ?>
                                        <?php if (!empty($nds_text)): ?>
                                            <strong>Налогообложение:</strong> <?= htmlspecialchars($nds_text, ENT_QUOTES, 'UTF-8') ?><br>
                                        <?php endif; ?>
                                        <?php if (!empty($dogovor)): ?>
                                            <strong>Условия поставки:</strong> <?= htmlspecialchars($dogovor, ENT_QUOTES, 'UTF-8') ?><br>
                                        <?php endif; ?>

                                        <strong>Имя:</strong> <?= htmlspecialchars($uname, ENT_QUOTES, 'UTF-8') ?><br>
                                           <strong>Номер телефона:</strong> <?= htmlspecialchars($telefon, ENT_QUOTES, 'UTF-8') ?><br>
                                           <strong>E-mail:</strong> <a href="mailto:<?= htmlspecialchars($user_email, ENT_QUOTES, 'UTF-8') ?>" target="_blank"><?= htmlspecialchars($user_email, ENT_QUOTES, 'UTF-8') ?></a><br>
                                           <?php if (!empty($note)): ?>
                                               <strong>Комментарий:</strong> <?= nl2br(htmlspecialchars($note, ENT_QUOTES, 'UTF-8')) ?><br>
                                           <?php endif; ?>
                                           <strong>Время заказа:</strong> <?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?>
                                        </p>

                                        <p>С уважением, <?= htmlspecialchars($namecomp, ENT_QUOTES, 'UTF-8') ?><br>
                                           <strong>Телефон:</strong> <?= htmlspecialchars($tell_site, ENT_QUOTES, 'UTF-8') ?>
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr><td colspan="2" style="padding:20px"></td></tr>
</table>
</body>
</html>
