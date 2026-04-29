<?php

/**
 * Open the SQLite DB, ensure the schema, and return the PDO handle.
 */
function open_db(string $path): PDO
{
    $db = new PDO('sqlite:' . $path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('CREATE TABLE IF NOT EXISTS generations (
        id_key       TEXT PRIMARY KEY,
        created_at   TEXT NOT NULL,
        char_count   INTEGER NOT NULL,
        repeat_count INTEGER NOT NULL,
        id_limit     INTEGER NOT NULL,
        rand_seed    INTEGER NOT NULL,
        seeds_json   TEXT NOT NULL
    )');
    return $db;
}

/**
 * Delete records whose created_at date is more than $retention_days days ago.
 * Comparison is by date only, so the cutoff matches the "valid until YYYY-MM-DD" UI label.
 */
function purge_expired(PDO $db, int $retention_days): void
{
    $cutoff_date = date('Y-m-d', strtotime('-' . $retention_days . ' days'));
    $db->prepare('DELETE FROM generations WHERE substr(created_at, 1, 10) < ?')
        ->execute([$cutoff_date]);
}

/**
 * Save a generation record.
 */
function save_generation(
    PDO $db,
    string $id_key,
    int $char_count,
    int $repeat_count,
    int $id_limit,
    int $rand_seed,
    array $seeds
): void {
    $stmt = $db->prepare(
        'INSERT INTO generations (id_key, created_at, char_count, repeat_count, id_limit, rand_seed, seeds_json) ' .
            'VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $id_key,
        date('c'),
        $char_count,
        $repeat_count,
        $id_limit,
        $rand_seed,
        json_encode($seeds),
    ]);
}

/**
 * Fetch a generation record.
 *
 * @return array<string, mixed>|false false if not found
 */
function fetch_generation(PDO $db, string $id_key): array|false
{
    $stmt = $db->prepare('SELECT * FROM generations WHERE id_key = ?');
    $stmt->execute([$id_key]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Delete a generation record.
 */
function delete_generation(PDO $db, string $id_key): void
{
    $db->prepare('DELETE FROM generations WHERE id_key = ?')->execute([$id_key]);
}
