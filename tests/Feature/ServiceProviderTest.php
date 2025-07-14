<?php

declare(strict_types=1);

namespace Tests\Feature;

use Orchestra\Testbench\TestCase;
use Siberfx\TurkiyeAdreslerJson\TurkiyeAdreslerServiceProvider;

class ServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [TurkiyeAdreslerServiceProvider::class];
    }

    public function test_service_provider_is_loaded()
    {
        $this->assertTrue(
            $this->app->providerIsLoaded(TurkiyeAdreslerServiceProvider::class),
            'Service provider should be loaded.'
        );
    }
}
