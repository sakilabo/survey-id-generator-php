<?php

return [
    'html_lang'                   => 'ja',

    'page_title'                  => 'アンケート ID ジェネレーター',
    'h1'                          => 'アンケート ID ジェネレーター',

    'whats_this_heading'          => 'これはなに？',
    'whats_this_body'             => '「アンケート ID」を生成するツールです。',
    'whats_this_value'            => 'アンケート ID を利用すると、Google フォームやその他のオンラインフォームで、回答が簡単かつ信頼性の高いアンケートを実施することができます。',

    'typo_prevention_body'        => '生成される ID は、押し間違いが起きにくい構成になっています。ID 認識パターンをフォームに設定すれば、入力ミスはフォーム側で弾かれます。',

    'fraud_detection_body'        => 'アンケート ID は、入力可能な文字列のうち <strong>約 1%</strong> だけが配布されます。配布した ID の一覧と回答を照合すれば、不正な回答 (= 配布していない ID で送られた回答) を見つけられます。',

    'readme_link'                 => '技術的な詳細については、GitHub にある <a href="https://github.com/sakilabo/survey-id-generator-php/blob/main/README.ja.md">README</a> をご確認ください。',

    'usage_heading'               => '使い方',
    'usage_intro'                 => '「生成」ボタンを押すと「ID 認識パターン」と「配布用 ID」が生成されます。',
    'usage_step_pattern'          => '「ID 認識パターン」は、アンケートの入力フォームに設定してください。Google フォームの場合は、質問項目で「回答の検証」を有効にし、「正規表現」と「一致する」を選択して、「パターン」の欄に「ID 認識パターン」を貼り付けてください。',
    'usage_step_ids'              => '「配布用 ID」は、回答者に配って入力してもらう ID の一覧です。あとで不正な回答を見つけるときに回答内容と照合する必要があるため、どこかに保存しておくか、ID 生成後のページの URL をブックマークしておいてください。',

    'generate_heading'            => 'ID 生成',
    'id_length_label'             => 'ID 文字数 (ID 配布数)：',
    'complexity_label_format'     => '%d 文字 (%s件)',
    'generate_button'             => '生成',

    'bookmark_url_heading_format' => 'ブックマーク用 URL (%sまで有効)',
    'delete_confirm'              => "サーバー上のデータを削除します。\n削除後はこの URL からアクセスできなくなります。\n本当に削除しますか？",
    'delete_button'               => 'サーバー上のデータを削除する',
    'pattern_heading'             => 'ID 認識パターン (フォーム用正規表現)',
    'distribution_heading_format' => '配布用 ID (%s件)',

    'date_format'                 => 'Y年n月j日',

    'copyright'                   => '© 2026 株式会社さきラボ All rights reserved.',
];
