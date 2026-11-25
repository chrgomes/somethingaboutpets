<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_login();
$next = safe_next($_GET['next'] ?? $_POST['next'] ?? '', '');
$editId = (int) ($_GET['edit'] ?? $_POST['id'] ?? 0);
$editing = $editId ? row('SELECT * FROM pets WHERE id = ? AND user_id = ?', [$editId, $user['id']]) : null;
$showForm = isset($_GET['add']) || $editing;
$errors = [];
$v = $editing ?: ['name' => '', 'species' => 'dog', 'breed' => '', 'age' => '', 'notes' => ''];
$speciesList = ['dog' => 'Dog', 'cat' => 'Cat', 'other' => 'Other'];

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete' && $editing) {
        $active = (int) scalar(
            "SELECT COUNT(*) FROM booking_pets bp JOIN bookings b ON b.id = bp.booking_id
             WHERE bp.pet_id = ? AND b.status IN ('pending','confirmed') AND b.end_at >= ?",
            [$editing['id'], now_sql()]
        );
        if ($active) {
            flash('error', $editing['name'] . ' has upcoming bookings. Cancel those first.');
        } else {
            q('DELETE FROM pets WHERE id = ? AND user_id = ?', [$editing['id'], $user['id']]);
            flash('success', $editing['name'] . ' was removed.');
        }
        redirect('/account/pets.php');
    }

    $v = [
        'name'    => input('name', 80),
        'species' => array_key_exists($_POST['species'] ?? '', $speciesList) ? $_POST['species'] : 'other',
        'breed'   => input('breed', 80),
        'age'     => input('age', 40),
        'notes'   => input('notes', 4000),
    ];
    if ($v['name'] === '') {
        $errors[] = 'Please enter your pet\'s name.';
    }
    if (!$errors) {
        if ($editing) {
            q('UPDATE pets SET name = ?, species = ?, breed = ?, age = ?, notes = ? WHERE id = ? AND user_id = ?',
                [$v['name'], $v['species'], $v['breed'], $v['age'], $v['notes'], $editing['id'], $user['id']]);
        } else {
            q('INSERT INTO pets (user_id, name, species, breed, age, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$user['id'], $v['name'], $v['species'], $v['breed'], $v['age'], $v['notes'], now_sql()]);
        }
        flash('success', $v['name'] . ' has been saved.');
        redirect($next ?: '/account/pets.php');
    }
    $showForm = true;
}

$pets = rows('SELECT * FROM pets WHERE user_id = ? ORDER BY name', [$user['id']]);

layout_start('My pets', ['noindex' => true]);
page_banner('My pets', 'Tell us about your pets so we can look after them properly.');
account_nav('pets');
?>
<div class="container sap-wide">
    <?= render_flashes() ?>
    <?php if ($showForm): ?>
        <div class="sap-card sap-narrow-card">
            <h2><?= $editing ? 'Edit ' . e($editing['name']) : 'Add a pet' ?></h2>
            <?php foreach ($errors as $er): ?><div class="sap-alert sap-alert-error" role="alert"><?= e($er) ?></div><?php endforeach; ?>
            <form method="post" class="sap-form">
                <?= csrf_field() ?>
                <input type="hidden" name="next" value="<?= e($next) ?>">
                <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $editing['id'] ?>"><?php endif; ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name">Name</label>
                        <input id="name" name="name" required maxlength="80" value="<?= e($v['name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="species">Type of pet</label>
                        <select id="species" name="species">
                            <?php foreach ($speciesList as $k => $lbl): ?>
                                <option value="<?= e($k) ?>" <?= $v['species'] === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="breed">Breed</label>
                        <input id="breed" name="breed" maxlength="80" value="<?= e($v['breed']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="age">Age</label>
                        <input id="age" name="age" maxlength="40" placeholder="e.g. 4 years" value="<?= e($v['age']) ?>">
                    </div>
                </div>
                <label for="notes">Care notes</label>
                <textarea id="notes" name="notes" rows="5" maxlength="4000" placeholder="Feeding, medication, vet details, behaviour with other dogs, anything we should know"><?= e($v['notes']) ?></textarea>
                <div class="sap-dash-actions">
                    <button class="primary-btn2" type="submit">Save</button>
                    <a class="sap-link-btn" href="<?= e(url($next ?: '/account/pets.php')) ?>">Cancel</a>
                </div>
            </form>
            <?php if ($editing): ?>
                <form method="post" class="sap-delete-form" data-confirm="Remove <?= e($editing['name']) ?>?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
                    <input type="hidden" name="action" value="delete">
                    <button class="sap-btn-danger" type="submit">Remove pet</button>
                </form>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="sap-dash-actions">
            <a class="primary-btn2" href="<?= e(url('/account/pets.php?add=1')) ?>">Add a pet</a>
        </div>
        <?php if (!$pets): ?>
            <div class="sap-card sap-empty">
                <?= pet_mark('pet-mark--empty', true, '') ?>
                <p>You haven't added any pets yet.</p>
            </div>
        <?php else: ?>
            <div class="sap-pet-grid">
                <?php foreach ($pets as $p): ?>
                    <div class="sap-card sap-pet-card">
                        <h3><?= e($p['name']) ?></h3>
                        <p class="sap-muted"><?= e(ucfirst($p['species'])) ?><?= $p['breed'] ? ' · ' . e($p['breed']) : '' ?><?= $p['age'] ? ' · ' . e($p['age']) : '' ?></p>
                        <?php if ($p['notes']): ?><p><?= nl2br(e(mb_strimwidth($p['notes'], 0, 180, '…'))) ?></p><?php endif; ?>
                        <a class="sap-link-btn" href="<?= e(url('/account/pets.php?edit=' . (int) $p['id'])) ?>">Edit</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php layout_end();
