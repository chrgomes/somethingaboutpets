<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

require_admin();
$errors = [];

if (is_post()) {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    $name = input('name', 80);
    $kind = ($_POST['kind'] ?? 'slot') === 'stay' ? 'stay' : 'slot';
    $duration = max(0, min(720, (int) ($_POST['duration_minutes'] ?? 60)));
    $priceRaw = trim((string) ($_POST['price'] ?? ''));
    $price = $priceRaw === '' ? null : (int) round(((float) str_replace([',', '$'], '', $priceRaw)) * 100);
    $active = !empty($_POST['active']) ? 1 : 0;
    $sort = (int) ($_POST['sort_order'] ?? 0);
    $desc = input('description', 1000);

    if ($name === '') {
        $errors[] = 'Please enter a service name.';
    }
    if ($kind === 'slot' && $duration < 15) {
        $errors[] = 'Time-slot services need a length of at least 15 minutes.';
    }
    if ($price !== null && $price < 0) {
        $errors[] = 'Price can\'t be negative.';
    }
    if (!$errors) {
        if ($id) {
            q('UPDATE services SET name = ?, description = ?, kind = ?, duration_minutes = ?, price_cents = ?, active = ?, sort_order = ? WHERE id = ?',
                [$name, $desc, $kind, $duration, $price, $active, $sort, $id]);
        } else {
            q('INSERT INTO services (name, description, kind, duration_minutes, price_cents, active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$name, $desc, $kind, $duration, $price, $active, $sort]);
        }
        flash('success', $name . ' saved.');
        redirect('/admin/services.php');
    }
}

$list = services(false);
$blank = ['id' => 0, 'name' => '', 'description' => '', 'kind' => 'slot', 'duration_minutes' => 60, 'price_cents' => null, 'active' => 1, 'sort_order' => count($list) + 1];

layout_start('Services & prices', ['noindex' => true]);
page_banner('Services & prices', 'Prices for in-house sitting are per night. Leave the price empty to show "Price on request".');
admin_nav('services');
?>
<div class="container sap-wide">
    <?= render_flashes() ?>
    <?php foreach ($errors as $er): ?><div class="sap-alert sap-alert-error" role="alert"><?= e($er) ?></div><?php endforeach; ?>
    <div class="sap-service-grid">
        <?php foreach (array_merge($list, [$blank]) as $s): $new = !(int) $s['id']; ?>
            <form method="post" class="sap-card sap-form sap-service-form<?= $new ? ' sap-service-new' : '' ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                <h2><?= $new ? 'Add a service' : e($s['name']) ?></h2>
                <label for="n<?= (int) $s['id'] ?>">Name</label>
                <input id="n<?= (int) $s['id'] ?>" name="name" maxlength="80" value="<?= e($s['name']) ?>" <?= $new ? '' : 'required' ?>>
                <label for="d<?= (int) $s['id'] ?>">Short description</label>
                <textarea id="d<?= (int) $s['id'] ?>" name="description" rows="2" maxlength="1000"><?= e($s['description']) ?></textarea>
                <div class="row g-2">
                    <div class="col-6">
                        <label for="k<?= (int) $s['id'] ?>">Booked by</label>
                        <select id="k<?= (int) $s['id'] ?>" name="kind" class="no-nice">
                            <option value="slot" <?= $s['kind'] === 'slot' ? 'selected' : '' ?>>Time slot</option>
                            <option value="stay" <?= $s['kind'] === 'stay' ? 'selected' : '' ?>>Date range (nights)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label for="m<?= (int) $s['id'] ?>">Length (minutes)</label>
                        <input id="m<?= (int) $s['id'] ?>" type="number" name="duration_minutes" min="0" max="720" step="5" value="<?= (int) $s['duration_minutes'] ?>">
                    </div>
                    <div class="col-6">
                        <label for="p<?= (int) $s['id'] ?>">Price (<?= e(cfg('booking.currency_symbol', '$')) ?>)</label>
                        <input id="p<?= (int) $s['id'] ?>" type="number" name="price" min="0" step="0.01" placeholder="On request" value="<?= $s['price_cents'] === null ? '' : e(number_format((int) $s['price_cents'] / 100, 2, '.', '')) ?>">
                    </div>
                    <div class="col-6">
                        <label for="o<?= (int) $s['id'] ?>">Order</label>
                        <input id="o<?= (int) $s['id'] ?>" type="number" name="sort_order" value="<?= (int) $s['sort_order'] ?>">
                    </div>
                </div>
                <label class="sap-check"><input type="checkbox" name="active" value="1" <?= (int) $s['active'] ? 'checked' : '' ?>> Available to book</label>
                <button class="primary-btn2" type="submit"><?= $new ? 'Add service' : 'Save' ?></button>
            </form>
        <?php endforeach; ?>
    </div>
</div>
<?php layout_end();
