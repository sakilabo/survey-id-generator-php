# アンケート ID ジェネレーター

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![License: UPL-1.0](https://img.shields.io/badge/license-UPL--1.0-green)
[![Test](https://github.com/sakilabo/survey-id-generator-php/actions/workflows/test.yml/badge.svg)](https://github.com/sakilabo/survey-id-generator-php/actions/workflows/test.yml)

> English: [README.md](README.md)

オンラインアンケートで **誤入力を起きにくくし、不正回答を検出しやすくする** ための「アンケート回答用 ID」を生成する PHP 製の Web ツールです。Google フォームなどの正規表現バリデーションと組み合わせて使います。

> UI は日本語と英語のバイリンガル対応です。ブラウザの `Accept-Language` ヘッダから言語を判定し、`ja*` なら日本語、それ以外は英語で表示されます。手動の切替 UI はありません。

## なぜこのツールが必要か

**ログイン不要で「適切な」アンケートを実施する**ためのツールです。ここでいう「適切」とは、次の条件を同時に満たすことを指します。

- 用途に応じて **回答者を特定もできるし、完全匿名にもできる**
- なりすまし・複数回答・対象外からの回答などの **不正回答を検出できる**
- 押し間違いなどの **単純な入力ミスを防止できる**

調べた限り、この 5 点 (上記 3 点 + ログイン不要 + 既存の無料フォームに乗せられる自前運用) を **同時に** 満たすツールは見当たりませんでした。一部の機能を持つ既存ツールは下記の「[関連ツール](#関連ツール)」を参照してください。

このツールは、それらの隙間を埋めるために作られています。配布した「ID 認識パターン (正規表現)」と「配布用 ID 一覧」を Google フォームなど既存の無料フォームに組み合わせるだけで、上記の条件を満たすアンケートを実施できます。**手元のローカル PC でも、月額数百円のレンタルサーバーでも動きます** — PHP 8.2+ が動く環境なら何でも構いません。「配布した人 ↔ ID」の対応表をユーザー側で持つか持たないかを変えるだけで、**特定可能なアンケートと完全匿名のアンケートを同じ仕組みで切り替えられる**のも特徴です。

## 関連ツール

本ツールが目指す要件の **一部** をカバーする既存ツール・サービス:

- [**Qualtrics Authenticator**](https://www.qualtrics.com/support/survey-platform/survey-module/survey-flow/advanced-elements/authenticator/authenticator-overview/) — 連絡先ごとに個別 URL を発行 (有償 SaaS、誤入力対策なし)
- [**CANDIDATE**](https://journals.plos.org/plosone/article?id=10.1371/journal.pone.0260569) — ハッシュベースの匿名 ID で多セッション回答を再リンク (研究用途、不正検出・誤入力対策はスコープ外)
- [**REDCap (公開サーベイの不正対策)**](https://portal.redcap.yale.edu/news/safeguarding-redcap-public-surveys-tips-prevent-fraud) — reCAPTCHA・タイムスタンプ検査による bot 対策中心 (機関設置の研究系基盤)
- [**BlockSurvey**](https://blocksurvey.io/features/anonymous-surveys) ([regex バリデーション](https://blocksurvey.io/features/regex-validations) / [重複防止](https://blocksurvey.io/features/prevent-duplicate-submission)) — 匿名 + 入力形式 regex 検証 + IP/Cookie ベース重複防止 (SaaS、配布済み ID 照合の発想ではない)
- [**Typeform 完了コード**](https://community.typeform.com/build-your-typeform-7/how-to-create-a-random-completion-code-id-for-the-end-of-a-typeform-survey-1078) — 回答**後**の謝礼用コード発行 (本ツールとは目的が逆)
- [**Microsoft Forms / Google Forms 標準の匿名収集**](https://support.microsoft.com/en-us/office/set-up-your-survey-so-names-aren-t-recorded-when-collecting-responses-25dd8442-f6ba-4934-9319-99f9f867f239) — 名前を記録しない設定のみ (不正検出・誤入力対策なし)

## 仕組み

### 誤入力防止

生成される ID は、紛らわしい字を除いた 28 文字 (`23456789abcdefghjknprstuwxyz`) から組み立てられます。さらに、**同じ文字位置に QWERTY キーボード上で隣接するキー文字を絶対に使わない** ように設計されています。

例えば、ある ID の 3 文字目に `f` が使われているとき、配布される他のどの ID を見ても、3 文字目には `f` の左右隣のキー (`d` や `g`) は登場しません。

これに対応する正規表現 (= ID 認識パターン) をフォームのバリデーションに設定すれば、押し間違いを **フォーム側で誤りとして検出** し、回答者に再入力を促せます。

### 不正回答検出

ID は **二段階のフィルタ** で不正回答を抑制します。

1. **ID 認識パターン自体が狭い** — 適当な文字を打ち込んでもまずパターンを通過できません
2. **配布率が約 1%** — パターンを通過する文字列のうち、実際に配布される ID は約 1% のみ

仮に攻撃者がパターンを推測してそれっぽい ID を入力できても、99% は配布されていない ID です。回答と「配布した ID 一覧」を照合すれば、**配っていない ID で送られてきた回答 = 不正の疑いがある回答** をかなりの精度で発見できます。

> 攻撃者を完全に排除する「予防」ではなく **不正があった場合に検出する** 設計方針としています。

## 動作要件

- PHP **8.2 以降** (`\Random\Randomizer` を使用)
- PHP 拡張: `pdo_sqlite` (`random` は 8.2+ 標準同梱)
- (任意) Apache + `mod_rewrite` — `/{id_key}` 形式のきれいな URL を使う場合のみ。なくても `?id={id_key}` 形式でアクセスできます

ローカル PC、レンタルサーバー、共用ホスティングなど **PHP が動く環境ならどこでも** 動作します。

## セットアップ

```bash
git clone https://github.com/sakilabo/survey-id-generator-php.git survey-id-generator
cd survey-id-generator

# プロジェクトローカルの composer.phar を取得
# (システムの composer が十分に新しい場合には不要)
bin/install-composer.sh

# 依存パッケージをインストール
php8.3 bin/composer.phar install --no-dev
```

SQLite データベース (`data.sqlite`) は初回アクセス時に自動生成されます。

> **PHP のバージョンに注意**: `php` のデフォルトが 8.2 未満の環境では、`php8.3` のようにバージョン付きのバイナリを明示してください。古い PHP で `composer install` を実行すると `composer.json` の制約により失敗します。

### ローカルで動かす

PHP 内蔵 Web サーバーで起動するだけで使えます。

```bash
php -S localhost:8000
```

http://localhost:8000/ にアクセスしてください。Apache がないため `.htaccess` のリライトは効かず `/{id_key}` 形式の短縮 URL は使えませんが、ツール本体は問題なく動作します (生成後に表示されるブックマーク URL は `?id={id_key}` 形式に手で置き換えてください)。

### サーバーに配置する

ドキュメントルート配下に上記の手順でクローンすると、`https://your-domain.example/survey-id-generator/` でアクセスできます。`/{id_key}` 形式の短縮 URL を有効にするには `.htaccess` を解釈する Apache + `mod_rewrite` が必要です (多くの共用ホスティングでは初期状態で有効)。

## 使い方

1. ツールを開いて「ID 文字数」を選択します (4 文字 = 100 件 〜 7 文字 = 20,000 件)
2. 「生成」ボタンを押すと **ID 認識パターン** と **配布用 ID 一覧** が表示されます
3. **ID 認識パターン** をアンケートのフォームに正規表現バリデーションとして設定します
   - Google フォームの場合: 質問項目の「回答の検証」を有効にし、「正規表現」「一致する」を選択して、「パターン」の欄に貼り付け
4. **配布用 ID** を回答者に配り、回答収集後に照合します
5. 表示される「ブックマーク用 URL」を保存しておけば、後日同じパターンと ID を復元できます

生成レコードはサーバーの SQLite に **180 日間** 保存されます。期限切れは自動的に削除され、不要になったレコードは UI から手動削除もできます。

## ファイル構成

```
.
├── index.php             HTTP エントリポイント (HTML テンプレート込み)
├── .htaccess             mod_rewrite と内部ファイルの遮断
├── src/
│   ├── chars.php         DEFAULT_CHARS, KEYBOARD_NEIGHBORS, pick_id_chars()
│   ├── sampling.php      sample_ids(), build_regex_pattern()
│   ├── repository.php    open_db(), save/fetch/delete_generation(), purge_expired()
│   ├── helpers.php       format_expiration_date()
│   ├── i18n.php          detect_language(), load_translations(), e()
│   └── i18n/
│       ├── ja.php        日本語 UI 文字列
│       └── en.php        英語 UI 文字列
├── tests/                PHPUnit テスト
├── bin/
│   └── install-composer.sh  プロジェクトローカル composer の取得スクリプト
└── data.sqlite           本番 DB (gitignore 済み)
```

## 開発

### テスト実行

```bash
php vendor/bin/phpunit
# または
php bin/composer.phar test
```

GitHub Actions で PHP 8.2 / 8.3 / 8.4 / 8.5 のマトリックスで自動実行されます。

### 新しい関数を `src/` に追加するとき

[composer.json](composer.json) の `autoload.files` 配列に必ず追加してください。登録しないと本番環境でロードされません (テストでは個別に require されるため、テストだけ通って本番が壊れる、という不整合が起きます)。

### 本番 DB の保護

本番環境のデータは `data.sqlite` に記録されています。**このデータを破壊しないように注意してください** (`rm`, `unlink`, `TRUNCATE` は禁止)。テストでは必ず一時ファイル (`tempnam(sys_get_temp_dir(), ...)`) を使ってください。詳細は [CLAUDE.md](CLAUDE.md) を参照。

## ライセンス

[UPL-1.0](https://opensource.org/license/upl)

## 著者

[株式会社さきラボ](https://さきラボ.jp/)
