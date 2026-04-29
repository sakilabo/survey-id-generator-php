<?php

use PHPUnit\Framework\TestCase;

final class CharsTest extends TestCase
{
    public function test_returns_requested_count(): void
    {
        $chars = pick_id_chars(8);
        $this->assertCount(8, $chars);
    }

    public function test_all_chars_are_in_default_charset(): void
    {
        for ($run = 0; $run < 50; $run++) {
            $chars = pick_id_chars(8);
            foreach ($chars as $c) {
                $this->assertStringContainsString($c, DEFAULT_CHARS, "'{$c}' is not in DEFAULT_CHARS");
            }
        }
    }

    public function test_no_duplicate_chars(): void
    {
        for ($run = 0; $run < 50; $run++) {
            $chars = pick_id_chars(8);
            $this->assertSame(
                count($chars),
                count(array_unique($chars)),
                "duplicate character in result (run $run)"
            );
        }
    }

    public function test_no_keyboard_adjacent_chars(): void
    {
        // Run 100 times to confirm no result ever contains an adjacent pair.
        for ($run = 0; $run < 100; $run++) {
            $chars = pick_id_chars(8);
            foreach ($chars as $i => $c) {
                $neighbors = KEYBOARD_NEIGHBORS[$c] ?? '';
                foreach ($chars as $j => $other) {
                    if ($i === $j) continue;
                    $this->assertStringNotContainsString(
                        $other,
                        $neighbors,
                        "'{$c}' and '{$other}' are keyboard-adjacent (run $run)"
                    );
                }
            }
        }
    }

    public function test_can_generate_at_max_capacity(): void
    {
        // count=15 is the practical ceiling under the adjacency constraint.
        // If this regresses, the loop can't escape and PHP may time out.
        $chars = pick_id_chars(15);
        $this->assertCount(15, $chars);
    }
}
