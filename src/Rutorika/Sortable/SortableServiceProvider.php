<?php

namespace Rutorika\Sortable;

use Illuminate\Support\ServiceProvider;

class SortableServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     */
    public function register()
    {
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/sortable.php' => config_path('sortable.php'),
        ]);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
