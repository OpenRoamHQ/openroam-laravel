<?php

namespace OpenRoam\Laravel\Tests;

use OpenRoam\Laravel\OpenRoamServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [OpenRoamServiceProvider::class];
    }
}
