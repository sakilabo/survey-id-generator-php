# プロジェクトガイド (AI エージェント / コントリビューター向け)

このアプリはレンタルサーバー (共用ホスティング) での動作を想定しています。レンタルサーバーは PHP のバージョンや CLI/CGI のデフォルトがホストごとに異なるため、以下のルールを守ってください。

## PHP バージョン

このアプリは **PHP 8.2 以降が必須** です (`\Random\Randomizer` を使用しているため)。

### コマンド実行前のチェック

`php` / `composer` / `phpunit` などを実行する前に、必ず PHP のバージョンを確認してください：

```bash
php -v
```

`php` のデフォルトが 8.2 未満の場合、明示的にバージョン付きバイナリを使ってください：

```bash
# 例: PHP 8.3 が php8.3 として用意されている環境
php8.3 -v
php8.3 bin/composer.phar install        # 後述の project-local composer を使う
php8.3 vendor/bin/phpunit
```

`composer install` を古い PHP で実行すると `composer.json` の `"php": ">=8.2"` 制約で失敗します。これは正しい挙動なので、エラーを回避するためにバージョン制約を緩めないでください。

## Composer のバージョン

レンタルサーバーのシステム composer は古いことがあるため、**プロジェクト内に `bin/composer.phar` を配置**して使います。

### セットアップ (clone 直後 1 回だけ)

```bash
bin/install-composer.sh
```

これで `bin/composer.phar` (最新版) が取得されます。`bin/composer.phar` 自体は `.gitignore` 済み (バイナリ PHAR は commit しない慣習)。

### 通常の使い方

以降、composer 経由のすべての操作は project-local の composer.phar 経由で：

```bash
php8.3 bin/composer.phar install
php8.3 bin/composer.phar update
php8.3 bin/composer.phar require <pkg>
php8.3 bin/composer.phar test            # = vendor/bin/phpunit
```

GitHub Actions では `shivammathur/setup-php@v2` が新しい composer を提供するので、CI ではシステム composer をそのまま使います。

## 本番データの保護

`data.sqlite` は本番の生成レコードを保持する SQLite ファイルです。**スクリプトと同じディレクトリに置かれている**ため、誤って消さないよう注意してください。

- **`rm data.sqlite` / `unlink('data.sqlite')` / `TRUNCATE` は禁止**。マイグレーション以外で本番 DB を破壊してはいけません。
- **テストでは必ず一時ファイルを使う**：
  ```php
  $db_path = tempnam(sys_get_temp_dir(), 'survey_id_test_');
  $db = open_db($db_path);
  // ... tearDown で @unlink($db_path)
  ```
  既存の `tests/RepositoryTest.php` を参考にしてください。
- **動作確認用の HTTP リクエストも本番 DB に書き込みます**。手動テストでレコードが増えた場合は、UI の「サーバー上のデータを削除する」ボタンで消去してください。

## テスト実行

```bash
composer test                  # = vendor/bin/phpunit
# または
vendor/bin/phpunit
```

`tests/` 配下に PHPUnit のテストがあります。新機能を追加したら対応するテストも追加してください。GitHub Actions (`.github/workflows/test.yml`) で PHP 8.2 / 8.3 / 8.4 のマトリックスで自動実行されます。

## ファイル構成

- `index.php` — HTTP エントリポイント (薄い、HTML テンプレート込み)
- `src/` — テスト可能なロジック。関数ベース、composer の `autoload.files` で読み込む
  - `chars.php` — `DEFAULT_CHARS`, `KEYBOARD_NEIGHBORS`, `pick_id_chars()`
  - `sampling.php` — `sample_ids()`, `build_regex_pattern()`
  - `repository.php` — `open_db()`, `save/fetch/delete_generation()`, `purge_expired()`
  - `helpers.php` — `format_expiration_date()` (第 3 引数で `date()` フォーマット文字列を必須)
  - `i18n.php` — `detect_language()`, `load_translations()`, `e()`
  - `i18n/{ja,en}.php` — UI 翻訳の連想配列。新キー追加時は両ファイルに必ず入れる (テストで対応をチェック)
- `tests/` — PHPUnit テスト
- `.htaccess` — Apache 設定。リライトと内部ファイルの遮断
- `data.sqlite` — 本番 DB (gitignore 済み)

新しい関数を `src/` に追加した際は、`composer.json` の `autoload.files` 配列に必ず登録してください。登録しないと本番でロードされず、テストではロードされる、という不整合が起きます。

## DB スキーマ変更

現状はマイグレーション機構なし。スキーマ変更時は次のいずれか：

1. 既存テーブルと互換性のある変更 (`ALTER TABLE ... ADD COLUMN`) を `open_db()` 内に追加
2. 互換性のない変更は、新カラム追加 + コードの両対応 + 古いカラムの後日削除、という段階的アプローチ

どちらにせよ、本番の `data.sqlite` を壊さない手順を計画してから実行してください。

## レンタルサーバー固有の注意

- **mod_rewrite が有効である前提**。`.htaccess` の `RewriteRule` がないと `/{id_key}` 形式の URL が動かなくなります。共用ホストでは通常有効ですが、デプロイ後に `/{id_key}` URL が 404 を返すなら mod_rewrite が無効の可能性があります。
- **PHP のハンドラ設定**：ホストによっては `.htaccess` で `AddHandler php-fcgi8.3 .php` 等の指定が必要なことがあります。デプロイ後に `phpinfo()` で動作中バージョンを確認してください。
- **`data.sqlite` への書き込み権限**：Web サーバー実行ユーザーが書き込みできる必要があります。多くの共用ホストではデフォルトで OK ですが、500 エラーが出たら権限を確認してください。
