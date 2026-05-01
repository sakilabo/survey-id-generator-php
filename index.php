<?php
/// Survey ID Generator

if (PHP_VERSION_ID < 80200) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "This application requires PHP 8.2 or later. / このアプリケーションには PHP 8.2 以降が必要です。";
    exit;
}

require __DIR__ . '/vendor/autoload.php';

// SQLite DB location
const DB_PATH = __DIR__ . '/data.sqlite';

// How long to keep generation records (days)
const RETENTION_DAYS = 180;

// URL path where this script lives (e.g. '/survey-id-generator').
// Combined with the .htaccess RewriteRule to produce /{id_key}-style URLs.
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

session_start();

// Avoid cache poisoning; rendering the 20,000-ID preset is ~10ms so caching gains nothing.
header('Cache-Control: no-store');

// Pick UI language: explicit ?lang= wins (and persists for the session),
// falling back to the session value, then to Accept-Language.
$lang_explicit = null;
if (isset($_GET['lang']) && in_array($_GET['lang'], ['ja', 'en'], true)) {
    $lang_explicit = $_GET['lang'];
    $_SESSION['lang'] = $lang_explicit;
} elseif (isset($_SESSION['lang']) && in_array($_SESSION['lang'], ['ja', 'en'], true)) {
    $lang_explicit = $_SESSION['lang'];
}
$lang = detect_language($lang_explicit);
$T = load_translations($lang);

// ID-length presets (repeat = ID length, count^repeat = regex match space, limit = number to distribute).
// count=9 leaves headroom under the adjacency-avoidance cap of 15, so each generation gets fresh char variety.
// limit aims for ~1% of the regex match space, rounded to a tidy number suitable as a distribution cap.
$complexity_options = [
    ['count' => 10, 'repeat' => 4, 'limit' => 100],   // 10 ^ 4 = 10,000
    ['count' => 9,  'repeat' => 5, 'limit' => 500],   // 9 ^ 5 = 59,049
    ['count' => 9,  'repeat' => 6, 'limit' => 5000],  // 9 ^ 6 = 531,441
    ['count' => 8,  'repeat' => 7, 'limit' => 20000], // 8 ^ 7 = 2,097,152
];

$db = open_db(DB_PATH);
purge_expired($db, RETENTION_DAYS);

// On the "generate" button: create a new ID, save it, redirect to the bookmark URL.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $idx = (int)($_POST['complexity'] ?? 2);
    $idx = max(0, min(count($complexity_options) - 1, $idx));
    $cfg = $complexity_options[$idx];

    $seeds = [];
    for ($i = 0; $i < $cfg['repeat']; $i++) {
        $seeds[] = pick_id_chars($cfg['count']);
    }
    $rand_seed = random_int(0, PHP_INT_MAX);
    $id_key = bin2hex(random_bytes(8)); // 16-char hex (64 bits)

    save_generation($db, $id_key, $cfg['count'], $cfg['repeat'], $cfg['limit'], $rand_seed, $seeds);

    // Post/Redirect/Get: redirect after POST so reloads don't regenerate.
    header('Location: ' . $base_path . '/' . $id_key);
    exit;
}

// On the "delete server data" button: remove the record and bounce to the top.
// No CSRF token: no login state to protect, and the id_key URL is itself the capability.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && isset($_POST['id'])) {
    delete_generation($db, $_POST['id']);
    header('Location: ' . $base_path . '/');
    exit;
}

// If ?id=... is given, restore the record from the DB. (false = not found, null = not specified)
$record = isset($_GET['id']) ? fetch_generation($db, $_GET['id']) : null;

// Unknown id: redirect to the top page.
if ($record === false) {
    header('Location: ' . $base_path . '/');
    exit;
}

$re_pattern = null;
$all = [];
$selected_complexity = 1;
$current_url = null;
$expires_at = null;
$repeat = 0;

// Absolute URL of the current page (sans query) — used for hreflang tags and,
// on bookmark pages, displayed in the copy-to-clipboard field. Site is HTTPS-only.
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$page_path = $base_path . '/' . (is_array($record) ? $record['id_key'] : '');
$abs_ja_url = 'https://' . $host . $page_path;
$abs_en_url = $abs_ja_url . '?lang=en';

if (is_array($record)) {
    $seeds = json_decode($record['seeds_json'], true);
    $count = (int)$record['char_count'];
    $repeat = (int)$record['repeat_count'];
    $limit = (int)$record['id_limit'];
    $rand_seed = (int)$record['rand_seed'];

    // Reverse-lookup the matching index in $complexity_options (so the dropdown reflects the saved choice).
    foreach ($complexity_options as $i => $opt) {
        if ($opt['count'] === $count && $opt['repeat'] === $repeat) {
            $selected_complexity = $i;
            break;
        }
    }

    $re_pattern = build_regex_pattern($seeds);

    $rng = new \Random\Randomizer(new \Random\Engine\Mt19937($rand_seed));
    $all = sample_ids($seeds, $limit, $rng);

    // CSV download: emit the distribution IDs and exit before any HTML output.
    if (isset($_GET['download'])) {
        $filename = $T['download_filename_prefix'] . $record['id_key'] . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"{$record['id_key']}.csv\"; filename*=UTF-8''" . rawurlencode($filename));
        // CRLF per RFC 4180. ASCII-only IDs so no UTF-8 BOM needed.
        echo implode("\r\n", $all);
        exit;
    }

    $current_url = $abs_ja_url;

    // Expiration date (created_at + RETENTION_DAYS), localised.
    $expires_at = format_expiration_date($record['created_at'], RETENTION_DAYS, $T['date_format']);
}

