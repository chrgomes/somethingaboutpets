<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/includes/pet-view.php';

$user = require_login();
$next = safe_next($_GET['next'] ?? $_POST['next'] ?? '', '');
$isNew = isset($_GET['new']) || (($_POST['action'] ?? '') === 'save' && empty($_POST['id']));
$petId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$pet = null;
if (!$isNew) {
    $pet = $petId ? row('SELECT * FROM pets WHERE id = ? AND user_id = ?', [$petId, $user['id']]) : null;
    if (!$pet) {
        flash('error', 'Pet not found.');
        redirect('/account/pets.php');
    }
}
$editing = $isNew || isset($_GET['edit']);
$errors = [];
$v = $pet ?: ['name' => '', 'species' => 'dog', 'sex' => '', 'desexed' => '', 'weight_kg' => null, 'emergency_authorised' => 0,
    'emergency_limit_cents' => null, 'emergency_signed_name' => '', 'photos_public_ok' => 0, 'good_with_dogs' => '',
    'good_with_cats' => '', 'good_with_kids' => '', 'off_lead' => '', 'emergency_name' => '', 'emergency_phone' => ''];
foreach (array_keys(pet_text_fields()) as $f) {
    $v[$f] ??= '';
}
$self = fn(string $extra = '') => '/account/pet.php?id=' . (int) ($pet['id'] ?? 0) . $extra;

