<?php

/**
 * Character set used in IDs.
 * Visually confusing characters (0/o, 1/i/l, q/g, m/v) are excluded.
 * Regex metacharacters are not supported (build_regex_pattern does not escape).
 */
const DEFAULT_CHARS = '23456789abcdefghjknprstuwxyz';

/**
 * QWERTY-adjacent keys, used to avoid pairs that produce typo-collisions.
 */
const KEYBOARD_NEIGHBORS = [
    '1' => '2',
    '2' => '13',
    '3' => '24',
    '4' => '35',
    '5' => '46',
    '6' => '57',
    '7' => '68',
    '8' => '79',
    '9' => '80',
    '0' => '9',
    'q' => 'w',
    'w' => 'qe',
    'e' => 'wr',
    'r' => 'et',
    't' => 'ry',
    'y' => 'tu',
    'u' => 'yi',
    'i' => 'uo',
    'o' => 'ip',
    'p' => 'o',
    'a' => 's',
    's' => 'ad',
    'd' => 'sf',
    'f' => 'dg',
    'g' => 'fh',
    'h' => 'gj',
    'j' => 'hk',
    'k' => 'jl',
    'l' => 'k',
    'z' => 'x',
    'x' => 'zc',
    'c' => 'xv',
    'v' => 'cb',
    'b' => 'vn',
    'n' => 'bm',
    'm' => 'n',
];

/**
 * Pick the character set used at one position of an ID.
 * No two characters in the result are adjacent on the keyboard.
 *
 * @param int $count number of characters to pick (max 15 under the adjacency constraint)
 * @return string[]
 */
function pick_id_chars(int $count = 8): array
{
    $charsLen = strlen(DEFAULT_CHARS);
    $result = [];
    $forbidden = ''; // characters that can no longer be used
    for ($i = 0; $i < $count; $i++) {
        $idx = mt_rand(0, $charsLen - 1);
        // If the pick lands on a forbidden character, walk forward through DEFAULT_CHARS.
        // If nothing safe remains, the loop wraps around and returns the first character.
        $c = DEFAULT_CHARS[$idx];
        for ($tries = 0; $tries <= $charsLen; $tries++) {
            if (strpos($forbidden, $c) === false) break;
            $idx = ($idx + 1) % $charsLen;
            $c = DEFAULT_CHARS[$idx];
        }
        $result[] = $c;
        $forbidden .= $c . KEYBOARD_NEIGHBORS[$c];
    }
    return $result;
}
