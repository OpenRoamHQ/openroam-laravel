<?php

namespace OpenRoam\Laravel\Tests;

use OpenRoam\OpenRoam;
use OpenRoam\OpenRoamException;

class ClientTest extends TestCase
{
    public function test_the_client_comes_from_the_container_and_is_shared(): void
    {
        config(['openroam.api_key' => 'test-key', 'openroam.environment' => 'sandbox']);

        $client = $this->app->make(OpenRoam::class);

        $this->assertInstanceOf(OpenRoam::class, $client);
        $this->assertSame($client, $this->app->make(OpenRoam::class));
        $this->assertSame($client, $this->app->make('openroam'));
    }

    public function test_a_missing_key_fails_with_the_sdk_message_only_when_the_client_is_used(): void
    {
        config(['openroam.api_key' => null]);
        putenv('OPENROAM_API_KEY');

        $this->expectException(OpenRoamException::class);
        $this->expectExceptionMessage('Missing API key');
        $this->app->make(OpenRoam::class);
    }

    public function test_an_unknown_environment_is_rejected(): void
    {
        config(['openroam.api_key' => 'test-key', 'openroam.environment' => 'staging']);

        $this->expectException(OpenRoamException::class);
        $this->app->make(OpenRoam::class);
    }

    public function test_the_config_has_safe_defaults(): void
    {
        $this->assertSame('production', config('openroam.environment'));
        $this->assertSame(0, config('openroam.max_retries'));
    }
}
