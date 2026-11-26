<?php
declare(strict_types=1);

/** Returns the schema statements for the current database driver. */
function schema_statements(string $driver): array
{
    $sql = file_get_contents(__DIR__ . '/schema.mysql.sql');
    $sql = preg_replace('/--[^\n]*/', '', $sql); // strip comments

    if ($driver === 'sqlite') {
        // Translate the MySQL schema into SQLite (used for local testing only).
        $sql = preg_replace('/\)\s*ENGINE=[^;]+;/', ');', $sql);
        $sql = str_replace('INT UNSIGNED AUTO_INCREMENT PRIMARY KEY', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
        $sql = str_replace('INT UNSIGNED', 'INTEGER', $sql);
        $sql = preg_replace('/UNIQUE KEY \w+ \(/', 'UNIQUE (', $sql);
        $sql = preg_replace('/^\s*KEY \w+ \([^)]*\),?\s*$/m', '', $sql);
        $sql = preg_replace('/CONSTRAINT \w+ FOREIGN KEY/', 'FOREIGN KEY', $sql);
        $sql = preg_replace('/TINYINT\(1\)/', 'INTEGER', $sql);
        // Remove trailing commas left before a closing bracket.
        $sql = preg_replace('/,\s*\)\s*;/', "\n);", $sql);
    }

    return array_values(array_filter(array_map('trim', explode(';', $sql))));
}

function seed_defaults(PDO $pdo): void
{
    if ((int) $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn() === 0) {
        $services = [
            ['Dog Walking', 'Tailored walks with exercise, sniffing time and lots of attention.', 'slot', 60, 1],
            ['Daily Visit', 'A home visit for feeding, fresh water, litter, play and cuddles.', 'slot', 30, 2],
            ['Pet Taxi', 'Safe, comfortable transport to the vet, groomer or wherever your pet needs to go.', 'slot', 60, 3],
            ['In-house Sitting', 'We stay in your home so your pets keep their routine while you are away. Priced per night.', 'stay', 0, 4],
        ];
        $stmt = $pdo->prepare('INSERT INTO services (name, description, kind, duration_minutes, price_cents, active, sort_order) VALUES (?, ?, ?, ?, NULL, 1, ?)');
        foreach ($services as $s) {
            $stmt->execute($s);
        }
    }
    if ((int) $pdo->query('SELECT COUNT(*) FROM working_hours')->fetchColumn() === 0) {
        $stmt = $pdo->prepare('INSERT INTO working_hours (weekday, is_open, open_time, close_time) VALUES (?, ?, ?, ?)');
        for ($d = 0; $d <= 6; $d++) {
            $stmt->execute([$d, $d === 0 ? 0 : 1, '08:00', '18:00']); // Sunday closed by default
        }
    }
}

/**
 * Columns added after the first release. Each is added only if it's missing, so running
 * install.php again on an existing site safely upgrades the database.
 * [table, column, MySQL definition, SQLite definition]
 */
function schema_migrations(): array
{
    $t = fn($c) => ['pets', $c, 'TEXT NULL', 'TEXT NULL'];
    $v = fn($c, $n = 120) => ['pets', $c, "VARCHAR($n) NOT NULL DEFAULT ''", "VARCHAR($n) NOT NULL DEFAULT ''"];
    return [
        $v('sex', 10), $v('desexed', 10), ['pets', 'weight_kg', 'DECIMAL(5,1) NULL', 'REAL NULL'],
        $v('colour'), $v('microchip', 40), $v('council_reg', 40), ['pets', 'photo_file_id', 'INT UNSIGNED NULL', 'INTEGER NULL'],
        $v('good_with_dogs', 10), $v('good_with_cats', 10), $v('good_with_kids', 10), $v('off_lead', 10),
        $t('behaviour'), $t('anxiety_triggers'), $t('walking'), $t('feeding'), $t('medical'),
        $v('vet_clinic'), $v('vet_phone', 40), $v('vet_address', 255),
        $v('emergency_name'), $v('emergency_phone', 40),
        ['pets', 'emergency_authorised', 'TINYINT(1) NOT NULL DEFAULT 0', 'INTEGER NOT NULL DEFAULT 0'],
        ['pets', 'emergency_limit_cents', 'INT NULL', 'INTEGER NULL'],
        $v('emergency_signed_name'), ['pets', 'emergency_signed_at', 'DATETIME NULL', 'DATETIME NULL'],
        ['pets', 'photos_public_ok', 'TINYINT(1) NOT NULL DEFAULT 0', 'INTEGER NOT NULL DEFAULT 0'],
        ['pets', 'updated_at', 'DATETIME NULL', 'DATETIME NULL'],
    ];
}

function run_migrations(PDO $pdo, string $driver): int
{
    $added = 0;
    $cache = [];
    foreach (schema_migrations() as [$table, $col, $mysql, $sqlite]) {
        if (!isset($cache[$table])) {
            $cols = [];
            $sql = $driver === 'sqlite' ? "PRAGMA table_info($table)" : "SHOW COLUMNS FROM `$table`";
            foreach ($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $cols[] = strtolower($r['name'] ?? $r['Field']);
            }
            $cache[$table] = $cols;
        }
        if (!in_array(strtolower($col), $cache[$table], true)) {
            $pdo->exec("ALTER TABLE $table ADD COLUMN $col " . ($driver === 'sqlite' ? $sqlite : $mysql));
            $cache[$table][] = strtolower($col);
            $added++;
        }
    }
    return $added;
}
