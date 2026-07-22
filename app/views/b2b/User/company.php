<!--prdt-starts-->
<section class="py-5">
    <div class="container">
        <div class="d-flex align-items-start cab-inner">
            <?php if (!empty($company) && is_object($company)): ?>
            <div class="aiz-user-panel">
                <div class="card col-xl-7 float-left" style="margin:0 30px 0 0">
                    <div class="card-header">
                        <h5 class="mb-0 h6">Компания</h5>
                    </div>
                    <div class="card-body">
                        <div class="box-body">
                            <div class="form-group has-feedback mb-3">
                                <label for="comp_name">Название компании</label>
                                <input type="text" class="form-control" name="comp_name" id="comp_name" value="<?= htmlspecialchars($company->comp_name ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled="">
                            </div>
                            <div class="form-group has-feedback mb-3">
                                <label for="comp_short_name">Краткое название компании</label>
                                <input type="text" class="form-control" name="comp_short_name" id="comp_short_name" value="<?= htmlspecialchars($company->comp_short_name ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled="">
                            </div>
                            <div class="form-group has-feedback mb-3">
                                <label for="url_address">Юр. адрес</label>
                                <input type="text" class="form-control" name="url_address" id="url_address" value="<?= htmlspecialchars($company->url_address ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled="">
                            </div>
                            <div class="form-group has-feedback mb-3">
                                <label for="postal_address">Почтовый адрес</label>
                                <input type="text" class="form-control" name="postal_address" id="postal_address" value="<?= htmlspecialchars($company->postal_address ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled="">
                            </div>
                            <div class="form-group has-feedback mb-3">
                                <label for="ogrn">ОГРН, ОГРНИП</label>
                                <input type="text" class="form-control" name="ogrn" id="ogrn" value="<?= htmlspecialchars($company->ogrn ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled="">
                            </div>
                            <div class="form-group mb-3">
                                <label for="inn">ИНН</label>
                                <input type="text" class="form-control" name="inn" id="inn" value="<?= htmlspecialchars($company->inn ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled="">
                            </div>
                            <div class="form-group has-feedback mb-3">
                                <label for="kpp">КПП</label>
                                <input type="text" class="form-control" name="kpp" id="kpp" value="<?= htmlspecialchars($company->kpp ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled="">
                            </div>
                            <div class="form-group has-feedback mb-3">
                                <label for="bik">БИК</label>
                                <input type="text" class="form-control" name="bik" id="bik" value="<?= htmlspecialchars($company->bik ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled="">
                            </div>
                            <div class="form-group has-feedback mb-3">
                                <label for="raschet">Расч. счёт</label>
                                <input type="text" class="form-control" name="raschet" id="raschet" value="<?= htmlspecialchars($company->raschet ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled="">
                            </div>
                            <div class="form-group has-feedback mb-3">
                                <label for="korschet">Кор. счёт</label>
                                <input type="text" class="form-control" name="korschet" id="korschet" value="<?= htmlspecialchars($company->korschet ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled="">
                            </div>
                            <div class="form-group has-feedback mb-3">
                                <label for="bank">Наименование банка</label>
                                <input type="text" class="form-control" name="bank" id="bank" value="<?= htmlspecialchars($company->bank ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled="">
                            </div>
                            <div class="form-group has-feedback mb-3">
                                <label for="dir_name">Генеральный директор</label>
                                <input type="text" class="form-control" name="dir_name" id="dir_name" value="<?= htmlspecialchars($company->dir_name ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled="">
                            </div>
                            <div class="form-group has-feedback mb-3">
                                <label for="nds">Система налогообложения</label>
                                <select class="form-control" name="nds" disabled="">
                                    <option value="1" <?= (isset($company->nds) && $company->nds == 1) ? 'selected' : '' ?>>с НДС</option>
                                    <option value="2" <?= (isset($company->nds) && $company->nds == 2) ? 'selected' : '' ?>>без НДС</option>
                                </select>
                            </div>
                            <div class="form-group has-feedback mb-3">
                                <label for="dogovor">Условия поставки</label>
                                <select class="form-control" name="dogovor" disabled="">
                                    <option value="1" <?= (isset($company->dogovor) && $company->dogovor == 1) ? 'selected' : '' ?>>Договор</option>
                                    <option value="2" <?= (isset($company->dogovor) && $company->dogovor == 2) ? 'selected' : '' ?>>Счёт-договор</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card col-xl-4 float-left">
                    <div class="card-header">
                        <h5 class="mb-0 h6">Тип</h5>
                    </div>
                    <div class="card-body">
                        <div class="box-body">
                            <div class="form-group has-feedback mb-3">
                                <label for="tip">Тип взаимодействия</label>
                                <select class="form-control" name="tip" disabled="">
                                    <option value="1" <?= (isset($company->tip) && $company->tip == 1) ? 'selected' : '' ?>>Розничная торговля</option>
                                    <option value="2" <?= (isset($company->tip) && $company->tip == 2) ? 'selected' : '' ?>>Оптовая торговля</option>
                                    <option value="3" <?= (isset($company->tip) && $company->tip == 3) ? 'selected' : '' ?>>Спец. торговля</option>
                                </select>
                            </div>

                            <div class="form-group has-feedback mb-3">
                                <label for="category_opt">Категории оптовых цен</label>
                                <?php if (!empty($category) && isset($company->id)): ?>
                                    <?php foreach ($category as $item): ?>
                                        <?php
                                        $categoryopt = \R::getRow(
                                            "SELECT * FROM category, company_typeprice 
                                            WHERE category.id = company_typeprice.category_id 
                                            AND company_typeprice.company_id = ? 
                                            AND company_typeprice.category_id = ?",
                                            [$company->id, $item["id"]]
                                        );
                                        ?>
                                        <div class="form-check">
                                            <input class="form-check-input newsletter_checked" type="checkbox" disabled <?= $categoryopt ? 'checked' : '' ?>>
                                            <label class="form-check-label"><?= htmlspecialchars($item["name"], ENT_QUOTES, 'UTF-8') ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
                <div class="alert alert-warning w-100">Информация о компании отсутствует.</div>
            <?php endif; ?>
        </div>
    </div>
</section>
<!--product-end-->
