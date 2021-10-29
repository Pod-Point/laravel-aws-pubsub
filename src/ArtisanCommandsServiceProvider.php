<?php

namespace PodPoint\AwsPubSub;

use Illuminate\Support\ServiceProvider;
use PodPoint\AwsPubSub\Console\InstallCommand;
use PodPoint\AwsPubSub\Console\ListenerMakeCommand;

class ArtisanCommandsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                ListenerMakeCommand::class,
            ]);
        }
    }

    public function register()
    {
        //
    }
}
