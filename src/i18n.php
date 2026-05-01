<?php

/**
 * Pick the UI language.
 *
 * If $explicit is 'ja' or 'en' (e.g. from ?lang= or a stored cookie), use it.
 * Otherwise, parse the browser's Accept-Language header and return 'en' only when
 * "en" is requested with a strictly higher q-value than "ja" (treating absent
 * tags as q=0). Otherwise — including when the header is missing, as is typical
 * for crawlers — return 'ja'. This makes '/' index as the Japanese version while
 * still respecting users who explicitly prefer English.
 */
function detect_language(?string $explicit = null): string
{
    if ($explicit === 'ja' || $explicit === 'en') {
        return $explicit;
    }

    $header = (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
    $en_q = 0.0;
    $ja_q = 0.0;
    foreach (explode(',', $header) as $part) {
        $tokens = explode(';', trim($part));
        $tag = strtolower(trim($tokens[0]));
        if ($tag === '') continue;
        $q = 1.0;
        for ($i = 1, $n = count($tokens); $i < $n; $i++) {
            $kv = explode('=', trim($tokens[$i]), 2);
            if (count($kv) === 2 && trim($kv[0]) === 'q') {
                $q = (float) $kv[1];
                break;
            }
        }
        if (str_starts_with($tag, 'en') && $q > $en_q) $en_q = $q;
        if (str_starts_with($tag, 'ja') && $q > $ja_q) $ja_q = $q;
    }

    return $en_q > $ja_q ? 'en' : 'ja';
}

/**
 * Load translation strings for the given language.
 *
 * @return array<string, string>
 */
function load_translations(string $lang): array
{
    return require __DIR__ . '/i18n/' . $lang . '.php';
}

/**
 * htmlspecialchars shorthand for templates.
 */
function e(string $s): string
{
    return htmlspecialchars($s);
}
