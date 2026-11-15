# OpenRoam for Laravel

Laravel integration for the [OpenRoam PHP SDK](https://github.com/OpenRoamHQ/openroam-php): a configured client in the
container, and a middleware that checks the signature of OpenRoam webhooks. Needs PHP 8.1+ and Laravel 10 to 13.

## Install

This package has the same version as the OpenRoam SDK it uses. A release candidate is installed by its exact version (a plain install
does not pick it up), and then this package requires exactly that SDK version:

<!-- release:install -->
```bash
composer require openroam/laravel:0.1.0-rc.6 openroam/sdk:0.1.0-rc.6
```
<!-- /release:install -->

```bash
php artisan vendor:publish --tag=openroam-config   # optional: copies config/openroam.php
```

Laravel finds the package by itself; there is nothing to register.

## Versions

`openroam/laravel` X.Y.Z always goes with `openroam/sdk` X.Y.Z: the same number, released together, with no exceptions. This package
requires exactly that SDK version, so you can never end up with a mix. A fix to this package is released as the next version of both.

## Configure

```dotenv
OPENROAM_API_KEY=your-key
OPENROAM_ENVIRONMENT=sandbox        # or production (the default)
OPENROAM_WEBHOOK_SECRET=your-secret # only if you receive webhooks
# OPENROAM_MAX_RETRIES=3            # default 0: no retries
```

## Use the client

Type-hint `OpenRoam\OpenRoam` anywhere Laravel injects (controllers, jobs, listeners, commands), or ask the container for it:

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

It is one shared client per app, built on first use. A missing key fails then, with the SDK's message, not at boot. Everything the client
can do is in the [PHP SDK README](https://github.com/OpenRoamHQ/openroam-php#readme): `search`, `quote`, `order`, idempotency keys, errors (`OpenRoamException`), retries.

## Check webhooks

Put the `openroam.webhook` middleware on the route that receives them:

```php
// routes/api.php
Route::post('/webhooks/openroam', OpenRoamWebhookController::class)->middleware('openroam.webhook');
```

A request that is not validly signed by `OPENROAM_WEBHOOK_SECRET`, or is older than 5 minutes (`OPENROAM_WEBHOOK_TOLERANCE`), gets
`401` and never reaches your controller. If the secret is not set, the response is `500`, so a setup mistake is not mistaken for a bad sender.
The body is checked exactly as received, so do not put a middleware that rewrites the body in front of it.

Webhooks are POSTs from OpenRoam's servers, with no session or CSRF token: exclude the route from CSRF checks
(`$middleware->validateCsrfTokens(except: ['webhooks/openroam'])` in `bootstrap/app.php` on Laravel 11+, or `$except` in `VerifyCsrfToken` before).

## Test

```bash
composer install && composer test
```

MIT licensed. Support: support@openroam.com.
