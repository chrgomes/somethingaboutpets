<?php
declare(strict_types=1);
require dirname(__DIR__) . '/includes/bootstrap.php';

require_admin();

if (is_post()) {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    match ($_POST['action'] ?? '') {
        'read'   => q('UPDATE contact_messages SET is_read = 1 WHERE id = ?', [$id]),
        'unread' => q('UPDATE contact_messages SET is_read = 0 WHERE id = ?', [$id]),
        'delete' => q('DELETE FROM contact_messages WHERE id = ?', [$id]),
        default  => null,
    };
    redirect('/admin/messages.php');
}

$messages = rows('SELECT * FROM contact_messages ORDER BY is_read ASC, created_at DESC LIMIT 300');

layout_start('Messages', ['noindex' => true]);
page_banner('Messages', 'From the contact form on the website.');
admin_nav('messages');
?>
<div class="container sap-wide">
    <?php if (!$messages): ?>
        <div class="sap-card"><p class="sap-muted">No messages yet.</p></div>
    <?php endif; ?>
    <?php foreach ($messages as $m): ?>
        <article class="sap-card sap-message<?= (int) $m['is_read'] ? ' is-read' : '' ?>">
            <header>
                <div>
                    <strong><?= e($m['subject'] ?: '(no subject)') ?></strong>
                    <span class="sap-muted">from <?= e($m['name']) ?> · <a href="mailto:<?= e($m['email']) ?>?subject=<?= e(rawurlencode('Re: ' . $m['subject'])) ?>"><?= e($m['email']) ?></a> · <?= e(fmt_datetime($m['created_at'])) ?></span>
                </div>
                <div class="sap-msg-actions">
                    <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><input type="hidden" name="action" value="<?= (int) $m['is_read'] ? 'unread' : 'read' ?>"><button class="sap-link-btn" type="submit"><?= (int) $m['is_read'] ? 'Mark unread' : 'Mark read' ?></button></form>
                    <form method="post" data-confirm="Delete this message?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><input type="hidden" name="action" value="delete"><button class="sap-icon-btn" type="submit" aria-label="Delete message"><i class="bi bi-trash"></i></button></form>
                </div>
            </header>
            <p><?= nl2br(e($m['message'])) ?></p>
        </article>
    <?php endforeach; ?>
</div>
<?php layout_end();
