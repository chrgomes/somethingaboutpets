<?php
declare(strict_types=1);

/*
 * Pet profiles: details, photos/ID/vaccination files and vaccination records.
 *
 * Uploaded files are kept OUTSIDE the public web folder when possible (config uploads.path) and are only ever
 * served through file.php to the pet's owner or the admin. Photos are resized and re-saved, which also
 * removes hidden data such as the GPS location phones add to pictures.
 */

const PET_YNU = ['yes' => 'Yes', 'no' => 'No', 'unsure' => 'Not sure'];
const PET_SEX = ['female' => 'Female', 'male' => 'Male'];
const PET_SPECIES = ['dog' => 'Dog', 'cat' => 'Cat', 'other' => 'Other'];
const PET_MAX_FILES = 30;
const PET_MAX_BYTES = 12 * 1024 * 1024;

/** A pet the given user may see (owner, or any pet for the admin). */
function pet_for(int $petId, array $user): ?array
{
    $pet = row('SELECT * FROM pets WHERE id = ?', [$petId]);
    if (!$pet || ($user['role'] !== 'admin' && (int) $pet['user_id'] !== (int) $user['id'])) {
        return null;
    }
    return $pet;
}

/** Text fields that the profile form saves: name => [max length, label]. */
function pet_text_fields(): array
{
    return [
        'name' => [80, 'Name'], 'breed' => [80, 'Breed'], 'age' => [40, 'Age'], 'colour' => [120, 'Colour and markings'],
        'microchip' => [40, 'Microchip number'], 'council_reg' => [40, 'Council registration number'],
        'behaviour' => [4000, 'Behaviour around other animals and people'], 'anxiety_triggers' => [4000, 'Anxiety triggers'],
        'walking' => [4000, 'Walking preferences'], 'feeding' => [4000, 'Feeding instructions'], 'medical' => [4000, 'Health, medication and allergies'],
        'vet_clinic' => [120, 'Vet clinic'], 'vet_phone' => [40, 'Vet phone'], 'vet_address' => [255, 'Vet address'],
        'emergency_name' => [120, 'Emergency contact name'], 'emergency_phone' => [40, 'Emergency contact phone'],
        'notes' => [4000, 'Anything else'],
    ];
}

/** Read and validate the profile form. Returns [values, errors]. */
function pet_form_input(?array $existing): array
{
    $v = [];
    foreach (pet_text_fields() as $f => [$max]) {
        $v[$f] = input($f, $max);
    }
    $pick = fn($k, $allowed) => array_key_exists($_POST[$k] ?? '', $allowed) ? $_POST[$k] : '';
    $v['species'] = $pick('species', PET_SPECIES) ?: 'other';
    $v['sex'] = $pick('sex', PET_SEX);
    $v['desexed'] = $pick('desexed', PET_YNU);
    foreach (['good_with_dogs', 'good_with_cats', 'good_with_kids', 'off_lead'] as $k) {
        $v[$k] = $pick($k, PET_YNU);
    }
    $w = trim(str_replace(',', '.', (string) ($_POST['weight_kg'] ?? '')));
    $v['weight_kg'] = $w === '' ? null : round((float) $w, 1);
    $v['photos_public_ok'] = !empty($_POST['photos_public_ok']) ? 1 : 0;

    // Emergency vet authorisation: a ticked box plus the owner's typed name, timestamped.
    $v['emergency_authorised'] = !empty($_POST['emergency_authorised']) ? 1 : 0;
    $lim = trim(str_replace([',', '$'], '', (string) ($_POST['emergency_limit'] ?? '')));
    $v['emergency_limit_cents'] = $lim === '' ? null : max(0, (int) round((float) $lim * 100));
    $v['emergency_signed_name'] = input('emergency_signed_name', 120);
    $wasSigned = $existing && (int) $existing['emergency_authorised'] === 1;
    $sameTerms = $wasSigned && $existing['emergency_signed_name'] === $v['emergency_signed_name']
        && (string) $existing['emergency_limit_cents'] === (string) $v['emergency_limit_cents'];
    $v['emergency_signed_at'] = $v['emergency_authorised'] ? ($sameTerms ? $existing['emergency_signed_at'] : now_sql()) : null;
    if (!$v['emergency_authorised']) {
        $v['emergency_signed_name'] = '';
    }

    $errors = [];
    if ($v['name'] === '') {
        $errors[] = 'Please enter your pet\'s name.';
    }
    if ($v['weight_kg'] !== null && ($v['weight_kg'] <= 0 || $v['weight_kg'] > 200)) {
        $errors[] = 'Please enter a weight in kilograms (for example 12.5).';
    }
    if ($v['emergency_authorised'] && $v['emergency_signed_name'] === '') {
        $errors[] = 'To give emergency vet authorisation, please type your full name to sign it.';
    }
    return [$v, $errors];
}