if (is_post()) {
    csrf_check();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save') {
        [$v, $errors] = pet_form_input($pet);
        if (!$errors) {
            $id = pet_save($v, (int) $user['id'], $pet ? (int) $pet['id'] : null);
            $uploadErrors = [];
            foreach (uploaded_list('photos') as $up) {
                if (($up['error'] ?? 4) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                try {
                    pet_store_upload($id, $up, 'photo');
                } catch (InvalidArgumentException $e) {
                    $uploadErrors[] = $e->getMessage();
                }
            }
            flash($uploadErrors ? 'error' : 'success', $v['name'] . '\'s profile has been saved.' . ($uploadErrors ? ' But a photo didn\'t upload: ' . $uploadErrors[0] : ''));
            redirect($next ?: '/account/pet.php?id=' . $id);
        }
        $v += $pet ?: [];
        $editing = true;
    }

    if ($pet && in_array($action, ['upload', 'set_main', 'delete_file', 'add_vax', 'delete_vax', 'delete_pet'], true)) {
        try {
            switch ($action) {
                case 'upload':
                    $kind = in_array($_POST['kind'] ?? '', ['photo', 'id'], true) ? $_POST['kind'] : 'photo';
                    $n = 0;
                    foreach (uploaded_list('files') as $up) {
                        if (($up['error'] ?? 4) !== UPLOAD_ERR_NO_FILE) {
                            pet_store_upload((int) $pet['id'], $up, $kind, input('label', 120));
                            $n++;
                        }
                    }
                    flash($n ? 'success' : 'error', $n ? ($n === 1 ? 'Uploaded.' : "$n files uploaded.") : 'Please choose a file to upload.');
                    break;
                case 'set_main':
                    $f = row("SELECT * FROM pet_files WHERE id = ? AND pet_id = ? AND kind = 'photo'", [(int) $_POST['file_id'], $pet['id']]);
                    if ($f) {
                        q('UPDATE pets SET photo_file_id = ? WHERE id = ?', [$f['id'], $pet['id']]);
                        flash('success', 'Profile photo updated.');
                    }
                    break;
                case 'delete_file':
                    $f = row('SELECT * FROM pet_files WHERE id = ? AND pet_id = ?', [(int) $_POST['file_id'], $pet['id']]);
                    if ($f) {
                        pet_delete_file($f);
                        flash('success', 'Deleted.');
                    }
                    break;
                case 'add_vax':
                    $name = input('vax_name', 80);
                    $given = (string) ($_POST['given_on'] ?? '');
                    $exp = (string) ($_POST['expires_on'] ?? '');
                    if ($name === '') {
                        throw new InvalidArgumentException('Please enter the vaccination name.');
                    }
                    if (($given !== '' && !valid_date($given)) || ($exp !== '' && !valid_date($exp))) {
                        throw new InvalidArgumentException('Please check the dates.');
                    }
                    $fileId = null;
                    $ups = array_filter(uploaded_list('vax_file'), fn($u) => ($u['error'] ?? 4) !== UPLOAD_ERR_NO_FILE);
                    if ($ups) {
                        $fileId = pet_store_upload((int) $pet['id'], reset($ups), 'vaccine', $name);
                    }
                    q('INSERT INTO pet_vaccinations (pet_id, name, given_on, expires_on, file_id, created_at) VALUES (?, ?, ?, ?, ?, ?)',
                        [$pet['id'], $name, $given ?: null, $exp ?: null, $fileId, now_sql()]);
                    flash('success', $name . ' added.');
                    break;
                case 'delete_vax':
                    $vx = row('SELECT * FROM pet_vaccinations WHERE id = ? AND pet_id = ?', [(int) $_POST['vax_id'], $pet['id']]);
                    if ($vx) {
                        if ($vx['file_id'] && ($f = row('SELECT * FROM pet_files WHERE id = ? AND pet_id = ?', [$vx['file_id'], $pet['id']]))) {
                            pet_delete_file($f);
                        }
                        q('DELETE FROM pet_vaccinations WHERE id = ?', [$vx['id']]);
                        flash('success', 'Vaccination removed.');
                    }
                    break;
                case 'delete_pet':
                    $active = (int) scalar(
                        "SELECT COUNT(*) FROM booking_pets bp JOIN bookings b ON b.id = bp.booking_id
                         WHERE bp.pet_id = ? AND b.status IN ('pending','confirmed') AND b.end_at >= ?", [$pet['id'], now_sql()]);
                    if ($active) {
                        throw new InvalidArgumentException($pet['name'] . ' has upcoming bookings. Cancel those first.');
                    }
                    pet_delete($pet);
                    flash('success', $pet['name'] . '\'s profile was removed.');
                    redirect('/account/pets.php');
            }
        } catch (InvalidArgumentException $e) {
            flash('error', $e->getMessage());
        }
        redirect($self('#' . ($action === 'add_vax' || $action === 'delete_vax' ? 'manage-vax' : 'manage-files')));
    }
}

$opt = fn(array $choices, ?string $cur, string $blank = 'Choose…') => '<option value="">' . e($blank) . '</option>'
    . implode('', array_map(fn($k, $l) => '<option value="' . e($k) . '"' . ((string) $cur === (string) $k ? ' selected' : '') . '>' . e($l) . '</option>', array_keys($choices), $choices));

$title = $isNew ? 'Add a pet' : ($editing ? 'Edit ' . $pet['name'] : $pet['name']);
layout_start($title, ['noindex' => true, 'scripts' => ['assets/js/pets.js']]);
page_banner($title, $isNew ? 'The more you tell us, the better we can look after them.' : '');
account_nav('pets');
?>
<div class="container sap-wide">
    <?= render_flashes() ?>
    <?php foreach ($errors as $er): ?><div class="sap-alert sap-alert-error" role="alert"><?= e($er) ?></div><?php endforeach; ?>

<?php if ($editing): ?>
    <form method="post" enctype="multipart/form-data" class="sap-form pet-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <?php if ($pet): ?><input type="hidden" name="id" value="<?= (int) $pet['id'] ?>"><?php endif; ?>

        <nav class="pet-form-nav" aria-label="Sections">
            <a href="#s-basics">Basics</a><a href="#s-health">Health</a><a href="#s-behaviour">Behaviour</a>
            <a href="#s-walk">Walking</a><a href="#s-feed">Feeding</a><a href="#s-vet">Vet &amp; emergency</a><a href="#s-id">ID</a>
        </nav>

        <fieldset class="sap-card" id="s-basics">
            <legend>Basics</legend>
            <div class="row g-3">
                <div class="col-md-6"><label for="name">Name *</label><input id="name" name="name" required maxlength="80" value="<?= e($v['name']) ?>"></div>
                <div class="col-md-6"><label for="species">Type of pet</label><select id="species" name="species" class="no-nice"><?= $opt(PET_SPECIES, $v['species'], 'Choose…') ?></select></div>
                <div class="col-md-6"><label for="breed">Breed</label><input id="breed" name="breed" maxlength="80" value="<?= e($v['breed']) ?>" placeholder="e.g. Labrador cross"></div>
                <div class="col-md-6"><label for="age">Age</label><input id="age" name="age" maxlength="40" value="<?= e($v['age']) ?>" placeholder="e.g. 4 years"></div>
                <div class="col-6 col-md-4"><label for="weight_kg">Weight (kg)</label><input id="weight_kg" name="weight_kg" type="number" step="0.1" min="0" max="200" inputmode="decimal" value="<?= e($v['weight_kg'] ?? '') ?>"></div>
                <div class="col-6 col-md-4"><label for="sex">Sex</label><select id="sex" name="sex" class="no-nice"><?= $opt(PET_SEX, $v['sex']) ?></select></div>
                <div class="col-md-4"><label for="desexed">Desexed (spayed/neutered)</label><select id="desexed" name="desexed" class="no-nice"><?= $opt(PET_YNU, $v['desexed']) ?></select></div>
            </div>
            <?php if ($isNew): ?>
                <label for="photos">Photos</label>
                <input id="photos" type="file" name="photos[]" accept="image/*" multiple class="pet-file-input" data-resize>
                <p class="sap-muted pet-hint">A clear photo helps us recognise your pet. You can add more later.</p>
            <?php endif; ?>
        </fieldset>

        <fieldset class="sap-card" id="s-health">
            <legend>Health</legend>
            <label for="medical">Health conditions, medication and allergies</label>
            <textarea id="medical" name="medical" rows="4" maxlength="4000" placeholder="e.g. Apoquel 16mg with breakfast for itchy skin. Allergic to chicken."><?= e($v['medical']) ?></textarea>
            <p class="sap-muted pet-hint"><?= $isNew ? 'You can add vaccinations and records after saving.' : 'Vaccinations and records are added on the profile page.' ?></p>
        </fieldset>

        <fieldset class="sap-card" id="s-behaviour">
            <legend>Behaviour</legend>
            <div class="row g-3">
                <div class="col-md-4"><label for="good_with_dogs">Good with other dogs?</label><select id="good_with_dogs" name="good_with_dogs" class="no-nice"><?= $opt(PET_YNU, $v['good_with_dogs']) ?></select></div>
                <div class="col-md-4"><label for="good_with_cats">Good with cats?</label><select id="good_with_cats" name="good_with_cats" class="no-nice"><?= $opt(PET_YNU, $v['good_with_cats']) ?></select></div>
                <div class="col-md-4"><label for="good_with_kids">Good with children?</label><select id="good_with_kids" name="good_with_kids" class="no-nice"><?= $opt(PET_YNU, $v['good_with_kids']) ?></select></div>
            </div>
            <label for="behaviour">Behaviour around other animals and people</label>
            <textarea id="behaviour" name="behaviour" rows="3" maxlength="4000" placeholder="e.g. Friendly with most dogs but reactive to big dogs on lead. Has never bitten."><?= e($v['behaviour']) ?></textarea>
            <label for="anxiety_triggers">Anxiety triggers</label>
            <textarea id="anxiety_triggers" name="anxiety_triggers" rows="3" maxlength="4000" placeholder="e.g. Fireworks, thunder, skateboards. Calms down with a Kong in her crate."><?= e($v['anxiety_triggers']) ?></textarea>
        </fieldset>

        <fieldset class="sap-card" id="s-walk">
            <legend>Walking</legend>
            <div class="row g-3"><div class="col-md-6"><label for="off_lead">OK off lead (where allowed)?</label><select id="off_lead" name="off_lead" class="no-nice"><?= $opt(PET_YNU, $v['off_lead']) ?></select></div></div>
            <label for="walking">Walking preferences</label>
            <textarea id="walking" name="walking" rows="3" maxlength="4000" placeholder="e.g. Harness not collar, pulls at the start, loves the beach, avoid hot pavements, recall is good with treats."><?= e($v['walking']) ?></textarea>
        </fieldset>

        <fieldset class="sap-card" id="s-feed">
            <legend>Feeding</legend>
            <label for="feeding">Feeding instructions</label>
            <textarea id="feeding" name="feeding" rows="3" maxlength="4000" placeholder="e.g. 1 cup Hill's dry food at 7am and 5pm. Food is in the pantry. No table scraps."><?= e($v['feeding']) ?></textarea>
        </fieldset>

        <fieldset class="sap-card" id="s-vet">
            <legend>Vet &amp; emergency</legend>
            <div class="row g-3">
                <div class="col-md-6"><label for="vet_clinic">Vet clinic</label><input id="vet_clinic" name="vet_clinic" maxlength="120" value="<?= e($v['vet_clinic']) ?>"></div>
                <div class="col-md-6"><label for="vet_phone">Vet phone</label><input id="vet_phone" name="vet_phone" type="tel" maxlength="40" value="<?= e($v['vet_phone']) ?>"></div>
                <div class="col-12"><label for="vet_address">Vet address</label><input id="vet_address" name="vet_address" maxlength="255" value="<?= e($v['vet_address']) ?>"></div>
                <div class="col-md-6"><label for="emergency_name">Emergency contact (if we can't reach you)</label><input id="emergency_name" name="emergency_name" maxlength="120" value="<?= e($v['emergency_name']) ?>" placeholder="Name"></div>
                <div class="col-md-6"><label for="emergency_phone">Emergency contact phone</label><input id="emergency_phone" name="emergency_phone" type="tel" maxlength="40" value="<?= e($v['emergency_phone']) ?>"></div>
            </div>
            <div class="pet-auth-box">
                <label class="sap-check"><input type="checkbox" name="emergency_authorised" value="1" id="emergency_authorised" <?= (int) $v['emergency_authorised'] ? 'checked' : '' ?>>
                    <span><strong>Emergency vet authorisation.</strong> If my pet needs urgent care and neither I nor my emergency contact can be reached, I authorise <?= e(cfg('app.name')) ?> to take them to my vet or the nearest available vet, and to agree to treatment up to the limit below. I'll pay the vet costs.</span></label>
                <div class="row g-3 pet-auth-fields">
                    <div class="col-md-5"><label for="emergency_limit">Spending limit (<?= e(cfg('booking.currency_symbol', '$')) ?>)</label>
                        <input id="emergency_limit" name="emergency_limit" type="number" min="0" step="50" inputmode="numeric" placeholder="e.g. 1000" value="<?= $v['emergency_limit_cents'] !== null ? (int) ((int) $v['emergency_limit_cents'] / 100) : '' ?>">
                        <p class="sap-muted pet-hint">Leave empty for "whatever is needed".</p></div>
                    <div class="col-md-7"><label for="emergency_signed_name">Type your full name to sign</label>
                        <input id="emergency_signed_name" name="emergency_signed_name" maxlength="120" autocomplete="name" value="<?= e($v['emergency_signed_name']) ?>"></div>
                </div>
            </div>
        </fieldset>

        <fieldset class="sap-card" id="s-id">
            <legend>Identification</legend>
            <div class="row g-3">
                <div class="col-12"><label for="colour">Colour and markings</label><input id="colour" name="colour" maxlength="120" value="<?= e($v['colour']) ?>" placeholder="e.g. Black with white chest and front paws"></div>
                <div class="col-md-6"><label for="microchip">Microchip number</label><input id="microchip" name="microchip" maxlength="40" inputmode="numeric" value="<?= e($v['microchip']) ?>"></div>
                <div class="col-md-6"><label for="council_reg">Council registration number</label><input id="council_reg" name="council_reg" maxlength="40" value="<?= e($v['council_reg']) ?>"></div>
            </div>
            <label class="sap-check"><input type="checkbox" name="photos_public_ok" value="1" <?= (int) $v['photos_public_ok'] ? 'checked' : '' ?>> It's OK to share photos of my pet on social media (never with my address)</label>
            <label for="notes">Anything else we should know?</label>
            <textarea id="notes" name="notes" rows="3" maxlength="4000"><?= e($v['notes']) ?></textarea>
        </fieldset>

        <div class="pet-form-actions">
            <button class="primary-btn2" type="submit"><?= $isNew ? 'Save pet' : 'Save changes' ?></button>
            <a class="sap-link-btn" href="<?= e(url($next ?: ($pet ? $self() : '/account/pets.php'))) ?>">Cancel</a>
        </div>
    </form>

<?php else: ?>
    <div class="sap-dash-actions">
        <a class="primary-btn2" href="<?= e(url($self('&edit=1'))) ?>"><i class="bi bi-pencil"></i> Edit profile</a>
        <a class="sap-link-btn" href="<?= e(url('/book.php')) ?>">Book for <?= e($pet['name']) ?></a>
        <a class="sap-link-btn" href="<?= e(url('/account/pets.php')) ?>"><i class="bi bi-arrow-left"></i> All pets</a>
    </div>
    <?php $pct = pet_completeness($pet); if ($pct < 100): ?>
        <div class="pet-progress sap-card">
            <div><strong>Profile <?= $pct ?>% complete</strong> <span class="sap-muted">A full profile helps us care for <?= e($pet['name']) ?> safely.</span></div>
            <div class="dash-bar-t"><span style="width:<?= $pct ?>%"></span></div>
        </div>
    <?php endif; ?>

    <?php render_pet_profile($pet); ?>

    <div class="row g-4">
        <div class="col-lg-6">
            <section class="sap-card" id="manage-vax">
                <h3><i class="bi bi-shield-check"></i> Add a vaccination</h3>
                <form method="post" enctype="multipart/form-data" class="sap-form">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $pet['id'] ?>"><input type="hidden" name="action" value="add_vax">
                    <label for="vax_name">Vaccination</label>
                    <input id="vax_name" name="vax_name" list="vax-names" maxlength="80" required placeholder="<?= $pet['species'] === 'cat' ? 'e.g. F3 (cat flu + enteritis)' : 'e.g. C5 (parvo, distemper, kennel cough)' ?>">
                    <datalist id="vax-names">
                        <?php foreach (($pet['species'] === 'cat' ? ['F3', 'F4', 'FIV', 'Rabies'] : ['C3', 'C5', 'Kennel cough', 'Leptospirosis', 'Rabies']) as $n): ?><option value="<?= e($n) ?>"><?php endforeach; ?>
                    </datalist>
                    <div class="row g-3">
                        <div class="col-6"><label for="given_on">Date given</label><input id="given_on" type="date" name="given_on"></div>
                        <div class="col-6"><label for="expires_on">Expires / due</label><input id="expires_on" type="date" name="expires_on"></div>
                    </div>
                    <label for="vax_file">Certificate or vet record <span class="sap-muted">(photo or PDF, optional)</span></label>
                    <input id="vax_file" type="file" name="vax_file" accept="image/*,application/pdf" class="pet-file-input" data-resize>
                    <button class="primary-btn2" type="submit">Add vaccination</button>
                </form>
                <?php $vax = pet_vaccinations((int) $pet['id']); if ($vax): ?>
                    <h4>Remove a vaccination</h4>
                    <ul class="pet-manage-list">
                        <?php foreach ($vax as $vx): ?>
                            <li><span><?= e($vx['name']) ?><?= $vx['expires_on'] ? ' <span class="sap-muted">· ' . e(fmt_date($vx['expires_on'])) . '</span>' : '' ?></span>
                                <form method="post" data-confirm="Remove <?= e($vx['name']) ?>?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $pet['id'] ?>"><input type="hidden" name="action" value="delete_vax"><input type="hidden" name="vax_id" value="<?= (int) $vx['id'] ?>">
                                    <button class="sap-icon-btn" type="submit" aria-label="Remove <?= e($vx['name']) ?>"><i class="bi bi-trash"></i></button></form></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>
        <div class="col-lg-6">
            <section class="sap-card" id="manage-files">
                <h3><i class="bi bi-camera"></i> Photos &amp; ID documents</h3>
                <form method="post" enctype="multipart/form-data" class="sap-form">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $pet['id'] ?>"><input type="hidden" name="action" value="upload">
                    <div class="row g-3">
                        <div class="col-md-6"><label for="kind">What is it?</label>
                            <select id="kind" name="kind" class="no-nice"><option value="photo">Photo of <?= e($pet['name']) ?></option><option value="id">ID document (registration, microchip papers)</option></select></div>
                        <div class="col-md-6"><label for="label">Label <span class="sap-muted">(optional)</span></label><input id="label" name="label" maxlength="120" placeholder="e.g. Council tag 2026"></div>
                    </div>
                    <label for="files">Files</label>
                    <input id="files" type="file" name="files[]" accept="image/*,application/pdf" multiple required class="pet-file-input" data-resize>
                    <button class="primary-btn2" type="submit">Upload</button>
                </form>
                <?php $all = pet_files((int) $pet['id']); $all = array_filter($all, fn($f) => $f['kind'] !== 'vaccine'); if ($all): ?>
                    <div class="pet-manage-grid">
                        <?php foreach ($all as $f): $isMain = (int) $pet['photo_file_id'] === (int) $f['id']; ?>
                            <div class="pet-thumb<?= $isMain ? ' is-main' : '' ?>">
                                <a href="<?= e(file_url((int) $f['id'])) ?>" target="_blank" rel="noopener">
                                    <?php if ($f['mime'] === 'application/pdf'): ?><span class="pet-thumb-doc"><i class="bi bi-file-earmark-pdf"></i><?= e(mb_strimwidth($f['label'] ?: $f['original_name'], 0, 22, '…')) ?></span>
                                    <?php else: ?><img src="<?= e(file_url((int) $f['id'], true)) ?>" alt="<?= e($f['label'] ?: $pet['name']) ?>" loading="lazy"><?php endif; ?>
                                </a>
                                <div class="pet-thumb-actions">
                                    <?php if ($f['kind'] === 'photo' && !$isMain): ?>
                                        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $pet['id'] ?>"><input type="hidden" name="action" value="set_main"><input type="hidden" name="file_id" value="<?= (int) $f['id'] ?>"><button class="sap-icon-btn" type="submit" title="Use as profile photo" aria-label="Use as profile photo"><i class="bi bi-star"></i></button></form>
                                    <?php elseif ($isMain): ?><span class="pet-main-tag"><i class="bi bi-star-fill"></i> Profile</span><?php else: ?><span class="pet-main-tag">ID</span><?php endif; ?>
                                    <form method="post" data-confirm="Delete this file?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $pet['id'] ?>"><input type="hidden" name="action" value="delete_file"><input type="hidden" name="file_id" value="<?= (int) $f['id'] ?>"><button class="sap-icon-btn" type="submit" aria-label="Delete file"><i class="bi bi-trash"></i></button></form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <form method="post" class="pet-delete" data-confirm="Remove <?= e($pet['name']) ?>'s profile and all photos? This can't be undone.">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $pet['id'] ?>"><input type="hidden" name="action" value="delete_pet">
        <button class="sap-btn-danger" type="submit">Remove <?= e($pet['name']) ?>'s profile</button>
    </form>
<?php endif; ?>
</div>
<?php layout_end();
