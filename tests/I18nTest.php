<?php

use PHPUnit\Framework\TestCase;

final class I18nTest extends TestCase
{
    public function test_returns_japanese_when_no_explicit(): void
    {
        $this->assertSame('ja', detect_language());
        $this->assertSame('ja', detect_language(null));
    }

    public function test_returns_japanese_when_explicit_is_japanese(): void
    {
        $this->assertSame('ja', detect_language('ja'));
    }

    public function test_returns_english_only_when_explicit_is_english(): void
    {
        $this->assertSame('en', detect_language('en'));
    }

    public function test_returns_japanese_for_invalid_explicit_values(): void
    {
        $this->assertSame('ja', detect_language('fr'));
        $this->assertSame('ja', detect_language(''));
        $this->assertSame('ja', detect_language('EN')); // case-sensitive — only literal 'en'
    }

    public function test_ignores_accept_language(): void
    {
        // Accept-Language is intentionally ignored: `/` always renders JA so
        // shared-link previews are predictable regardless of the fetcher's locale.
        $original = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;
        try {
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.9';
            $this->assertSame('ja', detect_language());
            $this->assertSame('ja', detect_language(null));
        } finally {
            if ($original === null) {
                unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            } else {
                $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $original;
            }
        }
    }

    public function test_translations_have_identical_keys(): void
    {
        $ja = load_translations('ja');
        $en = load_translations('en');
        $this->assertSame(
            array_keys($ja),
            array_keys($en),
            'ja and en translation files must have the same keys'
        );
    }

    public function test_translations_have_no_empty_values(): void
    {
        foreach (['ja', 'en'] as $lang) {
            foreach (load_translations($lang) as $key => $value) {
                $this->assertNotSame('', $value, "translation '{$key}' is empty in {$lang}");
            }
        }
    }

    public function test_lang_pick_returns_en_value_when_lang_is_english(): void
    {
        $this->assertSame('en_US', lang_pick('en', 'en_US', 'ja_JP'));
        $this->assertSame('?lang=en', lang_pick('en', '?lang=en', ''));
    }

    public function test_lang_pick_returns_ja_value_otherwise(): void
    {
        $this->assertSame('ja_JP', lang_pick('ja', 'en_US', 'ja_JP'));
        $this->assertSame('', lang_pick('ja', '?lang=en', ''));
        // Falls through to JA for any non-'en' input — invalid lang values
        // shouldn't reach lang_pick, but the fall-through is the safe default.
        $this->assertSame('ja_JP', lang_pick('fr', 'en_US', 'ja_JP'));
    }

    public function test_lang_pick_handles_mixed_value_types(): void
    {
        $this->assertNull(lang_pick('ja', 'en', null));
        $this->assertSame('en', lang_pick('en', 'en', null));
    }

    public function test_e_escapes_html_special_chars(): void
    {
        $this->assertSame('&lt;b&gt;', e('<b>'));
        $this->assertSame('&quot;hello&quot;', e('"hello"'));
        $this->assertSame('&#039;hello&#039;', e("'hello'"));
        $this->assertSame('a &amp; b', e('a & b'));
    }
}
