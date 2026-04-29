<?php

/**
 * Pick the UI language from the browser's Accept-Language header.
 * Returns 'ja' when the highest-priority tag is Japanese, 'en' otherwise.
 */
function detect_language(): string
{
    $header = (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
    // First tag in the comma-separated list — browsers list languages in preference order.
    $first = strtolower(ltrim((string) strtok($header, ',')));
    return str_starts_with($first, 'ja') ? 'ja' : 'en';
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
