# アンケート ID ジェネレーター

![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![License: UPL-1.0](https://img.shields.io/badge/license-UPL--1.0-green)
[![Test](https://github.com/sakilabo/survey-id-generator-php/actions/workflows/test.yml/badge.svg)](https://github.com/sakilabo/survey-id-generator-php/actions/workflows/test.yml)

> English: [README.md](README.md)

オンラインアンケートを **回答しやすく、結果が信頼できる状態** で実施するための「アンケート ID」を生成する PHP 製の Web ツールです。Google フォームなどの正規表現バリデーションと組み合わせて使います。

> UI は日本語と英語に対応しています。ブラウザの `Accept-Language` ヘッダから言語を判定し、`ja*` なら日本語、それ以外は英語で表示されます。手動の切替 UI はありません。

> <https://sakilabo.jp/survey-id-generator/> でそのまま使えます。自前でホストする場合は [セットアップ](#セットアップ) を参照してください。

## 概要

「アンケート ID」を利用すると、**ユーザー登録やメール認証を必要としない、簡単かつ信頼性の高いアンケート** を実施することができます。対象者に **個別の「アンケート ID」を渡して** 回答してもらう形を想定しています。PTAや町内会などの小さな集まりから、展示会やイベントでの情報収集まで、数十人から数万人の規模に対応しています。

### 信頼できるアンケートに必要なこと

アンケート結果が母集団を反映するためには、ふたつのことが必要です:

1. **回答者層に偏りがないこと** — ユーザー登録やログイン操作の要求は、ある種の (そして多くの) ユーザーを「**IT リテラシーによってふるいにかける**」行為です。これを要求した時点で、回答者は IT リテラシーが高い層に偏り、結果は母集団を反映しなくなります。誰でも簡単に回答できる仕組みが必要です。
2. **回答に間違いがないこと** — ID の偽造や入力ミスは、アンケートの信頼性を低下させる大きな要因の一つです。原因が悪意かどうかにかかわらず、結果に間違いがないことを担保する仕組みが必要です。

既存のアンケートの多く (または殆ど) は、このどちらか — あるいは両方 — を欠いたまま運用されています (一部の機能を備える既存ツールは下記の「[関連ツール](#関連ツール)」を参照)。

### このツールがしていること

このツールは、上記のふたつに対処しているだけで、特別なことはしていません。

- **偏りへの対処**: 回答者に求めるのは、配られた ID を入力することだけ。ID は短く (4〜7 文字) — 100 人規模のアンケートなら 4 文字で済み、回答者の入力負担はメールアドレスを書くより軽いほどです。配布側も、ツールが生成した **正規表現** と ID 一覧を Google フォームなどに貼り付けるだけで設定できます。
- **間違いへの対処**: 原因が悪意でも単純なミスでも、結果を変質させるという意味では同じなので、両方に対処します:
  - 配布側だけが ID 名簿を持つ構造により、**配布外 ID で送られた偽回答は照合で除外** されます。攻撃者は配布名簿を持たない限り、結果をコントロールできません。
  - ID は紛らわしい字 (`0/o`, `1/i/l` など) を避け、同じ文字位置で QWERTY キーボード上の隣接キーが使われないように作られています。**押し間違いはフォームの正規表現バリデーションで弾かれ**、回答者に再入力を促せます。

「配布した人 ↔ ID」の対応表を配布側で持つか持たないかを変えるだけで、**回答者を特定可能にもできるし、完全匿名にもできる** のもこの仕組みの特徴です。

> 補足: 仕組みは「正規表現バリデーションができるフォームやシステム」+「配布した ID の入力」の組み合わせなので、アンケート以外の用途にも応用できる可能性があります。

## 仕組み

ID 文字数の選択ごとに、文字空間が三段階に絞り込まれます:

| ID 文字数 | 文字制限 | ID パターン | 配布数 | 配布率 |
| :---: | :---: | :---: | :---: | :---: |
| 4 文字 | 28^4 = 614,656 | 10^4 = 10,000 | 100 | 1.00% |
| 5 文字 | 28^5 = 17,210,368 | 9^5 = 59,049 | 500 | 0.85% |
| 6 文字 | 28^6 = 481,890,304 | 9^6 = 531,441 | 5,000 | 0.94% |
| 7 文字 | 28^7 = 13,492,928,512 | 8^7 = 2,097,152 | 20,000 | 0.95% |

**ID パターン** の底 (10 / 9 / 8) は、**ID の各文字位置で使える文字数** を表します。28 文字をすべての位置で使うわけではなく、QWERTY 隣接キーを避けるため、各位置で 8〜10 文字に絞られています (詳細は [誤入力防止](#誤入力防止))。

底が ID 文字数によって変わるのには、ふたつの理由があります:

1. **配布率を約 1% に揃えるため** — 文字数が増えると組み合わせ数が指数的に増えるので、底を少し下げてバランスを取っています。
2. **別々のアンケートで配った ID を、人の目で見分けやすくするため** — 例えば 4 文字 / 底 10 のアンケートでは、各文字位置に使われる文字は 10 種類だけです。別のアンケートで配られた ID にその 10 種類以外の文字が含まれていれば、「これは別の ID だ」と人が感覚的に気づけます。数学的な衝突回避よりも、運用上の扱いを重視した設計です。

> 上記はアプリケーションの初期設定です。仕組み上は ID 文字数をさらに増やすことも可能です。

### 誤入力防止

生成される ID は、紛らわしい字を除いた 28 文字 (`23456789abcdefghjknprstuwxyz`) から組み立てられます。さらに、**同じ文字位置に QWERTY キーボード上で隣にある文字を使わない** ように設計されています。

例えば、ある ID の 3 文字目に `f` が使われているとき、配布される他のどの ID を見ても、3 文字目には `f` の左右隣のキー (`d` や `g`) は登場しません。

これに対応する正規表現 (= ID 認識パターン) をフォームのバリデーションに設定すれば、押し間違いを **フォーム側で誤りとして検出** し、回答者に再入力を促せます。

### 不正回答検出

ID パターンを通過する文字列のうち、**実際に配布される ID は約 1% のみ** です。攻撃者が ID パターンを推測してそれっぽい ID を入力しても、99% は配布されていない ID になります。

回答と「配布した ID 一覧」を照合すれば、**配っていない ID で送られてきた回答 = 不正の疑いがある回答** をかなりの精度で発見できます。

> 設計の狙いは「攻撃者を完全に排除する予防」ではなく **「攻撃者が結果をコントロールできない状態」** を作ることです。配布外 ID は照合で除外できるため、配布名簿を持たない攻撃者は、意図した方向に結果を傾けることができません。

> **応用**: 攻撃者が 500 件の偽 ID を投入した場合、配布率 1% から 5 件は偶然配布済み ID に当たって通り抜けてしまうものの、残り 495 件は照合で除外されます。この 495 件の **入力傾向** から、攻撃者の意図や手口を読み取ることもできます — 不正な入力を弾くだけでなく、**不正な入力を理解する** ことも可能です。

## 使い方

1. ツールを開いて「ID 文字数」を選択します (4 文字 = 100 件 〜 7 文字 = 20,000 件)。
2. 「生成」ボタンを押すと **ID 認識パターン** と **配布用 ID 一覧** が表示されます。
3. **ID 認識パターン** をアンケートのフォームに正規表現バリデーションとして設定します。
   - Google フォームの場合: 質問項目の「回答の検証」を有効にし、「正規表現」「一致する」を選択して、「パターン」の欄に貼り付け
4. **配布用 ID** を回答者に配り、回答収集後に照合します。
5. 表示される「ブックマーク用 URL」を保存しておけば、後日同じパターンと ID を復元できます。

生成レコードはサーバーの SQLite に **180 日間** 保存されます。期限切れは自動的に削除され、不要になったレコードは UI から手動削除もできます。

## 動作要件

- PHP **8.2 以降** (`\Random\Randomizer` を使用)
- PHP 拡張: `pdo_sqlite` (`random` は 8.2+ 標準同梱)
- (任意) Apache + `mod_rewrite` — `/{id_key}` 形式のきれいな URL を使う場合のみ。なくても `?id={id_key}` 形式でアクセスできます。

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

## 関連ツール

本ツールが目指す要件の **一部** をカバーする既存ツール・サービス:

- [**Qualtrics Authenticator**](https://www.qualtrics.com/support/survey-platform/survey-module/survey-flow/advanced-elements/authenticator/authenticator-overview/) — 連絡先ごとに個別 URL を発行 (有償 SaaS、誤入力対策なし)
- [**CANDIDATE**](https://journals.plos.org/plosone/article?id=10.1371/journal.pone.0260569) — ハッシュベースの匿名 ID で多セッション回答を再リンク (研究用途、不正検出・誤入力対策はスコープ外)
- [**REDCap (公開サーベイの不正対策)**](https://portal.redcap.yale.edu/news/safeguarding-redcap-public-surveys-tips-prevent-fraud) — reCAPTCHA・タイムスタンプ検査による bot 対策中心 (機関設置の研究系基盤)
- [**BlockSurvey**](https://blocksurvey.io/features/anonymous-surveys) ([regex バリデーション](https://blocksurvey.io/features/regex-validations) / [重複防止](https://blocksurvey.io/features/prevent-duplicate-submission)) — 匿名 + 入力形式 regex 検証 + IP/Cookie ベース重複防止 (SaaS、配布済み ID 照合の発想ではない)
- [**Typeform 完了コード**](https://community.typeform.com/build-your-typeform-7/how-to-create-a-random-completion-code-id-for-the-end-of-a-typeform-survey-1078) — 回答**後**の謝礼用コード発行 (本ツールとは目的が逆)
- [**Microsoft Forms / Google Forms 標準の匿名収集**](https://support.microsoft.com/en-us/office/set-up-your-survey-so-names-aren-t-recorded-when-collecting-responses-25dd8442-f6ba-4934-9319-99f9f867f239) — 名前を記録しない設定のみ (不正検出・誤入力対策なし)

## ライセンス

[UPL-1.0](https://opensource.org/license/upl)

## 著者

[株式会社さきラボ](https://さきラボ.jp/)
