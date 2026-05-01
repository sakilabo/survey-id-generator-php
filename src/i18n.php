<?php

/** Accept-Language is intentionally ignored — `/` always previews JA so shared links are predictable. */
function detect_language(?string $explicit = null): string
{
    return $explicit === 'en' ? 'en' : 'ja';
}

/**
 * Pick a language-specific value: returns $en when $lang is 'en', $ja otherwise.
 * Centralises `$lang === 'en' ? X : Y` ternaries so language branches read uniformly.
 */
function lang_pick(string $lang, mixed $en, mixed $ja): mixed
{
    return $lang === 'en' ? $en : $ja;
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
