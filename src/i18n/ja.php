<?php

return [
    'html_lang'                   => 'ja',

    'page_title'                  => 'アンケート ID ジェネレーター',
    'h1'                          => 'アンケート ID ジェネレーター',

    'overview_image'              => 'overview.ja.jpg',

    'whats_this_heading'          => 'これはなに？',
    'whats_this_body'             => '「アンケート ID」を生成するツールです。以下のような特徴があります。',
    'feature_typo_prevention'     => '入力ミスが発生しにくい ID パターンを生成',
    'feature_fraud_detection'     => 'ID 照合で不正回答を検出',

    'readme_link'                 => '技術的な詳細については、GitHub にある <a href="https://github.com/sakilabo/survey-id-generator-php/blob/main/README.ja.md">README</a> をご確認ください。',

    'usage_heading'               => '使い方',
    'usage_intro'                 => '「ID 文字数」を選択して「生成」ボタンを押してください。「ID 認識パターン」と「配布用 ID」が生成されます。',
    'usage_step_pattern'          => '「ID 認識パターン」は、アンケートの入力フォームに設定してください。Google フォームの場合は、設問の右下にある 「︙」 から 「回答の検証」 を有効にして、「正規表現」の「パターン」欄に「ID 認識パターン」を貼り付けてください。<br><img src="form_config.png" />',
    'usage_step_ids'              => '「配布用 ID」は回答者に配る ID の一覧です。配布用 ID と回答の ID を照合すると、不正な回答を検出することができます。',
    'usage_tip_sheet_link'        => 'Google フォームの場合、作業性を良くするため、<a href="https://support.google.com/docs/answer/2917686">回答をスプレッドシートにリンク</a>しておくことを推奨します。',

    'generate_heading'            => 'ID 生成',
    'id_length_label'             => 'ID 文字数 (ID 配布数)：',
    'complexity_label_format'     => '%d 文字 (%s件)',
    'generate_button'             => '生成',

    'bookmark_url_heading_format' => 'ブックマーク用 URL (%sまで有効)',
    'delete_confirm'              => "サーバー上のデータを削除します。\n削除後はこの URL からアクセスできなくなります。\n本当に削除しますか？",
    'delete_button'               => 'サーバー上のデータを削除する',
    'pattern_heading'             => 'ID 認識パターン (フォーム用正規表現)',
    'distribution_heading_format' => '配布用 ID (%s件)',
    'download_button'             => 'ダウンロード',
    'download_filename_prefix'    => 'アンケートID ',

    'validation_heading'          => '回答 ID 検証',
    'validation_button'           => '検証実行',
    'validation_paste_error'      => 'ID を認識できませんでした',
    'validation_result_label'     => '検証結果:',
    'validation_count_total'      => '回答件数: %s件',
    'validation_count_unique'     => '回答 ID 数: %s件',
    'validation_count_duplicates' => '重複: %s件',
    'validation_count_invalid'    => '不正: %s件',

    'date_format'                 => 'Y年n月j日',

    'copyright'                   => '© 2026 株式会社さきラボ All rights reserved.',
];
