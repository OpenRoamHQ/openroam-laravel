<?php

namespace OpenRoam\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use OpenRoam\WebhookSignatureException;
use OpenRoam\Webhooks;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects (401) any request to the route that is not a validly signed, recent OpenRoam webhook.
 * Registered as the "openroam.webhook" middleware alias.
 */
class VerifyOpenRoamWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('openroam.webhook_secret');
        if (!is_string($secret) || $secret === '') {
            // A server setup problem, not the sender's: say so instead of pretending the signature was wrong.
            abort(500, 'OPENROAM_WEBHOOK_SECRET is not set.');
        }

        try {
            Webhooks::verify(
                secret: $secret,
                timestamp: (string) $request->header('X-OpenRoam-Timestamp', ''),
                eventId: (string) $request->header('X-OpenRoam-Event-Id', ''),
                signature: (string) $request->header('X-OpenRoam-Signature', ''),
                rawBody: $request->getContent(), // exactly as received
                toleranceSeconds: (int) config('openroam.webhook_tolerance', 300),
            );
        } catch (WebhookSignatureException $error) {
            abort(401, $error->getMessage());
        }

        return $next($request);
    }
}
