<?php
/**
 * Serves pet photos and documents to the pet's owner or the admin only.
 * file.php?id=123        full size
 * file.php?id=123&s=t    thumbnail (photos)
 */
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$user = current_user();
$file = $user ? row('SELECT f.*, p.user_id FROM pet_files f JOIN pets p ON p.id = f.pet_id WHERE f.id = ?', [(int) ($_GET['id'] ?? 0)]) : null;
if (!$file || ($user['role'] !== 'admin' && (int) $file['user_id'] !== (int) $user['id'])) {
    http_response_code(404);
    exit('Not found');
}
$dir = uploads_dir();
$name = $file['stored_name'];
if (($_GET['s'] ?? '') === 't' && str_ends_with($name, '.jpg') && is_file($dir . '/' . substr($name, 0, -4) . '_t.jpg')) {
    $name = substr($name, 0, -4) . '_t.jpg';
}
$path = $dir . '/' . basename($name);
if (!is_file($path)) {
    http_response_code(404);
    exit('Not found');
}
$download = preg_replace('/[^A-Za-z0-9._ -]/', '_', $file['original_name'] ?: $name);
header('Content-Type: ' . $file['mime']);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="' . $download . '"');
header('Cache-Control: private, max-age=86400');
header('X-Content-Type-Options: nosniff');
if ($file['mime'] !== 'application/pdf') {
    header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox");
}
readfile($path);
