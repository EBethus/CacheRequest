<?php
namespace EBethus\CacheRequest;

use Illuminate\Support\ServiceProvider;

class CacheRequestProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(CacheRequest::class, function ($app) {
            return new CacheRequest();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [CacheRequest::class];
    }

    public function boot()
    {
    }
}
