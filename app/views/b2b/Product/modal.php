<div class="container-fluid">
    <!-- Верхний блок: фото + артикул, цена, наличие -->
    <div class="row mb-4">
        <div class="col-md-4 text-center">
            <?php if (!empty($product['unload_img'])): ?>
                <img src="/images/product/unload/<?=$product['unload_img']?>" alt="" style="max-height: 200px;" class="img-fluid rounded shadow-sm">
            <?php else: ?>
                <div class="text-muted">Нет изображения</div>
            <?php endif; ?>
        </div>
        <div class="col-md-8">
            <h5><?=$product['name']?></h5>
            <p><strong>Артикул:</strong> <?=$product['article']?></p>
            <p><strong>Цена:</strong> <?=number_format($product['price'], 0, ',', ' ')?> ₽</p>
            <p><strong>Наличие:</strong> <?=$product['quantity']?> шт.</p>
			<?php if (!empty($product['wait_date'])): ?>
			<p><strong>Поступление:</strong> <?=$product['wait']?> шт. </p>
			<p><strong>Дата поступления:</strong> <?= $product['wait_date'] ?></p>
			<?php endif; ?>
        </div>
    </div>

    <!-- Характеристики -->
    <?php if ($attribute_group): ?>
        <h6 class="mb-3">Характеристики:</h6>
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-striped">
                <?php foreach ($attribute_group as $group): ?>
                    <thead class="table-light">
                        <tr>
                            <th colspan="2"><?=$group["attribute_name"]?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $attributs = \R::getAll("
                            SELECT * FROM attribute 
                            JOIN product_attribute ON product_attribute.attribute_id = attribute.id 
                            WHERE product_attribute.product_id = ? AND product_attribute.attribute_group_id = ? 
                            ORDER BY attribute.attribute_position
                        ", [$product["id"], $group["attribute_group_id"]]);

                        foreach ($attributs as $att): ?>
                            <tr>
                                <td><?=$att["attribute_name"]?></td>
                                <td><?=$att["attribute_text"]?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
</div>
