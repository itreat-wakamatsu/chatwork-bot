# Chatwork AI Bot (Laravel + MySQL, MVP)

Chatwork の `mention_to_me` Webhook を受信し、トリガーメッセージ + 直近会話を文脈として Gemini で返信文を生成し、同一ルームへ To 付き返信する MVP 実装です。

## フォルダ構成

```text
app/
  Http/
    Controllers/
      ChatworkWebhookController.php
    Middleware/
      VerifyChatworkSignature.php
  Jobs/
    ProcessChatworkMentionJob.php
  Models/
    AiExecution.php
  Services/
    ChatworkClient.php
    GeminiClient.php
    ToolRunner.php
    ChatworkMentionOrchestrator.php
bootstrap/
  app.php
config/
  services.php
database/
  migrations/
    2026_01_01_000000_create_ai_executions_table.php
routes/
  web.php
```

## 実装内容

- `POST /webhook/chatwork` を受信。
- `x-chatworkwebhooksignature` を **生ボディ** で HMAC-SHA256 検証。
- `mention_to_me` かつ本文に `[To:BOT_ACCOUNT_ID]` があるときのみ起動。
- `ai_executions` のユニークキー `(room_id, trigger_message_id)` で冪等管理。
- Chatwork API
  - `GET /rooms/{room_id}/messages?force=1`
  - `POST /rooms/{room_id}/messages`
- Gemini API で system prompt + user prompt を送信し、JSON 応答を解釈。
- 最大 6 ステップの tool loop（MVPは `get_messages` のみ）。
- 失敗時も依頼者へ To 付きエラー返信。

> Webhook は 10 秒以内に 200 応答が必要なため、`ProcessChatworkMentionJob` を利用して即時 200 を返す設計です。同期実行も可能ですが本番は queue ワーカー運用を推奨します。

## セットアップ

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

queue を使う場合（推奨）:

```bash
php artisan queue:work
```

## 環境変数

`.env` に以下を設定してください。

```dotenv
CHATWORK_API_TOKEN=
CHATWORK_WEBHOOK_TOKEN=
BOT_ACCOUNT_ID=
GEMINI_API_KEY=
GEMINI_MODEL=gemini-2.5-flash
APP_URL=https://example.com
DB_CONNECTION=mysql
DB_HOST=...
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
```

## Webhook 設定手順（Chatwork側）

1. `https://<APP_URL>/webhook/chatwork` を Webhook URL に設定。
2. Event は `mention_to_me` を有効化。
3. 表示される webhook token を `CHATWORK_WEBHOOK_TOKEN` に設定。
4. Bot の account_id を `BOT_ACCOUNT_ID` に設定。

## 署名検証の注意（ローカル疎通）

署名は JSON を再エンコードした文字列ではなく、**実際のHTTP生ボディ** を使う必要があります。

### 署名生成例（curlテスト）

```bash
RAW='{"webhook_event_type":"mention_to_me","webhook_event":{"from_account_id":111,"to_account_id":222,"room_id":12345,"message_id":"1001","body":"[To:222] テストです","send_time":1730000000}}'
SECRET_B64='YOUR_CHATWORK_WEBHOOK_TOKEN'
SIG=$(php -r '$raw=$argv[1]; $k=base64_decode($argv[2], true); echo base64_encode(hash_hmac("sha256", $raw, $k, true));' "$RAW" "$SECRET_B64")

curl -i -X POST "http://127.0.0.1:8000/webhook/chatwork" \
  -H "Content-Type: application/json" \
  -H "x-chatworkwebhooksignature: $SIG" \
  --data "$RAW"
```

## 動作確認（最小）

1. Chatworkで bot 宛 To を含むメッセージを投稿。
2. `ai_executions` に `processing -> completed` が記録される。
3. 同一 room / message で再実行されても二重返信しない。

## 仕様上のMVP制約

- Chatwork の取得制約により、履歴は `force=1` の最新 100 件まで。
- `get_messages` は最新100件の再取得 + `before_message_id` より前を切り出す方式。
- 外部検索、別ルーム参照、タスク操作、添付解析、複数投稿分割は未実装（仕様どおり）。