function pet_save(array $v, int $userId, ?int $petId): int
{
    $v['updated_at'] = now_sql();
    $cols = array_keys($v);
    if ($petId) {
        $set = implode(', ', array_map(fn($c) => "$c = ?", $cols));
        q("UPDATE pets SET $set WHERE id = ? AND user_id = ?", array_merge(array_values($v), [$petId, $userId]));
        return $petId;
    }
    $v['user_id'] = $userId;
    $v['created_at'] = now_sql();
    $cols = array_keys($v);
    q('INSERT INTO pets (' . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')', array_values($v));
    return (int) db()->lastInsertId();
}

/* ---------------- Files ---------------- */

function uploads_dir(): string
{
    $dir = (string) cfg('uploads.path', '');
    if ($dir === '') {
        $dir = APP_ROOT . '/storage/uploads';
    }
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
    // Belt and braces in case the folder is inside the public web folder.
    if (!is_file($dir . '/.htaccess')) {
        @file_put_contents($dir . '/.htaccess', "Require all denied\n");
    }
    return $dir;
}

function pet_files(int $petId, ?string $kind = null): array
{
    return $kind
        ? rows('SELECT * FROM pet_files WHERE pet_id = ? AND kind = ? ORDER BY id DESC', [$petId, $kind])
        : rows('SELECT * FROM pet_files WHERE pet_id = ? ORDER BY id DESC', [$petId]);
}

function file_url(int $fileId, bool $thumb = false): string
{
    return url('/file.php?id=' . $fileId . ($thumb ? '&s=t' : ''));
}

function pet_photo_url(array $pet, bool $thumb = true): ?string
{
    return $pet['photo_file_id'] ? file_url((int) $pet['photo_file_id'], $thumb) : null;
}

/** Normalise $_FILES[name] (single or multiple) into a list of uploads. */
function uploaded_list(string $field): array
{
    $f = $_FILES[$field] ?? null;
    if (!$f || !isset($f['name'])) {
        return [];
    }
    if (!is_array($f['name'])) {
        return [$f];
    }
    $out = [];
    foreach ($f['name'] as $i => $n) {
        $out[] = ['name' => $n, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
    }
    return $out;
}

/**
 * Store one upload for a pet. Images are resized (max 1600px) and re-saved as JPEG with a 480px thumbnail;
 * PDFs are kept as they are. Returns the file id. Throws InvalidArgumentException with a friendly message.
 */
function pet_store_upload(int $petId, array $up, string $kind, string $label = ''): int
{
    if (($up['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $msg = in_array($up['error'] ?? 0, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            ? 'That file is too big to upload.' : 'The upload didn\'t work. Please try again.';
        throw new InvalidArgumentException($msg);
    }
    if (!is_uploaded_file($up['tmp_name']) && PHP_SAPI !== 'cli') {
        throw new InvalidArgumentException('The upload didn\'t work. Please try again.');
    }
    if ((int) $up['size'] > PET_MAX_BYTES) {
        throw new InvalidArgumentException('Files must be under 12 MB.');
    }
    if ((int) scalar('SELECT COUNT(*) FROM pet_files WHERE pet_id = ?', [$petId]) >= PET_MAX_FILES) {
        throw new InvalidArgumentException('This pet already has the maximum number of files. Delete some first.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($up['tmp_name']) ?: '';
    $images = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $isPdf = $mime === 'application/pdf' && file_get_contents($up['tmp_name'], false, null, 0, 5) === '%PDF-';
    if (!in_array($mime, $images, true) && !($isPdf && $kind !== 'photo')) {
        throw new InvalidArgumentException($kind === 'photo' ? 'Please upload a photo (JPG, PNG or WebP).' : 'Please upload a photo or a PDF.');
    }

    $dir = uploads_dir();
    $base = bin2hex(random_bytes(16));
    if ($isPdf) {
        $stored = $base . '.pdf';
        if (!move_uploaded_file($up['tmp_name'], "$dir/$stored") && !copy($up['tmp_name'], "$dir/$stored")) {
            throw new InvalidArgumentException('Could not save the file.');
        }
        $saveMime = 'application/pdf';
    } else {
        $stored = $base . '.jpg';
        if (!save_image($up['tmp_name'], $mime, "$dir/$stored", 1600) || !save_image($up['tmp_name'], $mime, "$dir/{$base}_t.jpg", 480)) {
            // No image library on the server: keep the original (already checked to be an image).
            if (!function_exists('imagecreatefromstring')) {
                $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][$mime];
                $stored = "$base.$ext";
                copy($up['tmp_name'], "$dir/$stored");
            } else {
                throw new InvalidArgumentException('That image couldn\'t be read. Please try a different photo.');
            }
        }
        $saveMime = str_ends_with($stored, '.jpg') ? 'image/jpeg' : $mime;
    }
    q('INSERT INTO pet_files (pet_id, kind, stored_name, original_name, mime, size_bytes, label, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [$petId, $kind, $stored, mb_substr(basename((string) $up['name']), 0, 190), $saveMime, (int) filesize("$dir/$stored"), mb_substr($label, 0, 120), now_sql()]);
    $id = (int) db()->lastInsertId();
    if ($kind === 'photo' && !scalar('SELECT photo_file_id FROM pets WHERE id = ?', [$petId])) {
        q('UPDATE pets SET photo_file_id = ? WHERE id = ?', [$id, $petId]);
    }
    return $id;
}

/** Resize and re-save an image as JPEG (drops EXIF, including GPS). */
function save_image(string $src, string $mime, string $dest, int $max): bool
{
    if (!function_exists('imagecreatefromstring')) {
        return false;
    }
    $img = @imagecreatefromstring((string) file_get_contents($src));
    if (!$img) {
        return false;
    }
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $o = (int) (@exif_read_data($src)['Orientation'] ?? 1);
        $img = match ($o) { 3 => imagerotate($img, 180, 0), 6 => imagerotate($img, -90, 0), 8 => imagerotate($img, 90, 0), default => $img };
    }
    $w = imagesx($img);
    $h = imagesy($img);
    $scale = min(1, $max / max($w, $h));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));
    $out = imagecreatetruecolor($nw, $nh);
    imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255)); // white behind transparent PNGs
    imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $ok = imagejpeg($out, $dest, 84);
    imagedestroy($img);
    imagedestroy($out);
    return $ok;
}

