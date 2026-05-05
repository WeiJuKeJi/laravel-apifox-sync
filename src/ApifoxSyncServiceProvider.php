<?php

namespace Weijukeji\LaravelApifoxSync;

use Illuminate\Support\ServiceProvider;
use Weijukeji\LaravelApifoxSync\Commands\ApifoxSyncCommand;

class ApifoxSyncServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/apifox.php' => config_path('apifox.php'),
        ], 'apifox-sync-config');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/apifox.php', 'apifox');

        $this->app->singleton(ApifoxImporter::class);
        $this->app->singleton(ApifoxDocumentFinder::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                ApifoxSyncCommand::class,
            ]);
        }
    }
}
