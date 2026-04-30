<?php
/// Survey ID Generator

if (PHP_VERSION_ID < 80200) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "This application requires PHP 8.2 or later. / このアプリケーションには PHP 8.2 以降が必要です。";
    exit;
}

require __DIR__ . '/vendor/autoload.php';

// Pick the UI language from the browser's Accept-Language header (ja or en).
$T = load_translations(detect_language());

// SQLite DB location
const DB_PATH = __DIR__ . '/data.sqlite';

// How long to keep generation records (days)
const RETENTION_DAYS = 180;

// ID-length presets (repeat = ID length, count^repeat = regex match space, limit = number to distribute).
// count=9 leaves headroom under the adjacency-avoidance cap of 15, so each generation gets fresh char variety.
// limit aims for ~1% of the regex match space, rounded to a tidy number suitable as a distribution cap.
$complexity_options = [
    ['count' => 10, 'repeat' => 4, 'limit' => 100],   // 10 ^ 4 = 10,000
    ['count' => 9,  'repeat' => 5, 'limit' => 500],   // 9 ^ 5 = 59,049
    ['count' => 9,  'repeat' => 6, 'limit' => 5000],  // 9 ^ 6 = 531,441
    ['count' => 8,  'repeat' => 7, 'limit' => 20000], // 8 ^ 7 = 2,097,152
];

// URL path where this script lives (e.g. '/survey-id-generator').
// Combined with the .htaccess RewriteRule to produce /{id_key}-style URLs.
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

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

    // URL of the current page (shown in the UI so it can be copied).
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $current_url = $scheme . '://' . $host . $base_path . '/' . $record['id_key'];

    // Expiration date (created_at + RETENTION_DAYS), localised.
    $expires_at = format_expiration_date($record['created_at'], RETENTION_DAYS, $T['date_format']);
}

?>
<html lang="<?= e($T['html_lang']) ?>">

<head>
    <title><?= e($T['page_title']) ?></title>
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

        p {
            font-size: 16px;
            line-height: 1.5em;
            margin: 6px 0;
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
            padding: 4px;
        }

        input {
            width: 100%;
        }

        textarea {
            width: 7em;
            height: 10em;
        }

        button.delete {
            display: block;
            background-color: #ffc0c0;
            border-width: 1px;
            margin: 20px 0 8px auto;
        }
    </style>
</head>

<body>
    <div id="content">
        <h1><?= e($T['h1']) ?></h1>
        <h2><?= e($T['whats_this_heading']) ?></h2>
        <p><?= $T['whats_this_body'] ?></p>
        <h3><?= e($T['typo_prevention_heading']) ?></h3>
        <p><?= $T['typo_prevention_body'] ?></p>
        <h3><?= e($T['fraud_detection_heading']) ?></h3>
        <p><?= $T['fraud_detection_body'] ?></p>
        <h2><?= e($T['usage_heading']) ?></h2>
        <p><?= $T['usage_intro'] ?></p>
        <ul>
            <li><?= $T['usage_step_pattern'] ?></li>
            <li><?= $T['usage_step_ids'] ?></li>
        </ul>
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
            <hr />
            <h2><?= e(sprintf($T['bookmark_url_heading_format'], $expires_at)) ?></h2>
            <input type="text" readonly value="<?= e($current_url) ?>" onclick="this.select()" />
            <form method="post" onsubmit="return confirm(<?= e(json_encode($T['delete_confirm'], JSON_UNESCAPED_UNICODE)) ?>);">
                <input type="hidden" name="id" value="<?= e($record['id_key']) ?>" />
                <button class="delete" type="submit" name="delete" value="1"><?= e($T['delete_button']) ?></button>
            </form>
            <h2><?= e($T['pattern_heading']) ?></h2>
            <input type="text" readonly value="<?= e($re_pattern) ?>" onclick="this.select()" />
            <h2><?= e(sprintf($T['distribution_heading_format'], number_format(count($all)))) ?></h2>
            <textarea readonly onclick="this.select()"><?= join("\n", $all) ?></textarea>
        <?php endif; ?>
        <hr />
        <div id="footer">
            <?= e($T['copyright']) ?>
        </div>
    </div>
</body>

</html>