function pet_delete_file(array $file): void
{
    $dir = uploads_dir();
    @unlink($dir . '/' . $file['stored_name']);
    @unlink($dir . '/' . preg_replace('/\.jpg$/', '_t.jpg', $file['stored_name']));
    q('DELETE FROM pet_files WHERE id = ?', [$file['id']]);
    q('UPDATE pet_vaccinations SET file_id = NULL WHERE file_id = ?', [$file['id']]);
    $pet = row('SELECT id, photo_file_id FROM pets WHERE id = ?', [$file['pet_id']]);
    if ($pet && (int) $pet['photo_file_id'] === (int) $file['id']) {
        $next = scalar("SELECT id FROM pet_files WHERE pet_id = ? AND kind = 'photo' ORDER BY id DESC LIMIT 1", [$pet['id']]);
        q('UPDATE pets SET photo_file_id = ? WHERE id = ?', [$next ?: null, $pet['id']]);
    }
}

function pet_delete(array $pet): void
{
    foreach (pet_files((int) $pet['id']) as $f) {
        pet_delete_file($f);
    }
    q('DELETE FROM pets WHERE id = ?', [$pet['id']]);
}

/* ---------------- Vaccinations ---------------- */

function pet_vaccinations(int $petId): array
{
    return rows('SELECT * FROM pet_vaccinations WHERE pet_id = ? ORDER BY (expires_on IS NULL), expires_on, name', [$petId]);
}

/** 'expired', 'due' (within 30 days), 'ok' or 'unknown'. */
function vaccine_status(?string $expires): string
{
    if (!$expires) {
        return 'unknown';
    }
    if ($expires < date('Y-m-d')) {
        return 'expired';
    }
    return $expires <= date('Y-m-d', strtotime('+30 days')) ? 'due' : 'ok';
}

function vaccine_badge(?string $expires): string
{
    $s = vaccine_status($expires);
    $map = ['expired' => ['Expired', 'declined'], 'due' => ['Due soon', 'pending'], 'ok' => ['Up to date', 'confirmed'], 'unknown' => ['No expiry date', 'cancelled']];
    return '<span class="sap-badge sap-badge-' . $map[$s][1] . '">' . $map[$s][0] . '</span>';
}

/** Worst vaccination status for a pet: used for warnings. */
function pet_vaccine_alert(int $petId): ?string
{
    $worst = null;
    foreach (pet_vaccinations($petId) as $v) {
        $s = vaccine_status($v['expires_on']);
        if ($s === 'expired') {
            return 'expired';
        }
        if ($s === 'due') {
            $worst = 'due';
        }
    }
    return $worst;
}

/** How complete a profile is, to nudge owners to fill it in. */
function pet_completeness(array $pet): int
{
    $checks = [
        (bool) $pet['photo_file_id'], $pet['breed'] !== '', $pet['age'] !== '', $pet['weight_kg'] !== null, $pet['sex'] !== '',
        trim((string) $pet['feeding']) !== '', trim((string) $pet['behaviour']) !== '' || $pet['good_with_dogs'] !== '',
        $pet['species'] !== 'dog' || trim((string) $pet['walking']) !== '',
        $pet['vet_clinic'] !== '', $pet['emergency_phone'] !== '', (int) $pet['emergency_authorised'] === 1,
        $pet['species'] === 'other' || (bool) scalar('SELECT 1 FROM pet_vaccinations WHERE pet_id = ? LIMIT 1', [$pet['id']]),
    ];
    return (int) round(count(array_filter($checks)) / count($checks) * 100);
}

function ynu(?string $v): string
{
    return PET_YNU[$v ?? ''] ?? '—';
}
