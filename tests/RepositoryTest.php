<?php

use PHPUnit\Framework\TestCase;

final class RepositoryTest extends TestCase
{
    private string $db_path;
    private PDO $db;

    protected function setUp(): void
    {
        $this->db_path = tempnam(sys_get_temp_dir(), 'survey_id_test_');
        $this->db = open_db($this->db_path);
    }

    protected function tearDown(): void
    {
        unset($this->db);
        @unlink($this->db_path);
    }

    public function test_open_db_creates_table(): void
    {
        $row = $this->db->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name='generations'"
        )->fetch();
        $this->assertNotFalse($row);
    }

    public function test_save_and_fetch_round_trip(): void
    {
        $seeds = [['a', 'b'], ['c', 'd']];
        save_generation($this->db, 'abc123', 2, 2, 4, 12345, $seeds);

        $record = fetch_generation($this->db, 'abc123');
        $this->assertIsArray($record);
        $this->assertSame('abc123', $record['id_key']);
        $this->assertSame(2, (int)$record['char_count']);
        $this->assertSame(2, (int)$record['repeat_count']);
        $this->assertSame(4, (int)$record['id_limit']);
        $this->assertSame(12345, (int)$record['rand_seed']);
        $this->assertSame($seeds, json_decode($record['seeds_json'], true));
    }

    public function test_created_at_is_set_on_save(): void
    {
        save_generation($this->db, 'abc', 1, 1, 1, 1, [['a']]);
        $record = fetch_generation($this->db, 'abc');
        $this->assertIsArray($record);
        // ISO 8601 (e.g. Y-m-dTH:i:s+09:00).
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+\-]\d{2}:\d{2}$/',
            $record['created_at']
        );
    }

    public function test_fetch_returns_false_for_missing_id(): void
    {
        $this->assertFalse(fetch_generation($this->db, 'nonexistent'));
    }

    public function test_delete_removes_record(): void
    {
        save_generation($this->db, 'abc', 1, 1, 1, 1, [['a']]);
        delete_generation($this->db, 'abc');
        $this->assertFalse(fetch_generation($this->db, 'abc'));
    }

    public function test_delete_nonexistent_id_does_not_error(): void
    {
        delete_generation($this->db, 'nonexistent');
        $this->assertTrue(true); // not throwing is enough
    }

    public function test_purge_expired_uses_date_only(): void
    {
        // RETENTION = 180 days.
        // Insert records straddling the boundary.
        $rows = [
            'old_181' => date('c', strtotime('-181 days')),
            'edge_180' => date('c', strtotime('-180 days')),    // same calendar date as cutoff -> kept
            'fresh_100' => date('c', strtotime('-100 days')),
            'today' => date('c'),
        ];
        $stmt = $this->db->prepare(
            'INSERT INTO generations (id_key, created_at, char_count, repeat_count, id_limit, rand_seed, seeds_json) ' .
            'VALUES (?, ?, 1, 1, 1, 1, ?)'
        );
        foreach ($rows as $key => $iso) {
            $stmt->execute([$key, $iso, '[["a"]]']);
        }

        purge_expired($this->db, 180);

        $this->assertFalse(fetch_generation($this->db, 'old_181'), '181-day-old record should be purged');
        $this->assertIsArray(fetch_generation($this->db, 'edge_180'), '180-day-old record should remain (same date)');
        $this->assertIsArray(fetch_generation($this->db, 'fresh_100'), '100-day-old record should remain');
        $this->assertIsArray(fetch_generation($this->db, 'today'), "today's record should remain");
    }

    public function test_purge_expired_keeps_record_through_full_expiration_day(): void
    {
        // Match the "valid until YYYY-MM-DD" UI label:
        // any time-of-day on the 180th day should still be kept.
        $iso_morning = date('Y-m-d', strtotime('-180 days')) . 'T00:00:01+09:00';
        $iso_late = date('Y-m-d', strtotime('-180 days')) . 'T23:59:59+09:00';
        $stmt = $this->db->prepare(
            'INSERT INTO generations (id_key, created_at, char_count, repeat_count, id_limit, rand_seed, seeds_json) ' .
            'VALUES (?, ?, 1, 1, 1, 1, ?)'
        );
        $stmt->execute(['morning', $iso_morning, '[["a"]]']);
        $stmt->execute(['late', $iso_late, '[["a"]]']);

        purge_expired($this->db, 180);

        $this->assertIsArray(fetch_generation($this->db, 'morning'));
        $this->assertIsArray(fetch_generation($this->db, 'late'));
    }
}
