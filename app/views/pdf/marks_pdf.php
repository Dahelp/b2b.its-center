<style>
@page {
    margin: 10mm;
}
body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 10pt;
}
.mark {
    width: 100mm;
    height: 50mm;
    margin-bottom: 10mm;
    border: 1px solid #000;
    border-collapse: collapse;
}
.mark td {
    padding: 0;
    margin: 0;
    border: none;
}
.mark-content {
    width: 100mm;
    height: 34mm;
}
.mark-left {
    width: 60mm;
    height: 34mm;
    padding: 3mm;
    box-sizing: border-box;
    text-align: center;
    vertical-align: middle;
}
.mark-right {
    width: 40mm;
    height: 34mm;
    padding: 3mm;
    box-sizing: border-box;
    text-align: center;
    vertical-align: middle;
}
.qr img {
    width: 28.5mm;
    height: 28.5mm;
}
.mark-bottom {
    height: 10mm;
    text-align: center;
    vertical-align: middle;
    font-size: 9pt;
    padding: 0 2mm;
    box-sizing: border-box;
}
.mark-number-row {
    height: 6mm;
}
.mark-num {
    width: 15mm;
    height: 6mm;
    font-size: 9pt;
    text-align: center;
    font-weight:bold;
    padding: 2px 0 0 0;       
    background-image: url("marks_pdf/mark-fon-num.png");
    background-repeat: no-repeat;
    background-size: cover;
    background-position: left top;
    box-sizing: border-box;
}
.product-name {
    display: inline-block;
    text-align: center;
    font-weight: normal;
}
.mark-code {
    display: inline-block;
    vertical-align: middle;
    line-height: 1.2;
}
</style>

<h2>Маркировка для заказа №<?= $order->inv ?></h2>

<?php foreach ($marks as $i => $m): ?>
    <table class="mark">
        <tr class="mark-content">
            <td class="mark-left">
                <div class="product-name"><?= htmlspecialchars($m['name']) ?></div>
            </td>
            <td class="mark-right">
                <div class="qr">
                    <img src="<?= $qr_images[$i] ?>">
                </div>
            </td>
        </tr>
        <tr class="mark-number-row">
            <td colspan="2">
                <div class="mark-num"><?= $i + 1 ?></div>
            </td>
        </tr>
        <tr>
            <td colspan="2" class="mark-bottom">
                <div class="mark-code"><?= htmlspecialchars($m['mark']) ?></div>
            </td>
        </tr>
    </table>
<?php endforeach; ?>
