<?php

namespace PodPoint\AwsPubSub\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pubsub:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the PubSubEventServiceProvider';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (file_exists(app_path('Providers/PubSubEventServiceProvider.php'))) {
            $this->error('PubSubEventServiceProvider already exists!');

            return 1;
        }

        copy(__DIR__ . '/../Sub/stubs/app/Providers/PubSubEventServiceProvider.php', app_path('Providers/PubSubEventServiceProvider.php'));

        $this->installServiceProviderAfter('EventServiceProvider', 'PubSubEventServiceProvider');
    }

    /**
     * Install the service provider in the application configuration file.
     *
     * @param  string  $after
     * @param  string  $name
     * @return void
     */
    protected function installServiceProviderAfter($after, $name)
    {
        if (! Str::contains($appConfig = file_get_contents(config_path('app.php')), 'App\\Providers\\' . $name . '::class')) {
            file_put_contents(config_path('app.php'), str_replace(
                'App\\Providers\\' . $after . '::class,',
                'App\\Providers\\' . $after . '::class,' . PHP_EOL . '        App\\Providers\\' . $name . '::class,',
                $appConfig
            ));
        }
    }
}
