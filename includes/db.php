<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $dsn = (string) cfg('db.dsn');
    $pdo = new PDO($dsn, (string) cfg('db.user', ''), (string) cfg('db.pass', ''), [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    if (db_driver() === 'sqlite') {
        $pdo->exec('PRAGMA foreign_keys = ON');
    }
    return $pdo;
}

function db_driver(): string
{
    return str_starts_with((string) cfg('db.dsn'), 'sqlite:') ? 'sqlite' : 'mysql';
}

/** Run a query with parameters and return the statement. */
function q(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function row(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch();
    return $r === false ? null : $r;
}

function rows(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function scalar(string $sql, array $params = [])
{
    $v = q($sql, $params)->fetchColumn();
    return $v === false ? null : $v;
}

function now_sql(): string
{
    return date('Y-m-d H:i:s');
}

/**
 * Run $fn while holding an exclusive booking lock, so two people can't grab the same slot at once.
 */
function with_booking_lock(callable $fn)
{
    $pdo = db();
    if (db_driver() === 'sqlite') {
        $pdo->exec('BEGIN IMMEDIATE');
        try {
            $result = $fn();
            $pdo->exec('COMMIT');
            return $result;
        } catch (Throwable $e) {
            $pdo->exec('ROLLBACK');
            throw $e;
        }
    }
    $got = (int) scalar("SELECT GET_LOCK('sap_booking', 10)");
    if ($got !== 1) {
        throw new RuntimeException('Could not lock bookings, please try again.');
    }
    $pdo->beginTransaction();
    try {
        $result = $fn();
        $pdo->commit();
        return $result;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    } finally {
        scalar("SELECT RELEASE_LOCK('sap_booking')");
    }
}