?>
<!DOCTYPE html>
<html lang="<?= e($T['html_lang']) ?>">

<head>
    <meta charset="utf-8">
    <title><?= e($T['page_title']) ?></title>
    <link rel="alternate" hreflang="ja" href="<?= e($abs_ja_url) ?>">
    <link rel="alternate" hreflang="en" href="<?= e($abs_en_url) ?>">
    <link rel="alternate" hreflang="x-default" href="<?= e($abs_ja_url) ?>">
    <style>
        :root {
            --page-gray: #a0a0a0;
        }

        html {
            margin: 0;
            padding: 0;
        }

        #content {
            width: 960px;
            margin: 15px auto;
            position: relative;
        }

        #lang_switch {
            position: absolute;
            top: 4px;
            right: 10px;
        }

        #dist_info {
            display: flex;
            gap: 24px;
        }

        #dist_info>div:first-child {
            width: 16em;
        }

        #validation_result {
            display: none;
        }

        #validation_result h3 {
            margin: 4px 0 8px;
        }

        #validation_result ul {
            padding-left: 1.5em;
        }

        #validation_result li {
            margin: 4px 0;
            line-height: 1.2em;
        }

        #validation_result .invalid:not([data-value="0"]) {
            color: #C00000;
        }

        #footer {
            font-size: 14px;
            color: var(--page-gray);
            text-align: center;
        }

        h1 {
            font-size: 36px;
            color: #284;
            border-bottom: #284 3px solid;
            margin: 0 0 20px;
        }

        h2 {
            font-size: 24px;
            line-height: 1em;
            margin: 20px 0 10px;
        }

        h3 {
            font-size: 18px;
            line-height: 1em;
            margin: 14px 0 8px;
        }

        img {
            max-width: 100%;
        }

        p {
            font-size: 16px;
            line-height: 1.5em;
            margin: 6px 0;
        }

        ul,
        ol {
            margin-top: 0;
            margin-bottom: 0;
        }

        li {
            margin-top: 6px;
            margin-bottom: 6px;
        }

        li img {
            margin-top: 4px;
        }

        hr {
            border: none;
            border-bottom: 2px solid var(--page-gray);
            margin: 20px 0;
        }

        select {
            font-size: 18px;
        }

        input,
        select,
        textarea,
        button {
            font-family: monospace;
            font-size: 15px;
            padding: 4px 5px;
            width: fit-content;
        }

        input {
            width: 100%;
        }

        textarea {
            width: 7em;
            height: 10em;
        }

        button {
            min-width: 100px;
        }

        button.delete {
            display: block;
            background-color: #ffc0c0;
            border-width: 1px;
            margin: 20px 0 8px auto;
        }
    </style>
    <script>
        const T = <?= json_encode($T, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;

        // Once the page (incl. images) has settled, scroll to the bookmark heading
        // so a deep-linked /{id_key} URL lands on the relevant section.
        window.addEventListener('load', () => {
            const bookmark = document.getElementById('bookmark');
            if (bookmark) bookmark.scrollIntoView({
                behavior: 'smooth'
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const deleteForm = document.querySelector('form.delete-form');
            if (deleteForm) {
                deleteForm.addEventListener('submit', e => {
                    if (!confirm(T.delete_confirm)) e.preventDefault();
                });
            }

            const textarea = document.querySelector('textarea.validation');
            if (!textarea) return;
            // textarea.value is LF-normalised by the browser; /\r?\n/ here is just defensive for clipboard input below.
            const distribution = new Set(document.querySelector('textarea.distribution').value.split(/\r?\n/));
            // Loose match (any alnum, not the reduced ID charset) so invalid/typo entries
            // survive paste and reach the validation count instead of being silently dropped.
            const pattern = /^[0-9a-z]{<?= $repeat ?>}$/;
            textarea.addEventListener('paste', function(e) {
                const pasted = (e.clipboardData || window.clipboardData).getData('text');
                if (pasted.length === 0) return;
                e.preventDefault();
                const valid = pasted.split(/\r?\n/)
                    .map(line => line.trim())
                    .filter(line => pattern.test(line));
                if (valid.length === 0) {
                    alert(T.validation_paste_error);
                    return;
                }
                // Insert/replace like a normal paste: at cursor when no selection,
                // replacing the current selection otherwise. Trailing newline so the next
                // paste/keystroke lands on a fresh line rather than appended to the last ID.
                textarea.setRangeText(valid.join('\n') + '\n', textarea.selectionStart, textarea.selectionEnd, 'end');
            });

            const button = document.querySelector('.validation-button');
            const result = document.getElementById('validation_result');
            // Hide stale result whenever new IDs are pasted in.
            textarea.addEventListener('paste', () => result.style.display = 'none');
            button.addEventListener('click', function() {
                const lines = textarea.value.split('\n').filter(line => line.length > 0);
                const counts = new Map();
                let invalid = 0;
                let unique = 0;
                for (const line of lines) {
                    const prev = counts.get(line) || 0;
                    counts.set(line, prev + 1);
                    if (!distribution.has(line)) invalid++;
                    else if (prev === 0) unique++;
                }
                let duplicates = 0;
                for (const [id, count] of counts) {
                    if (count > 1 && distribution.has(id)) duplicates += count;
                }
                const set = (selector, template, value) => {
                    const el = result.querySelector(selector);
                    el.textContent = template.replace('%s', value);
                    el.dataset.value = value;
                };
                set('.total', T.validation_count_total, lines.length);
                set('.unique', T.validation_count_unique, unique);
                set('.duplicates', T.validation_count_duplicates, duplicates);
                set('.invalid', T.validation_count_invalid, invalid);
                result.style.display = 'block';
            });
        });
    </script>
</head>

<body>
    <div id="content">
        <div id="lang_switch">
            <a href="?lang=<?= $lang === 'ja' ? 'en' : 'ja' ?>"><?= e($T['language_switch_label']) ?></a>
        </div>
        <h1><?= e($T['h1']) ?></h1>
        <div><img src="<?= $T['overview_image'] ?>"></div>
        <h2><?= e($T['whats_this_heading']) ?></h2>
        <p><?= $T['whats_this_body'] ?></p>
        <ul>
            <li><?= e($T['feature_typo_prevention']) ?></li>
            <li><?= e($T['feature_anonymous']) ?></li>
            <li><?= e($T['feature_no_login']) ?></li>
            <li><?= e($T['feature_fraud_detection']) ?></li>
        </ul>
        <p><?= $T['readme_link'] ?></p>
        <h2><?= e($T['usage_heading']) ?></h2>
        <p><?= $T['usage_intro'] ?></p>
        <ul>
            <li><?= $T['usage_step_pattern'] ?></li>
            <li>
                <?= $T['usage_step_ids'] ?>
                <ul>
                    <li><?= $T['usage_tip_sheet_link'] ?></li>
                </ul>
            </li>
        </ul>
        <hr>
        <h2><?= e($T['generate_heading']) ?></h2>
        <form method="post">
            <label><?= e($T['id_length_label']) ?><select name="complexity">
                    <?php foreach ($complexity_options as $i => $opt):
                        $label = sprintf($T['complexity_label_format'], $opt['repeat'], number_format($opt['limit']));
                    ?>
                        <option value="<?= $i ?>" <?= $i === $selected_complexity ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            &nbsp;
            <button type="submit" name="generate" value="1"><?= e($T['generate_button']) ?></button>
        </form>
        <?php if (is_array($record)): ?>
            <hr>
            <h2 id="bookmark"><?= e(sprintf($T['bookmark_url_heading_format'], $expires_at)) ?></h2>
            <input type="text" name="url" readonly value="<?= e($current_url) ?>" onclick="this.select()">
            <form method="post" class="delete-form">
                <input type="hidden" name="id" value="<?= e($record['id_key']) ?>">
                <button class="delete" type="submit" name="delete" value="1"><?= e($T['delete_button']) ?></button>
            </form>
            <h2><?= e($T['pattern_heading']) ?></h2>
            <input type="text" name="id_pattern" readonly value="<?= e($re_pattern) ?>" onclick="this.select()">
            <div id="dist_info">
                <div>
                    <h2><?= e(sprintf($T['distribution_heading_format'], number_format(count($all)))) ?></h2>
                    <div style="display:flex;flex-direction:column;gap:10px;">
                        <?php /* LF to match this file's line endings; browser normalises textarea.value to LF anyway. CSV download uses CRLF separately. */ ?>
                        <textarea class="distribution" readonly onclick="this.select()"><?= join("\n", $all) ?></textarea>
                        <form class="download-form" method="get" action="<?= e($current_url) ?>">
                            <button type="submit" name="download"><?= e($T['download_button']) ?></button>
                        </form>
                    </div>
                </div>
                <div>
                    <h2><?= e($T['validation_heading']) ?></h2>
                    <div style="display:flex;gap:20px;">
                        <div style="display:flex;flex-direction:column;gap:10px;">
                            <textarea class="validation" onclick="this.select()"></textarea>
                            <button class="validation-button" type="button"><?= e($T['validation_button']) ?></button>
                        </div>
                        <div id="validation_result">
                            <h3><?= e($T['validation_result_label']) ?></h3>
                            <ul>
                                <li class="total" data-value="0"></li>
                                <li class="unique" data-value="0"></li>
                                <li class="duplicates" data-value="0"></li>
                                <li class="invalid" data-value="0"></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <hr>
        <div id="footer">
            <?= e($T['copyright']) ?>
        </div>
    </div>
</body>

</html>