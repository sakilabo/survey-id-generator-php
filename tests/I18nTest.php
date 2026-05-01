<?php

use PHPUnit\Framework\TestCase;

final class I18nTest extends TestCase
{
    private ?string $original_accept_language;

    protected function setUp(): void
    {
        $this->original_accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->original_accept_language === null) {
            unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        } else {
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $this->original_accept_language;
        }
    }

    public function test_detects_japanese_when_primary(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ja-JP,ja;q=0.9,en;q=0.5';
        $this->assertSame('ja', detect_language());
    }

    public function test_detects_japanese_bare_tag(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ja';
        $this->assertSame('ja', detect_language());
    }

    public function test_returns_english_when_primary_is_english(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.9';
        $this->assertSame('en', detect_language());
    }

    public function test_returns_english_when_english_outranks_japanese(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,ja;q=0.5';
        $this->assertSame('en', detect_language());
    }

    public function test_returns_japanese_when_japanese_outranks_english(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ja,en;q=0.5';
        $this->assertSame('ja', detect_language());
    }

    public function test_returns_japanese_on_q_value_tie(): void
    {
        // Ties go to ja so that '/' is indexed as the Japanese version.
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en;q=0.5,ja;q=0.5';
        $this->assertSame('ja', detect_language());
    }

    public function test_defaults_to_japanese_when_header_missing(): void
    {
        // Common case for crawlers (Googlebot's default crawl) — keeps '/' indexed as Japanese.
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $this->assertSame('ja', detect_language());
    }

    public function test_defaults_to_japanese_when_header_empty(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = '';
        $this->assertSame('ja', detect_language());
    }

    public function test_defaults_to_japanese_when_unknown_language(): void
    {
        // No "en" preference at all → fall through to the Japanese default.
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR,fr;q=0.9';
        $this->assertSame('ja', detect_language());
    }

    public function test_explicit_override_takes_precedence(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.9';
        $this->assertSame('ja', detect_language('ja'));

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ja';
        $this->assertSame('en', detect_language('en'));
    }

    public function test_invalid_explicit_override_is_ignored(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ja';
        $this->assertSame('ja', detect_language('fr'));
        $this->assertSame('ja', detect_language(''));
    }

    public function test_picks_highest_q_value_across_multiple_japanese_entries(): void
    {
        // ja-JP at q=1.0 should outweigh en-US at q=0.9.
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr;q=0.3,en-US;q=0.9,ja-JP,ja;q=0.8';
        $this->assertSame('ja', detect_language());
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

    public function test_e_escapes_html_special_chars(): void
    {
        $this->assertSame('&lt;b&gt;', e('<b>'));
        $this->assertSame('&quot;hello&quot;', e('"hello"'));
        $this->assertSame('&#039;hello&#039;', e("'hello'"));
        $this->assertSame('a &amp; b', e('a & b'));
    }
}
