<?php
$size_groups = [2, 6, 7, 8, 9, 10];

$category_id = isset($category_id) ? (int)$category_id : 0;

$selected_filters = [];
if (!empty($_GET['filter'])) {
    $selected_filters = explode(',', preg_replace("#[^\d,]+#", '', $_GET['filter']));
}

$groups = $this->groups ?? [];
$attrs  = $this->attrs ?? [];

// ВАЖНО:
// Поиск по кросс-номеру и поиск по названию показываем только в подборе фильтров.
// Категория фильтров = id 4.
// Для шин, камер, дисков и услуг эти поля не выводим.
$showCrossSearch = ($category_id === 4);
?>

<input type="hidden" name="current_category_id" value="<?= (int)$category_id ?>">

<?php foreach ($groups as $group_id => $group_item): ?>
    <?php $type = in_array((int)$group_id, $size_groups, true) ? 'size' : 'text'; ?>

    <section class="sky-form col-md-3" data-group-id="<?= (int)$group_id ?>">
        <div class="row1">
            <div class="col">
                <?php if (isset($attrs[$group_id])): ?>
                    <select
                        data-placeholder="<?= htmlspecialchars($group_item['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        data-type="<?= $type; ?>"
                        data-group-id="<?= (int)$group_id ?>"
                        multiple="multiple"
                        class="form-control select js-select2"
                    >
                        <?php foreach ($attrs[$group_id] as $attr_id => $value): ?>
                            <option value="<?= (int)$attr_id; ?>"
                                <?= in_array((string)$attr_id, $selected_filters, true) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endforeach; ?>

<?php if ($showCrossSearch): ?>
    <section class="sky-form col-md-3" data-group-id="4">
        <div class="row1">
            <div class="col">
                <input type="text"
                       id="cross-search"
                       class="form-control"
                       placeholder="Поиск по кросс-номеру (например: P554005)">
                <div id="cross-results" class="cross-dropdown" style="position: relative; z-index: 10;"></div>
            </div>
        </div>
    </section>

    <section class="sky-form col-md-3" data-group-id="4">
        <div class="row1">
            <div class="col">
                <input type="text"
                       id="find-search"
                       class="form-control"
                       placeholder="Поиск по названию, артикулу или модификации">
                <div id="find-results" class="cross-dropdown" style="position:relative;z-index:10;"></div>
            </div>
        </div>
    </section>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('cross-search');
    const results = document.getElementById('cross-results');

    if (!input) return; // 💥 фикс ошибки на страницах без input

    input.addEventListener('input', function () {
        const query = input.value.replace(/[\/\.\-\sb]/gi, '');

        if (query.length < 3) {
            results.innerHTML = '';
            return;
        }

        fetch('/filter/cross-search?term=' + encodeURIComponent(query))
            .then(res => res.json())
            .then(data => {
                let html = '';
                if (data.length > 0) {
                    html = '<ul class="list-group mt-2">';
                    data.forEach(item => {
                        html += `<li class="list-group-item cross-item" style="cursor:pointer;" data-id="${item.id}">${item.name}</li>`;
                    });
                    html += '</ul>';
                } else {
                    html = '<p class="mt-2">Ничего не найдено</p>';
                }
                results.innerHTML = html;

                document.querySelectorAll('.cross-item').forEach(el => {
                    el.addEventListener('click', function () {
                        const id = this.dataset.id;
                        const current = new URLSearchParams(window.location.search);
                        current.set('cross', id);
                        window.location.search = current.toString();
                    });
                });
            });
    });
});
// find
document.addEventListener('DOMContentLoaded', function(){
  const input = document.getElementById('find-search');
  const box   = document.getElementById('find-results');
  if (!input) return;
  input.addEventListener('input', () => {
    const q = input.value.trim();
    if (q.length < 2){ box.innerHTML=''; return; }
    const params = new URLSearchParams();
    params.set('term', q);
    const cat = document.querySelector('input[name="current_category_id"]');
    if (cat && +cat.value > 0) params.set('category', cat.value);
    fetch('/filter/find-search?'+params.toString())
      .then(r=>r.json())
      .then(items=>{
        box.innerHTML = items.length
          ? '<ul class="list-group mt-2">'+items.map(i=>`<li class="list-group-item find-item" data-id="${i.id}" style="cursor:pointer;">${i.name}</li>`).join('')+'</ul>'
          : '<p class="mt-2">Ничего не найдено</p>';
        box.querySelectorAll('.find-item').forEach(li=>{
          li.addEventListener('click', ()=>{
            const url = new URL(location.href);
            url.search = '';
            url.searchParams.set('find_pids', li.dataset.id);
            location.href = url.toString();
          });
        });
      });
  });
});

</script>