<?php
declare(strict_types=1);

/** Read-only pet profile, shared by the client's pet page and the admin. */
function render_pet_profile(array $pet, bool $forAdmin = false): void
{
    $photos = pet_files((int) $pet['id'], 'photo');
    $ids = pet_files((int) $pet['id'], 'id');
    $vax = pet_vaccinations((int) $pet['id']);
    $vaxFiles = [];
    foreach (pet_files((int) $pet['id'], 'vaccine') as $f) {
        $vaxFiles[$f['id']] = $f;
    }
    $isDog = $pet['species'] === 'dog';
    $val = fn($s) => trim((string) $s) !== '' ? nl2br(e($s)) : '<span class="sap-muted">Not added yet</span>';
    ?>
    <div class="pet-profile">
        <section class="sap-card pet-hero">
            <div class="pet-hero-photo">
                <?php if ($url = pet_photo_url($pet, false)): ?>
                    <img src="<?= e($url) ?>" alt="Photo of <?= e($pet['name']) ?>">
                <?php else: ?>
                    <div class="pet-photo-empty"><?= pet_mark('', false, '') ?></div>
                <?php endif; ?>
            </div>
            <div class="pet-hero-info">
                <h2><?= e($pet['name']) ?></h2>
                <p class="pet-hero-sub"><?= e(PET_SPECIES[$pet['species']] ?? ucfirst($pet['species'])) ?><?= $pet['breed'] ? ' · ' . e($pet['breed']) : '' ?></p>
                <dl class="pet-facts">
                    <div><dt>Age</dt><dd><?= e($pet['age'] ?: '—') ?></dd></div>
                    <div><dt>Weight</dt><dd><?= $pet['weight_kg'] !== null ? e(rtrim(rtrim(number_format((float) $pet['weight_kg'], 1), '0'), '.')) . ' kg' : '—' ?></dd></div>
                    <div><dt>Sex</dt><dd><?= e(PET_SEX[$pet['sex']] ?? '—') ?></dd></div>
                    <div><dt>Desexed</dt><dd><?= e(ynu($pet['desexed'])) ?></dd></div>
                </dl>
                <?php if ($alert = pet_vaccine_alert((int) $pet['id'])): ?>
                    <p class="pet-warn"><i class="bi bi-exclamation-triangle"></i> <?= $alert === 'expired' ? 'A vaccination has expired.' : 'A vaccination is due in the next 30 days.' ?></p>
                <?php endif; ?>
                <?php if (!(int) $pet['emergency_authorised']): ?>
                    <p class="pet-warn"><i class="bi bi-exclamation-triangle"></i> No emergency vet authorisation yet.</p>
                <?php endif; ?>
            </div>
        </section>

        <div class="row g-4">
            <div class="col-lg-6">
                <section class="sap-card">
                    <h3><i class="bi bi-activity"></i> Health</h3>
                    <p><?= $val($pet['medical']) ?></p>
                    <h4>Vaccinations</h4>
                    <?php if (!$vax): ?><p class="sap-muted">No vaccinations recorded.</p><?php endif; ?>
                    <ul class="pet-vax-list">
                        <?php foreach ($vax as $v): ?>
                            <li>
                                <div><strong><?= e($v['name']) ?></strong>
                                    <span class="sap-muted"><?= $v['given_on'] ? 'Given ' . e(fmt_date($v['given_on'])) : '' ?><?= $v['expires_on'] ? ' · Expires ' . e(fmt_date($v['expires_on'])) : '' ?></span>
                                    <?php if ($v['file_id'] && isset($vaxFiles[$v['file_id']])): ?>
                                        <a href="<?= e(file_url((int) $v['file_id'])) ?>" target="_blank" rel="noopener"><i class="bi bi-paperclip"></i> Record</a>
                                    <?php endif; ?>
                                </div>
                                <?= vaccine_badge($v['expires_on']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
                <section class="sap-card">
                    <h3><i class="bi bi-people"></i> Behaviour</h3>
                    <dl class="pet-facts pet-facts-3">
                        <div><dt>With dogs</dt><dd><?= e(ynu($pet['good_with_dogs'])) ?></dd></div>
                        <div><dt>With cats</dt><dd><?= e(ynu($pet['good_with_cats'])) ?></dd></div>
                        <div><dt>With children</dt><dd><?= e(ynu($pet['good_with_kids'])) ?></dd></div>
                    </dl>
                    <p><?= $val($pet['behaviour']) ?></p>
                    <h4>Anxiety triggers</h4>
                    <p><?= $val($pet['anxiety_triggers']) ?></p>
                </section>
                <?php if ($isDog || trim((string) $pet['walking']) !== ''): ?>
                <section class="sap-card">
                    <h3><i class="bi bi-signpost-split"></i> Walking</h3>
                    <p><strong>Off lead:</strong> <?= e(ynu($pet['off_lead'])) ?></p>
                    <p><?= $val($pet['walking']) ?></p>
                </section>
                <?php endif; ?>
            </div>
            <div class="col-lg-6">
                <section class="sap-card">
                    <h3><i class="bi bi-cup"></i> Feeding</h3>
                    <p><?= $val($pet['feeding']) ?></p>
                </section>
                <section class="sap-card">
                    <h3><i class="bi bi-plus-square"></i> Vet &amp; emergencies</h3>
                    <dl class="sap-dl">
                        <dt>Vet clinic</dt><dd><?= e($pet['vet_clinic'] ?: '—') ?></dd>
                        <dt>Vet phone</dt><dd><?= $pet['vet_phone'] ? '<a href="' . e(tel_link($pet['vet_phone'])) . '">' . e($pet['vet_phone']) . '</a>' : '—' ?></dd>
                        <dt>Vet address</dt><dd><?= e($pet['vet_address'] ?: '—') ?></dd>
                        <dt>Emergency contact</dt><dd><?= e($pet['emergency_name'] ?: '—') ?><?= $pet['emergency_phone'] ? ' · <a href="' . e(tel_link($pet['emergency_phone'])) . '">' . e($pet['emergency_phone']) . '</a>' : '' ?></dd>
                    </dl>
                    <div class="pet-auth <?= (int) $pet['emergency_authorised'] ? 'is-yes' : 'is-no' ?>">
                        <?php if ((int) $pet['emergency_authorised']): ?>
                            <strong><i class="bi bi-check-circle"></i> Emergency vet care authorised</strong>
                            <span>Up to <?= $pet['emergency_limit_cents'] !== null ? e(money((int) $pet['emergency_limit_cents'])) : 'whatever is needed' ?>, if the owner can't be reached.
                            Signed by <?= e($pet['emergency_signed_name']) ?> on <?= e(fmt_date((string) $pet['emergency_signed_at'])) ?>.</span>
                        <?php else: ?>
                            <strong><i class="bi bi-x-circle"></i> No emergency authorisation</strong>
                            <span>We'll keep trying to reach the owner before any treatment.</span>
                        <?php endif; ?>
                    </div>
                </section>
                <section class="sap-card">
                    <h3><i class="bi bi-person-badge"></i> Identification</h3>
                    <dl class="sap-dl">
                        <dt>Colour &amp; markings</dt><dd><?= e($pet['colour'] ?: '—') ?></dd>
                        <dt>Microchip</dt><dd><?= e($pet['microchip'] ?: '—') ?></dd>
                        <dt>Council registration</dt><dd><?= e($pet['council_reg'] ?: '—') ?></dd>
                        <dt>Photos on social media</dt><dd><?= (int) $pet['photos_public_ok'] ? 'OK to share' : 'Please don\'t share' ?></dd>
                    </dl>
                    <?php if ($ids): ?>
                        <div class="pet-docs">
                            <?php foreach ($ids as $f): ?>
                                <a href="<?= e(file_url((int) $f['id'])) ?>" target="_blank" rel="noopener"><i class="bi <?= $f['mime'] === 'application/pdf' ? 'bi-file-earmark-pdf' : 'bi-image' ?>"></i> <?= e($f['label'] ?: $f['original_name']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
                <?php if (trim((string) $pet['notes']) !== ''): ?>
                <section class="sap-card">
                    <h3><i class="bi bi-chat-dots"></i> Anything else</h3>
                    <p><?= nl2br(e($pet['notes'])) ?></p>
                </section>
                <?php endif; ?>
            </div>
        </div>

        <?php if (count($photos) > 1 || ($forAdmin && $photos)): ?>
        <section class="sap-card">
            <h3><i class="bi bi-images"></i> Photos</h3>
            <div class="pet-gallery">
                <?php foreach ($photos as $f): ?>
                    <a href="<?= e(file_url((int) $f['id'])) ?>" target="_blank" rel="noopener"><img src="<?= e(file_url((int) $f['id'], true)) ?>" alt="<?= e($pet['name']) ?>" loading="lazy"></a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
    <?php
}
