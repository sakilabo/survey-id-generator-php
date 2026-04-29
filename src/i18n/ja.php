<?php

return [
    'html_lang'                   => 'ja',

    'page_title'                  => 'アンケート ID ジェネレーター',
    'h1'                          => 'アンケート ID ジェネレーター',

    'whats_this_heading'          => 'これはなに？',
    'whats_this_body'             => '誤入力や不正回答を防止するための「アンケート回答用 ID」を生成するツールです。',

    'typo_prevention_heading'     => '誤入力防止',
    'typo_prevention_body'        => 'スマホの縦画面ではキーの横間隔が特に狭く、隣接キーの押し間違いが起きやすくなっています。このツールで生成される ID は、<strong>同じ文字位置に「隣り合うキー」を使わない</strong>ように作られています。例えば、ある ID の 3 文字目に "f" が使われている場合、他のどの ID を見ても、3 文字目には "f" の隣にある "d" や "g" が登場しません。この仕組みによって、隣のキーを押し間違えた場合の入力ミスを検出できるようになっています。',

    'fraud_detection_heading'     => '不正回答検出',
    'fraud_detection_body'        => 'このツールで生成される ID は、適当な文字を打ち込んだくらいではアンケートの入力欄を通過できないように作られています。さらに、入力欄を通過できる文字列のうち、<strong>実際に配布される ID はおよそ 1% だけ</strong>です。パターンを解析してそれっぽい文字列を入力しても、99% は（配布されていない）不正な ID になります。この仕組みによって、アンケートの回答を「配布した ID の一覧」と照合すると、<strong>配っていない ID で送られてきた回答 = 不正の疑いがある回答</strong>を、かなり高い確率で見つけられるようになっています。',

    'usage_heading'               => '使い方',
    'usage_intro'                 => '「生成」ボタンを押すと「ID 認識パターン」と「配布用 ID」が生成されます。',
    'usage_step_pattern'          => '「ID 認識パターン」は、アンケートの入力フォームに設定してください。Google フォームの場合は、質問項目で「回答の検証」を有効にし、「正規表現」と「一致する」を選択して、「パターン」の欄に「ID 認識パターン」を貼り付けてください。',
    'usage_step_ids'              => '「配布用 ID」は、回答者に配って入力してもらう ID の一覧です。あとで不正な回答を見つけるときに回答内容と照合する必要があるため、どこかに保存しておくか、ID 生成後のページの URL をブックマークしておいてください。',

    'generate_heading'            => 'ID 生成',
    'id_length_label'             => 'ID 文字数 (有効 ID 数)：',
    'complexity_label_format'     => '%d 文字 (%s件)',
    'generate_button'             => '生成',

    'bookmark_url_heading_format' => 'ブックマーク用 URL (%sまで有効)',
    'delete_confirm'              => "サーバー上のデータを削除します。\n削除後はこの URL からアクセスできなくなります。\n本当に削除しますか？",
    'delete_button'               => 'サーバー上のデータを削除する',
    'pattern_heading'             => 'ID 認識パターン (フォーム用正規表現)',
    'distribution_heading_format' => '配布用 ID (%s件)',

    'date_format'                 => 'Y年n月j日',

    'copyright'                   => '© 2026 Sakilabo Corporation Ltd. All rights reserved.',
];
