<?php

namespace BoomCMS\Core\Chunk;

use Illuminate\Support\ServiceProvider;

class ChunkServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton('boomcms.chunk', function ($app) {
            return new Provider($app['boomcms.auth'], $app['boomcms.editor']);
        });
    }

    /**
     *
     * @return void
     */
    public function register() {}

}