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
            $cachename = config('services.cacherequest.drive') ?? config('cache.default');
            return new CacheRequest($cachename);
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
