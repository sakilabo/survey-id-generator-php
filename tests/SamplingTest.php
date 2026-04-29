<?php

use PHPUnit\Framework\TestCase;

final class SamplingTest extends TestCase
{
    /** @var string[][] */
    private array $seeds;

    protected function setUp(): void
    {
        // Fixed seeds for tests (4-character IDs).
        $this->seeds = [
            ['2', '5', '7', '9', 'b', 'f', 'h', 'k'],
            ['3', '6', '8', 'a', 'c', 'g', 'j', 'n'],
            ['4', 'd', 'e', 'p', 'r', 'u', 'y', 'z'],
            ['5', 'b', 'h', 'k', 'r', '9', '7', 'c'],
        ];
    }

    public function test_returns_requested_count(): void
    {
        $rng = new \Random\Randomizer(new \Random\Engine\Mt19937(42));
        $ids = sample_ids($this->seeds, 100, $rng);
        $this->assertCount(100, $ids);
    }

    public function test_all_ids_are_unique(): void
    {
        $rng = new \Random\Randomizer(new \Random\Engine\Mt19937(42));
        $ids = sample_ids($this->seeds, 100, $rng);
        $this->assertSame(count($ids), count(array_unique($ids)));
    }

    public function test_clamps_to_total_space(): void
    {
        // 2x2 seeds = 4 possibilities total; limit=100 still returns just 4.
        $small = [['a', 'b'], ['c', 'd']];
        $rng = new \Random\Randomizer(new \Random\Engine\Mt19937(42));
        $ids = sample_ids($small, 100, $rng);
        $this->assertCount(4, $ids);
        $this->assertEqualsCanonicalizing(['ac', 'ad', 'bc', 'bd'], $ids);
    }

    public function test_deterministic_with_same_seed(): void
    {
        $rng1 = new \Random\Randomizer(new \Random\Engine\Mt19937(42));
        $rng2 = new \Random\Randomizer(new \Random\Engine\Mt19937(42));
        $a = sample_ids($this->seeds, 100, $rng1);
        $b = sample_ids($this->seeds, 100, $rng2);
        $this->assertSame($a, $b);
    }

    public function test_different_seed_produces_different_ids(): void
    {
        $rng1 = new \Random\Randomizer(new \Random\Engine\Mt19937(42));
        $rng2 = new \Random\Randomizer(new \Random\Engine\Mt19937(99));
        $a = sample_ids($this->seeds, 100, $rng1);
        $b = sample_ids($this->seeds, 100, $rng2);
        $this->assertNotSame($a, $b);
    }

    public function test_all_ids_match_regex(): void
    {
        $rng = new \Random\Randomizer(new \Random\Engine\Mt19937(42));
        $ids = sample_ids($this->seeds, 100, $rng);
        $pattern = build_regex_pattern($this->seeds);
        foreach ($ids as $id) {
            $this->assertMatchesRegularExpression('/^' . $pattern . '$/', $id);
        }
    }

    public function test_id_length_matches_repeat(): void
    {
        $rng = new \Random\Randomizer(new \Random\Engine\Mt19937(42));
        $ids = sample_ids($this->seeds, 100, $rng);
        foreach ($ids as $id) {
            $this->assertSame(4, strlen($id)); // repeat = 4
        }
    }

    public function test_regex_pattern_format(): void
    {
        $seeds = [['a', 'b'], ['c', 'd']];
        $this->assertSame('(a|b)(c|d)', build_regex_pattern($seeds));
    }

    public function test_regex_pattern_with_three_positions(): void
    {
        $seeds = [['x'], ['y', 'z'], ['1', '2', '3']];
        $this->assertSame('(x)(y|z)(1|2|3)', build_regex_pattern($seeds));
    }
}
