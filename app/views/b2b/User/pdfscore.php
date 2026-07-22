<?php
use ishop\App;
use Dompdf\Dompdf;

function toUtf8($text) {
    return mb_convert_encoding($text, 'UTF-8', mb_detect_encoding($text, 'UTF-8, Windows-1251, ISO-8859-1', true));
}

// Подготовка переменных:
$dogovor = ($comp["dogovor"] == 1) ? "Договор" : "Счет-договор";
$poluchatel = $otpravitel = ($user["groups"] == 5) ? "{$comp["comp_name"]}, ИНН {$comp["inn"]}, {$comp["url_address"]}, {$user["telefon"]}" : '';
$nds_comp = ($comp["nds"] == 1) ? "20%" : "без НДС";

// Проверка и назначение данных для подписей, печати и должности
if (!isset($seller['id']) || empty($seller['id'])) {
    // Можно выбросить исключение или назначить значения по умолчанию
    $dolzhnost = "Ответственный";
    $rukovod = "_____________";
    $pech = "pech-blank";
    $pdp = "pdp-blank";
} else {
    switch ($seller['id']) {
        case "1":
            $dolzhnost = "Индивидуальный предприниматель";
            $rukovod = "Шишкарёв Д. В.";
            $pech = "pech-1";
            $pdp = "pdp-1";
            break;
        case "2":
            $dolzhnost = "Индивидуальный предприниматель";
            $rukovod = "Романов В. М.";
            $pech = "pech-2";
            $pdp = "pdp-2";
            break;
        case "3":
            $dolzhnost = "Генеральный директор";
            $rukovod = "Цыхоня В. А.";
            $pech = "pech-3";
            $pdp = "pdp-3";
            break;
        default:
            $dolzhnost = "Ответственный";
            $rukovod = "_____________";
            $pech = "pech-blank";
            $pdp = "pdp-blank";
    }
}

// Передача переменных в шаблон
$data = compact(
    'order',
    'order_products',
    'comp',
    'seller',
    'user',
    'dogovor',
    'poluchatel',
    'otpravitel',
    'nds_comp',
    'dolzhnost',
    'rukovod',
    'pech',
    'pdp'
);
extract($data);

ob_start(); // Буферизация вывода
include ROOT . '/app/views/pdf/template-schet.php'; // здесь размещён весь твой HTML-код (ниже покажу)
$html = ob_get_clean();

// Обновим все строки, если надо (на всякий случай):
$html = toUtf8($html);

// PDF генерация
$dompdf = new Dompdf();
$dompdf->set_option('isRemoteEnabled', true);
$dompdf->set_option('defaultFont', 'DejaVu Sans');
$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->render();
$dompdf->stream($order['inv']);

exit();
