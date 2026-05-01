# Survey ID Generator

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![License: UPL-1.0](https://img.shields.io/badge/license-UPL--1.0-green)
[![Test](https://github.com/sakilabo/survey-id-generator-php/actions/workflows/test.yml/badge.svg)](https://github.com/sakilabo/survey-id-generator-php/actions/workflows/test.yml)

> 日本語: [README.ja.md](README.ja.md)

A PHP web tool that generates **survey IDs** — letting you run an online survey that is **easy to answer** and produces **results you can trust**. Pair the generated IDs with the regex-validation feature in Google Forms (or any form builder that supports regex validation).

> The UI is bilingual (Japanese / English) and defaults to Japanese. `Accept-Language` is intentionally ignored to keep the language consistent when links are shared on social media. Switch to English with `?lang=en` or the language link in the upper-right.

> Hosted instance: <https://sakilabo.jp/survey-id-generator/> — use it as is. To self-host, see [Setup](#setup).

## Overview

With **Survey IDs**, you can run **simple, trustworthy surveys without user registration or email-based authentication**. The intended setup: hand each recipient an individual Survey ID, and have them enter it when responding. The tool covers everything from small gatherings like parent–teacher associations and neighborhood/community associations to information gathering at exhibitions and events — anywhere from dozens to tens of thousands of respondents.

### What a trustworthy survey requires

For survey results to actually reflect the target population, two conditions must hold:

1. **The respondent pool is not skewed** — requiring user registration or login acts as an **IT-literacy filter**, screening out a sizable portion of users in the process. The respondents who remain are skewed toward higher IT literacy, and the result no longer reflects the population. A mechanism that lets anyone respond easily is required.
2. **The responses themselves are not wrong** — fake IDs and input mistakes are major causes of unreliable survey results. Whether the cause is malice or accident, a mechanism that ensures the data is free of errors is required.

Most existing survey setups fail one or both of these (see [Related tools](#related-tools) for adjacent options that cover parts of the problem).

### What this tool does

This tool just addresses both of the above; nothing exotic.

- **Against bias**: respondents only have to type the ID they were given. IDs are short (4–7 characters); for a 100-person survey, 4 characters is enough — lighter to type than an email address. The distributor's setup is just pasting the generated **regex** and ID list into Google Forms or similar.
- **Against wrong data**: malice and honest error both produce the same outcome (data that's wrong), so the tool addresses both:
  - Only the distributor holds the ID roster, so any submission with an ID that wasn't actually distributed gets **filtered out at reconciliation**. Without access to the roster, an attacker cannot steer the result.
  - IDs avoid visually confusing characters (`0/o`, `1/i/l`, etc.) and are built so that no two QWERTY-adjacent keys are ever used at the same character position across one batch. **Adjacent-key typos get caught by the form's regex validation**, prompting the respondent to retry on the spot.

Whether you keep a "person ↔ ID" mapping is your decision — the same workflow supports both **identifiable** and **fully anonymous** surveys depending on whether that mapping exists on your side.

> Side note: because the underlying mechanism is just "a regex-validating form/system" + "the respondent types a distributed ID," the same idea can apply outside of surveys.

## How it works

Each ID-length preset narrows the string space in three stages:

| ID length | All combinations | Pattern matches | Distributed | Rate |
| :---: | :---: | :---: | :---: | :---: |
| 4 chars | 28^4 = 614,656 | 10^4 = 10,000 | 100 | 1.00% |
| 5 chars | 28^5 = 17,210,368 | 9^5 = 59,049 | 500 | 0.85% |
| 6 chars | 28^6 = 481,890,304 | 9^6 = 531,441 | 5,000 | 0.94% |
| 7 chars | 28^7 = 13,492,928,512 | 8^7 = 2,097,152 | 20,000 | 0.95% |

The base of **Pattern matches** (10 / 9 / 8) is the **number of distinct characters used at each ID position**. Not all 28 alphabet characters appear at every position — QWERTY-adjacent characters are excluded, narrowing each position to 8–10 characters (see [Typo prevention](#typo-prevention)).

The base varies with ID length for two reasons:

1. **To keep the distribution rate near 1%** — combinations grow exponentially with length, so the base is shrunk a bit to balance the ratio.
2. **To make IDs from separate surveys visually distinguishable to humans** — for example, with 4-char IDs at base 10, only 10 distinct characters appear at each position. If you see an ID from another survey that includes a character outside that set, you can recognize "this isn't from my survey" at a glance. This is about operational legibility for humans, not mathematical collision avoidance.

> The numbers above are the application's default presets. Longer IDs are possible at the design level.

### Typo prevention

Each generated ID is built from a 28-character alphabet (`23456789abcdefghjknprstuwxyz`) — visually confusing characters (`0/o`, `1/i/l`, `q/g`, `m/v`) are excluded. On top of that, the IDs in one batch **avoid using two QWERTY-adjacent keys at the same character position**.

For example, if some distributed ID has `f` as its third character, no other distributed ID has `d` or `g` (the keys on either side of `f`) at that same position.

Set the matching regex (the **ID Recognition Pattern**) as a form-validation rule, and the form will reject inputs where the user accidentally hits an adjacent key — letting them retry instead of submitting a typo.

### Fraud detection

Of all strings that pass the regex pattern, **only about 1% are actually distributed IDs**. Even if an attacker reverse-engineers the pattern and crafts plausible-looking strings, 99% of the time they hit an ID that was never handed out.

Cross-check submitted answers against your distributed-ID list, and a response with an ID that **was never distributed** is almost certainly fraudulent.

> Design goal: not "block every attacker," but "deny attackers control over the aggregate." Submissions with non-distributed IDs are filtered at reconciliation, so without access to the roster, an attacker cannot meaningfully steer the result.

> **Beyond rejection**: if an attacker submits 500 fake IDs, the 1% rate means about 5 might happen to land on actually-distributed IDs and slip through, but the other 495 get filtered out at reconciliation. The **patterns in those 495 rejected inputs** can reveal the attacker's intent and methods. You don't only block bad input — you can **understand it**.

## Usage

1. Open the tool and choose an "ID length" (4 chars = 100 IDs, up to 7 chars = 20,000 IDs).
2. Press **Generate**. The tool returns an **ID Recognition Pattern** (regex) and a **Distribution ID list**.
3. Set the **ID Recognition Pattern** as the regex validation on your survey's input field.
   - In Google Forms: enable "Response validation" on the question, choose "Regular expression" + "Matches", and paste the pattern into the "Pattern" field.
4. Hand out the **Distribution IDs** to respondents. After collection, cross-check submitted IDs against this list to spot fraudulent answers.
5. Bookmark the displayed "bookmark URL" if you want to retrieve the same pattern and IDs later.

Each generation is stored on the server's SQLite for **180 days**. Records past that age are purged automatically; you can also delete a record manually from the UI.

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

## Related tools

Existing tools and services that cover **parts** of the goal:

- [**Qualtrics Authenticator**](https://www.qualtrics.com/support/survey-platform/survey-module/survey-flow/advanced-elements/authenticator/authenticator-overview/) — issues per-contact unique URLs (paid SaaS; no typo prevention)
- [**CANDIDATE**](https://journals.plos.org/plosone/article?id=10.1371/journal.pone.0260569) — hash-based anonymous IDs to re-link multi-session responses (research use; fraud detection and typo prevention out of scope)
- [**REDCap (public-survey fraud guidance)**](https://portal.redcap.yale.edu/news/safeguarding-redcap-public-surveys-tips-prevent-fraud) — reCAPTCHA / timestamp-cluster checks for bots (institutional research platform)
- [**BlockSurvey**](https://blocksurvey.io/features/anonymous-surveys) ([regex validation](https://blocksurvey.io/features/regex-validations) / [duplicate prevention](https://blocksurvey.io/features/prevent-duplicate-submission)) — anonymous + format-level regex + IP/cookie duplicate prevention (SaaS; not built around cross-checking against a distribution list)
- [**Typeform completion codes**](https://community.typeform.com/build-your-typeform-7/how-to-create-a-random-completion-code-id-for-the-end-of-a-typeform-survey-1078) — generates a code *after* a respondent finishes (opposite direction from this tool)
- [**Microsoft Forms / Google Forms anonymous collection**](https://support.microsoft.com/en-us/office/set-up-your-survey-so-names-aren-t-recorded-when-collecting-responses-25dd8442-f6ba-4934-9319-99f9f867f239) — a "do not record names" toggle only (no fraud detection or typo prevention)

## License

[UPL-1.0](https://opensource.org/license/upl)

## Author

[Sakilabo Corporation Ltd.](https://sakilabo.jp/)
