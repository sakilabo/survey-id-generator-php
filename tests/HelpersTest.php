<?php

use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    public function test_format_expiration_date_japanese(): void
    {
        $this->assertSame(
            '2026年10月26日',
            format_expiration_date('2026-04-29T22:20:29+09:00', 180, 'Y年n月j日')
        );
    }

    public function test_format_expiration_date_english(): void
    {
        $this->assertSame(
            'October 26, 2026',
            format_expiration_date('2026-04-29T22:20:29+09:00', 180, 'F j, Y')
        );
    }

    public function test_format_expiration_date_handles_year_boundary(): void
    {
        $this->assertSame(
            '2027年1月1日',
            format_expiration_date('2026-07-05T00:00:00+09:00', 180, 'Y年n月j日')
        );
    }

    public function test_format_ignores_time_component(): void
    {
        // Same calendar date should yield the same output regardless of time-of-day.
        $a = format_expiration_date('2026-04-29T00:00:00+09:00', 180, 'Y年n月j日');
        $b = format_expiration_date('2026-04-29T23:59:59+09:00', 180, 'Y年n月j日');
        $this->assertSame($a, $b);
    }

    public function test_format_with_zero_retention(): void
    {
        $this->assertSame(
            '2026年4月29日',
            format_expiration_date('2026-04-29T12:00:00+09:00', 0, 'Y年n月j日')
        );
    }
}
