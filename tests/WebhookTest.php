<?php

namespace OpenRoam\Laravel\Tests;

use Illuminate\Support\Facades\Route;

class WebhookTest extends TestCase
{
    private const SECRET = 'whsec_test';

    protected function defineEnvironment($app): void
    {
        $app['config']->set('openroam.webhook_secret', self::SECRET);
    }

    protected function defineRoutes($router): void
    {
        Route::post('/webhooks/openroam', fn () => response()->json(['ok' => true]))->middleware('openroam.webhook');
    }

    /** @return array<string, string> */
    private function signedHeaders(string $body, ?int $timestamp = null, string $secret = self::SECRET): array
    {
        $timestamp ??= time();
        $eventId = 'evt_1';

        return [
            'X-OpenRoam-Timestamp' => (string) $timestamp,
            'X-OpenRoam-Event-Id' => $eventId,
            'X-OpenRoam-Signature' => hash_hmac('sha256', "{$timestamp}.{$eventId}.{$body}", $secret),
            'CONTENT_TYPE' => 'application/json',
        ];
    }

    private function sendWebhook(string $body, array $headers)
    {
        $server = [];
        foreach ($headers as $name => $value) {
            $server[$name === 'CONTENT_TYPE' ? $name : 'HTTP_' . strtoupper(str_replace('-', '_', $name))] = $value;
        }

        return $this->call('POST', '/webhooks/openroam', [], [], [], $server, $body);
    }

    public function test_a_validly_signed_webhook_passes(): void
    {
        $body = '{"type":"order.item.refunded"}';

        $this->sendWebhook($body, $this->signedHeaders($body))->assertOk()->assertJson(['ok' => true]);
    }

    public function test_a_wrong_signature_is_rejected(): void
    {
        $body = '{"type":"order.item.refunded"}';

        $this->sendWebhook($body, $this->signedHeaders($body, secret: 'another-secret'))->assertStatus(401);
    }

    public function test_a_changed_body_is_rejected(): void
    {
        $headers = $this->signedHeaders('{"amount":1}');

        $this->sendWebhook('{"amount":2}', $headers)->assertStatus(401);
    }

    public function test_an_old_webhook_is_rejected(): void
    {
        $body = '{}';

        $this->sendWebhook($body, $this->signedHeaders($body, time() - 3600))->assertStatus(401);
    }

    public function test_missing_headers_are_rejected(): void
    {
        $this->call('POST', '/webhooks/openroam', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{}')->assertStatus(401);
    }

    public function test_a_missing_secret_is_a_server_error_not_a_401(): void
    {
        config(['openroam.webhook_secret' => null]);
        $body = '{}';

        $this->sendWebhook($body, $this->signedHeaders($body))->assertStatus(500);
    }
}
