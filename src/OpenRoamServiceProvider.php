<?php

namespace OpenRoam\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use OpenRoam\Laravel\Http\Middleware\VerifyOpenRoamWebhook;
use OpenRoam\OpenRoam;

class OpenRoamServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/openroam.php', 'openroam');

        // One client per app, built lazily from config/openroam.php (so a missing key only fails when it is used).
        $this->app->singleton(OpenRoam::class, function (Application $app): OpenRoam {
            $config = $app['config']->get('openroam');

            $options = [
                'apiKey' => $config['api_key'] ?? null,
                'environment' => $config['environment'] ?? 'production',
                'maxRetries' => (int) ($config['max_retries'] ?? 0),
            ];
            if (!empty($config['base_url'])) {
                $options['baseUrl'] = $config['base_url'];
            }

            // A null apiKey makes the SDK fall back to the OPENROAM_API_KEY environment variable, then fail with a clear message.
            return new OpenRoam(array_filter($options, static fn ($value) => $value !== null));
        });
        $this->app->alias(OpenRoam::class, 'openroam');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../config/openroam.php' => config_path('openroam.php')], 'openroam-config');
        }

        $this->app->make(Router::class)->aliasMiddleware('openroam.webhook', VerifyOpenRoamWebhook::class);
    }
}
