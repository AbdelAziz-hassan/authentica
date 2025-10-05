# Authentica for Laravel

> A secure, DX‑friendly Laravel SDK for **Authentica** (Format: 1A, Host: `https://api.authentica.sa/api/v2`).  
> Features: OTP (SMS/WhatsApp/Email), Nafath (with **package‑handled webhook → single event**), Face & Voice verification, and an **optional circuit breaker**.

---

## Table of Contents

- [Highlights](#highlights)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
  - [OTP](#otp)
  - [Nafath](#nafath)
  - [Webhook (package‑handled)](#webhook-packagehandled)
  - [Face Verification](#face-verification)
  - [Voice Verification](#voice-verification)
- [Circuit Breaker (Optional)](#circuit-breaker-optional)
- [Security Notes](#security-notes)
- [Troubleshooting](#troubleshooting)
- [Versioning](#versioning)
- [License](#license)

---

## Highlights

- **Clean API** returning a predictable shape: `{ ok, status, body, message, circuit? }`
- **Zero app boilerplate** for Nafath webhooks — the package authenticates, validates, and **dispatches a single Laravel event** for you to handle.
- **Secure by default**: JSON‑only responses, strict validation, Basic/Password webhook auth.
- **Production‑ready** optional **circuit breaker** that you can enable/disable and choose the backing store for (Cache or in‑memory).

---

## Requirements

- PHP **8.1+**
- Laravel **9.x / 10.x / 11.x**
- Composer

---

## Installation

```bash
composer require abdelaziz-hassan/authentica
```

The service provider and facades are auto‑discovered.

(Optional) Publish the configuration file:

```bash
php artisan vendor:publish --tag=authentica-config
```

---

## Configuration

Set the basics in `.env`:

```dotenv
AUTHENTICA_API_KEY=your-authentica-api-key
AUTHENTICA_HOST=https://api.authentica.sa/api/v2
```

### Nafath Webhook

Enable and choose **one** auth mode:

```dotenv
AUTHENTICA_NAFATH_WEBHOOK_ENABLED=true
AUTHENTICA_NAFATH_WEBHOOK_PATH=/webhooks/authentica/nafath

# ONE of: body_password | basic
AUTHENTICA_NAFATH_WEBHOOK_AUTH=body_password

# Body password mode
AUTHENTICA_NAFATH_PASSWORD_LABEL=Password
AUTHENTICA_NAFATH_PASSWORD_VALUE=super-secret

# OR Basic mode (header OR body labels accepted)
# AUTHENTICA_NAFATH_WEBHOOK_AUTH=basic
# AUTHENTICA_NAFATH_BASIC_USER_LABEL=username
# AUTHENTICA_NAFATH_BASIC_USER_VALUE=authentica
# AUTHENTICA_NAFATH_BASIC_PASS_LABEL=password
# AUTHENTICA_NAFATH_BASIC_PASS_VALUE=strongpass
```

### Circuit Breaker (Optional)

Disabled by default. Enable if you want the SDK to **fast‑fail** during upstream outages:

```dotenv
AUTHENTICA_CIRCUIT_ENABLED=true
AUTHENTICA_CIRCUIT_DRIVER=cache   # or: array   (in‑memory)
# AUTHENTICA_CIRCUIT_FAILURES=5
# AUTHENTICA_CIRCUIT_OPEN=60
# AUTHENTICA_CIRCUIT_SUCC=2
```

---

## Usage

Import the facade:

```php
use AbdelAzizHassan\Authentica\Facades\Authentica;
```

All client calls return:

```php
[
  'ok'      => bool,                  // Http::successful()
  'status'  => int,                   // HTTP status (0 => network error)
  'body'    => array|null,            // decoded JSON
  'message' => string|null,           // body['message'] if present
  'circuit' => ['state' => 'closed|open|half_open'] // when breaker enabled
]
```

### OTP

**Send OTP**
```php
$res = Authentica::sendOtp([
  'method' => 'sms',                  // sms|whatsapp|email
  'phone'  => '+9665XXXXXXXXX',       // required for sms/whatsapp
  // 'email'     => 'user@test.test', // required for email
  // 'template_id' => 31,
  // 'fallback_email' => 'fallback@test.test',
  // 'otp' => '123456',               // optional custom OTP
]);
```

**Verify OTP**
```php
$res = Authentica::verifyOtp([
  'phone' => '+9665XXXXXXXXX',  // or 'email'
  'email' => 'user@test.test',
  'otp'   => '123456',
]);
```

### Nafath

**Start verification**
```php
$res = Authentica::verifyByNafath([
  'national_id' => '123XXXXXXX',
]);
// $res['body']['data'] -> ['TransactionId' => '...', 'Code' => '...']
```

**Nafath with data**
```php
// 1) request OTP to device
$r1 = Authentica::nafathRequest([
  'national_id' => '123XXXXXXX',
  'phone_number'=> '966XXXXXXX',
]);

// 2) verify to get Code
$r2 = Authentica::nafathVerify([
  'otp'        => 'xxxxxx',
  'request_id' => 'xxxxx-xxxx-xxxx-xxxx-xxxxxxxxx',
]);
```

### Webhook (package‑handled)

The package registers a `POST` route (default `/webhooks/authentica/nafath`).  
It **authenticates**, **validates**, and **dispatches** one event:

```php
\AbdelAzizHassan\Authentica\Webhooks\Events\NafathWebhookReceived
```

Listen to it in your app:

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    \AbdelAzizHassan\Authentica\Webhooks\Events\NafathWebhookReceived::class => [
        \App\Listeners\HandleNafathWebhook::class,
    ],
];
```

```php
// app/Listeners/HandleNafathWebhook.php
use AbdelAzizHassan\Authentica\Webhooks\Events\NafathWebhookReceived;

class HandleNafathWebhook
{
    public function handle(NafathWebhookReceived $e): void
    {
        // $e->transactionId  string
        // $e->nationalId     string
        // $e->status         "COMPLETED" | "REJECTED"
        // $e->raw            full original payload
        // ... your logic here ...
    }
}
```

**Example payload from Authentica**
```json
{
  "TransactionId": "6419747b-b0d7-4564-8755-a7f554d16b10",
  "NationalId": "1234567891",
  "Status": "COMPLETED",
  "Password": "your-secure-password"
}
```

### Face Verification

```php
// Verify by face (base64 images as JSON)
$res = Authentica::verifyByFace([
  'user_id'              => 'u-123',
  // 'registered_face_image' => 'base64-...',
  'query_face_image'     => 'base64-...',
]);

// Store/Get/Delete reference face image
Authentica::storeFaceImage('u-123', ['face_image' => 'base64-...']);
Authentica::getFaceImage('u-123');
Authentica::deleteFaceImage('u-123');
```

### Voice Verification

```php
// Verify by voice (base64 audio as JSON)
$res = Authentica::verifyByVoice([
  'user_id'         => 'u-123',
  // 'registered_audio' => 'base64-...',
  'query_audio'     => 'base64-...',
]);

// Store/Get/Delete reference audio
Authentica::storeVoice('u-123', ['audio' => 'base64-...']);
Authentica::getVoice('u-123');
Authentica::deleteVoice('u-123');
```

---

## Circuit Breaker (Optional)

When enabled, the SDK protects your app from upstream instability.

- Opens after consecutive **network errors** or configured **HTTP statuses** (`429,500,502,503,504` by default).
- While open, calls **short‑circuit** with:
  ```json
  { "ok": false, "status": 503, "message": "Upstream temporarily unavailable (circuit open)" }
  ```
- After a cool‑down, it **half‑opens** and closes on a few successes.

Enable in `.env`:

```dotenv
AUTHENTICA_CIRCUIT_ENABLED=true
AUTHENTICA_CIRCUIT_DRIVER=cache   # or array
# AUTHENTICA_CIRCUIT_FAILURES=5
# AUTHENTICA_CIRCUIT_OPEN=60
# AUTHENTICA_CIRCUIT_SUCC=2
```

You can check the state in every response:
```php
$res['circuit']['state'] ?? null; // 'closed' | 'open' | 'half_open'
```

---

## Security Notes

- **Webhook auth**: choose **one** mode — `body_password` or `basic`.
  - **Basic** accepts standard `Authorization: Basic …` header **or** labeled fields in JSON (`username_label`/`password_label`).
- All webhook responses are **JSON** (no Blade pages).
- Inputs are **validated**; failures return `422` with details.

---

## Troubleshooting

- **Got Blade/HTML in webhook?** Ensure the route is under the `api` stack and the package’s JSON middleware is active (it is by default).
- **Auth fails on webhook**: Double‑check your `.env` values and whether you’re sending header or labeled fields.
- **Circuit always open**: Start with `AUTHENTICA_CIRCUIT_FAILURES=1` in dev and watch logs; ensure your cache driver works if using `cache` mode.

---

## Versioning

Follows **SemVer**.  
Targets Authentica **Format: 1A**, base host `https://api.authentica.sa/api/v2`.

---

## License

MIT © AbdelAziz Hassan
