<?php

namespace PodPoint\AwsPubSub\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
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
     * @return int|void
     */
    public function handle()
    {
        if (file_exists(app_path('Providers/PubSubEventServiceProvider.php'))) {
            $this->error('PubSubEventServiceProvider already exists!');

            return 1;
        }

        copy(__DIR__.'/../Sub/stubs/app/Providers/PubSubEventServiceProvider.php', app_path('Providers/PubSubEventServiceProvider.php'));

        $this->installServiceProvider('PubSubEventServiceProvider');

        $this->info('PubSubEventServiceProvider created successfully.');
    }

    /**
     * Install the service provider in the application configuration file.
     *
     * @param  string  $name
     * @return void
     */
    protected function installServiceProvider(string $name): void
    {
        $providerFqn = 'App\\Providers\\'.$name.'::class';

        if (! Str::contains($appConfigString = file_get_contents(config_path('app.php')), $providerFqn)) {
            $lastProvider = $this->getLastRegisteredProvider().'::class,';

            file_put_contents(config_path('app.php'), str_replace(
                $lastProvider,
                $lastProvider.PHP_EOL.'        '.$providerFqn.',',
                $appConfigString
            ));
        }
    }

    /**
     * @return string
     */
    protected function getLastRegisteredProvider(): string
    {
        $appConfigArray = include config_path('app.php');

        return Arr::last($appConfigArray['providers']);
    }
}
