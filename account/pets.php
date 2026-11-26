<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

$user = require_login();

// Old links: ?add=1 and ?edit=ID now live on pet.php
if (isset($_GET['add'])) {
    redirect('/account/pet.php?new=1' . (isset($_GET['next']) ? '&next=' . rawurlencode(safe_next($_GET['next'], '')) : ''));
}
if (isset($_GET['edit'])) {
    redirect('/account/pet.php?id=' . (int) $_GET['edit'] . '&edit=1');
}

$pets = rows('SELECT * FROM pets WHERE user_id = ? ORDER BY name', [$user['id']]);

layout_start('My pets', ['noindex' => true]);
page_banner('My pets', 'Everything we need to know to look after them, in one place.');
account_nav('pets');
?>
<div class="container sap-wide">
    <?= render_flashes() ?>
    <div class="pet-grid">
        <?php foreach ($pets as $p): $pct = pet_completeness($p); $alert = pet_vaccine_alert((int) $p['id']); ?>
            <a class="sap-card pet-card" href="<?= e(url('/account/pet.php?id=' . (int) $p['id'])) ?>">
                <span class="pet-card-photo">
                    <?php if ($u = pet_photo_url($p)): ?><img src="<?= e($u) ?>" alt="" loading="lazy">
                    <?php else: ?><?= pet_mark('', false, '') ?><?php endif; ?>
                </span>
                <span class="pet-card-body">
                    <strong><?= e($p['name']) ?></strong>
                    <span class="sap-muted"><?= e(PET_SPECIES[$p['species']] ?? ucfirst($p['species'])) ?><?= $p['breed'] ? ' · ' . e($p['breed']) : '' ?><?= $p['age'] ? ' · ' . e($p['age']) : '' ?></span>
                    <?php if ($alert): ?><span class="pet-warn"><i class="bi bi-exclamation-triangle"></i> <?= $alert === 'expired' ? 'Vaccination expired' : 'Vaccination due soon' ?></span><?php endif; ?>
                    <span class="pet-card-progress"><span class="dash-bar-t"><span style="width:<?= $pct ?>%"></span></span><span><?= $pct ?>% complete</span></span>
                </span>
            </a>
        <?php endforeach; ?>
        <a class="sap-card pet-card pet-card-add" href="<?= e(url('/account/pet.php?new=1')) ?>">
            <span class="pet-add-icon"><i class="bi bi-plus-lg"></i></span>
            <strong><?= $pets ? 'Add another pet' : 'Add your first pet' ?></strong>
            <span class="sap-muted">Photo, health, behaviour, feeding, vet details and more</span>
        </a>
    </div>
</div>
<?php layout_end();
