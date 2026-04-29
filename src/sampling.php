<?php

/**
 * Sample $limit unique IDs from the regex match space.
 *
 * @param string[][] $seeds characters available at each position
 * @param int $limit number of IDs to generate (clamped to the regex match space)
 * @param \Random\Randomizer $rng random source (seed controls reproducibility)
 * @return string[] generated IDs
 */
function sample_ids(array $seeds, int $limit, \Random\Randomizer $rng): array
{
    $count = count($seeds[0]);
    $repeat = count($seeds);
    $limit = min($limit, $count ** $repeat);

    $seen = [];
    $result = [];
    for ($i = 0; $i < $limit; $i++) {
        do {
            $id = '';
            for ($k = 0; $k < $repeat; $k++) {
                $id .= $seeds[$k][$rng->getInt(0, $count - 1)];
            }
        } while (isset($seen[$id])); // retry on collision
        $seen[$id] = true; // assoc-array hash lookup is ~100x faster than in_array
        $result[] = $id;
    }
    return $result;
}

/**
 * Build the regex pattern from the seed table.
 *
 * @param string[][] $seeds characters available at each position
 * @return string a regex of the form '(a|b|c)(d|e|f)'
 */
function build_regex_pattern(array $seeds): string
{
    return join('', array_map(fn($s) => '(' . join('|', $s) . ')', $seeds));
}
