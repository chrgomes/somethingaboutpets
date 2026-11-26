<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/includes/pet-view.php';

$admin = require_admin();
$pet = pet_for((int) ($_GET['id'] ?? 0), $admin);
if (!$pet) {
    flash('error', 'Pet not found.');
    redirect('/admin/clients.php');
}
$owner = row('SELECT id, name, phone, email FROM users WHERE id = ?', [$pet['user_id']]);

layout_start($pet['name'], ['noindex' => true]);
page_banner($pet['name'], 'Owner: ' . $owner['name']);
admin_nav('clients');
?>
<div class="container sap-wide">
    <div class="sap-dash-actions">
        <a class="sap-link-btn" href="<?= e(url('/admin/clients.php?id=' . (int) $owner['id'])) ?>"><i class="bi bi-arrow-left"></i> <?= e($owner['name']) ?></a>
        <?php if ($owner['phone']): ?><a class="sap-link-btn" href="<?= e(tel_link($owner['phone'])) ?>"><i class="bi bi-telephone"></i> Call owner</a><?php endif; ?>
        <?php if ($pet['updated_at']): ?><span class="sap-muted">Profile updated <?= e(fmt_date($pet['updated_at'])) ?></span><?php endif; ?>
    </div>
    <?php render_pet_profile($pet, true); ?>
</div>
<?php layout_end();
