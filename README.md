# Survey ID Generator

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![License: UPL-1.0](https://img.shields.io/badge/license-UPL--1.0-green)
[![Test](https://github.com/sakilabo/survey-id-generator-php/actions/workflows/test.yml/badge.svg)](https://github.com/sakilabo/survey-id-generator-php/actions/workflows/test.yml)

> 日本語: [README.ja.md](README.ja.md)

A small PHP web tool that generates respondent IDs for online surveys, designed to **prevent typos** and **make fraudulent responses easy to detect**. Pair the generated IDs with the regex-validation feature in Google Forms (or any form builder that supports regex validation).

> The UI is bilingual (Japanese / English). Language is selected from the browser's `Accept-Language` header — Japanese for `ja*`, English for everything else. There is no manual switcher.

## Why this tool exists

The goal is to enable **proper anonymous-or-identifiable surveys without making respondents log in.** "Proper" here means satisfying all of:

- Either identifiable or fully anonymous, depending on use — and switchable on the same workflow
- Fraudulent answers (impersonation, duplicate submissions, responses from outsiders) are detectable after collection
- Simple input mistakes (e.g., mobile typos) are caught at form-submission time

To my knowledge, no existing tool satisfies all of these *at once* (plus the constraints of "no login" and "drops onto a free form like Google Forms with self-hosted infra"). Adjacent tools cover parts of the problem — see [Related tools](#related-tools).

This tool fills the gap. Combine the generated **ID Recognition Pattern** (a regex) and the **Distribution ID list** with a free form builder, and the resulting survey meets all of the above. **It runs on a local laptop or on shared hosting that costs a few dollars a month** — anywhere PHP 8.2+ runs. Whether the survey is identifiable or anonymous depends entirely on whether *you* keep a "person → ID" mapping; the same workflow handles both modes.

## Related tools

Existing tools and services that cover **parts** of the goal:

- [**Qualtrics Authenticator**](https://www.qualtrics.com/support/survey-platform/survey-module/survey-flow/advanced-elements/authenticator/authenticator-overview/) — issues per-contact unique URLs (paid SaaS; no typo prevention)
- [**CANDIDATE**](https://journals.plos.org/plosone/article?id=10.1371/journal.pone.0260569) — hash-based anonymous IDs to re-link multi-session responses (research use; fraud detection and typo prevention out of scope)
- [**REDCap (public-survey fraud guidance)**](https://portal.redcap.yale.edu/news/safeguarding-redcap-public-surveys-tips-prevent-fraud) — reCAPTCHA / timestamp-cluster checks for bots (institutional research platform)
- [**BlockSurvey**](https://blocksurvey.io/features/anonymous-surveys) ([regex validation](https://blocksurvey.io/features/regex-validations) / [duplicate prevention](https://blocksurvey.io/features/prevent-duplicate-submission)) — anonymous + format-level regex + IP/cookie duplicate prevention (SaaS; not built around cross-checking against a distribution list)
- [**Typeform completion codes**](https://community.typeform.com/build-your-typeform-7/how-to-create-a-random-completion-code-id-for-the-end-of-a-typeform-survey-1078) — generates a code *after* a respondent finishes (opposite direction from this tool)
- [**Microsoft Forms / Google Forms anonymous collection**](https://support.microsoft.com/en-us/office/set-up-your-survey-so-names-aren-t-recorded-when-collecting-responses-25dd8442-f6ba-4934-9319-99f9f867f239) — a "do not record names" toggle only (no fraud detection or typo prevention)

## How it works

### Typo prevention

Each generated ID is built from a 28-character alphabet (`23456789abcdefghjknprstuwxyz`) — visually confusing characters (`0/o`, `1/i/l`, `q/g`, `m/v`) are excluded. On top of that, **at any single character position, two QWERTY-adjacent keys are never both used** across the IDs distributed in one batch.

For example, if some distributed ID has `f` as its third character, no other distributed ID has `d` or `g` (the keys on either side of `f`) at that same position.

Set the matching regex (the **ID Recognition Pattern**) as a form-validation rule, and the form will reject inputs where the user accidentally pressed an adjacent key — letting them retry instead of submitting a typo.

### Fraud detection

A two-stage filter detects fraudulent responses:

1. **The recognition pattern itself is narrow** — random typing very rarely passes form validation.
2. **Only ~1% of pattern-matching strings are actually distributed** — even if an attacker reverse-engineers the pattern and crafts a "plausible" string, 99% of the time it isn't an ID you actually handed out.

Cross-check submitted answers against your distributed-ID list, and a response with an ID that **was never distributed** is almost certainly fraudulent.

> Design philosophy: prefer detectability over prevention. A determined attacker can be stopped only at very high cost; building a system where fraud is *cheap to detect afterward* is more practical.

## Requirements

- PHP **8.2+** (uses `\Random\Randomizer`)
- PHP extensions: `pdo_sqlite` (the `random` extension ships with 8.2+ by default)
- *Optional* — Apache + `mod_rewrite`: only needed for `/{id_key}`-style pretty URLs. Without them, you can use `?id={id_key}` URLs.

Runs anywhere PHP runs — local laptop, shared hosting, VPS.

## Setup

```bash
git clone https://github.com/sakilabo/survey-id-generator-php.git survey-id-generator
cd survey-id-generator

# Fetch a project-local composer.phar
# (skip this if your system composer is recent enough)
bin/install-composer.sh

# Install dependencies
php bin/composer.phar install --no-dev
```

The SQLite database (`data.sqlite`) is created on first request.

> **PHP version note**: if your system's `php` defaults to a version older than 8.2, invoke a versioned binary explicitly (e.g. `php8.3 bin/composer.phar install`). Running `composer install` against an older PHP fails because of the `composer.json` constraint.

### Run locally

The PHP built-in web server is enough.

```bash
php -S localhost:8000
```

Open <http://localhost:8000/>. Without Apache, `.htaccess` rewrites are inactive and `/{id_key}` short URLs won't work — but the tool itself works fine. Replace `/{id_key}` in the displayed bookmark URL with `?id={id_key}` to navigate manually.

### Deploy to a server

Clone into your document root with the same setup steps; the tool becomes available at `https://your-domain.example/survey-id-generator/`. To enable `/{id_key}` short URLs you need Apache with `mod_rewrite` enabled and `.htaccess` allowed (the default on most shared hosts).

## Usage

1. Open the tool and choose an "ID length" (4 chars = 100 IDs, up to 7 chars = 20,000 IDs).
2. Press **Generate**. The tool returns an **ID Recognition Pattern** (regex) and a **Distribution ID list**.
3. Set the **ID Recognition Pattern** as the regex validation on your survey's input field.
   - In Google Forms: enable "Response validation" on the question, choose "Regular expression" + "Matches", and paste the pattern into the "Pattern" field.
4. Hand out the **Distribution IDs** to respondents. After collection, cross-check submitted IDs against this list to spot fraudulent answers.
5. Bookmark the displayed "bookmark URL" if you want to retrieve the same pattern and IDs later.

Each generation is stored on the server's SQLite for **180 days**. Records past that age are purged automatically; you can also delete a record manually from the UI.

## Project layout

```
.
├── index.php             HTTP entry point (HTML template inline)
├── .htaccess             mod_rewrite + access controls for internal files
├── src/
│   ├── chars.php         DEFAULT_CHARS, KEYBOARD_NEIGHBORS, pick_id_chars()
│   ├── sampling.php      sample_ids(), build_regex_pattern()
│   ├── repository.php    open_db(), save/fetch/delete_generation(), purge_expired()
│   ├── helpers.php       format_expiration_date()
│   ├── i18n.php          detect_language(), load_translations(), e()
│   └── i18n/
│       ├── ja.php        Japanese UI strings
│       └── en.php        English UI strings
├── tests/                PHPUnit tests
├── bin/
│   └── install-composer.sh   Fetches a project-local composer.phar
└── data.sqlite           Production DB (gitignored)
```

## Development

### Run tests

```bash
php vendor/bin/phpunit
# or
php bin/composer.phar test
```

GitHub Actions runs the suite on PHP 8.2 / 8.3 / 8.4 / 8.5 in a matrix.

### Adding a new function under `src/`

Add it to the `autoload.files` array in [composer.json](composer.json). Forgetting this means the function loads in tests (which require files individually) but not in production — a "tests pass, production breaks" inconsistency.

### Production DB safety

`data.sqlite` holds production records. **Do not destroy it** outside of migrations (no `rm`, no `unlink`, no `TRUNCATE`). For tests, always use a temporary file via `tempnam(sys_get_temp_dir(), ...)`.

## License

[UPL-1.0](https://opensource.org/license/upl)

## Author

[Sakilabo Corporation Ltd.](https://sakilabo.jp/)
