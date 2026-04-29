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

    public function test_falls_back_to_english_when_primary_is_other(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.9';
        $this->assertSame('en', detect_language());
    }

    public function test_falls_back_to_english_when_japanese_is_secondary(): void
    {
        // Per the agreed simple rule: only the first tag matters.
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,ja;q=0.5';
        $this->assertSame('en', detect_language());
    }

    public function test_falls_back_to_english_when_header_missing(): void
    {
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $this->assertSame('en', detect_language());
    }

    public function test_falls_back_to_english_when_header_empty(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = '';
        $this->assertSame('en', detect_language());
    }

    public function test_handles_unknown_language(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR,fr;q=0.9';
        $this->assertSame('en', detect_language());
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
