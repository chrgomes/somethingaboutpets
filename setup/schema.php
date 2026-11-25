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
