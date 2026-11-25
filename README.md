<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="brand/lockup-color-on-dark.svg" />
    <img src="brand/lockup-ink.svg" alt="OpenRoam" width="280" />
  </picture>
</p>

# OpenRoam for Laravel

Laravel integration for the [OpenRoam PHP SDK](https://github.com/OpenRoamHQ/openroam-php): a configured client in the
service container, plus middleware that verifies OpenRoam webhook signatures. Requires PHP 8.1+ and Laravel 10–13.

## Install

This package version always matches the OpenRoam SDK it depends on. Install a release candidate by its exact version
(a plain `composer require` will not select it), then require that same SDK version:

<!-- release:install -->
```bash
composer require openroam/laravel:0.1.0-rc.6 openroam/sdk:0.1.0-rc.6
```
<!-- /release:install -->

```bash
php artisan vendor:publish --tag=openroam-config   # optional: copies config/openroam.php
```

Laravel discovers the package automatically; nothing to register.

## Versions

`openroam/laravel` X.Y.Z always ships with `openroam/sdk` X.Y.Z — same number, released together. This package requires
exactly that SDK version, so the two cannot drift. A fix to this package is released as the next version of both.

## Configure

```dotenv
OPENROAM_API_KEY=your-key
OPENROAM_ENVIRONMENT=sandbox        # or production (the default)
OPENROAM_WEBHOOK_SECRET=your-secret # only if you receive webhooks
# OPENROAM_MAX_RETRIES=3            # default 0: no retries
# OPENROAM_BASE_URL=                # unnamed host override only; leave unset to use the environment above
```

### Environments

Partners have exactly two named environments:

| Value | Host |
|---|---|
| `production` (default) | `https://api.openroam.com` |
| `sandbox` | `https://api.sandbox.openroam.com` |

`OPENROAM_BASE_URL` is an unnamed host override for rare cases — not a third environment. The client sends
`OPENROAM_API_KEY` as `X-Partner-Key`. A key works only on the environment that issued it.

## Use the client

Type-hint `OpenRoam\OpenRoam` anywhere Laravel injects (controllers, jobs, listeners, commands), or resolve it from
the container:

```php
use OpenRoam\OpenRoam;

class EsimController
{
    public function index(OpenRoam $openroam)
    {
        return $openroam->esims->search(['countryCode' => 'JP'])->getItems();
    }
}

$openroam = app(OpenRoam::class);   // or app('openroam')
```

One shared client per app, built on first use. A missing key fails then (with the SDK’s message), not at boot. Full
client API — `search`, `quote`, `order`, idempotency keys, `OpenRoamException`, retries — is in the
[PHP SDK README](https://github.com/OpenRoamHQ/openroam-php#readme).

## Verify webhooks

Attach the `openroam.webhook` middleware to the route that receives them:

```php
// routes/api.php
Route::post('/webhooks/openroam', OpenRoamWebhookController::class)->middleware('openroam.webhook');
```

A request that is not validly signed by `OPENROAM_WEBHOOK_SECRET`, or is older than 5 minutes
(`OPENROAM_WEBHOOK_TOLERANCE`), returns `401` and never reaches your controller. If the secret is not set, the
response is `500`, so a setup mistake is not mistaken for a bad sender. The body is checked exactly as received — do
not put a middleware that rewrites the body in front of it.

Webhooks are POSTs from OpenRoam with no session or CSRF token. Exclude the route from CSRF checks
(`$middleware->validateCsrfTokens(except: ['webhooks/openroam'])` in `bootstrap/app.php` on Laravel 11+, or `$except`
in `VerifyCsrfToken` before).

## Test

```bash
composer install && composer test
```

MIT licensed. Support: support@openroam.com.
