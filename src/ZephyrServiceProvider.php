<?php

namespace RedberryProducts\Zephyr;

use RedberryProducts\Zephyr\Commands\GenerateCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\MediaCollections\Models\Observers\MediaObserver;

class ZephyrServiceProvider extends PackageServiceProvider
{
    public function boot()
    {
        $this->registerPublishables();
    }

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-zephyr')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel-zephyr_table')
            ->hasCommands([GenerateCommand::class]);
    }


    protected function registerPublishables(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/zephyr.php' => config_path('zephyr.php'),
        ], 'config');

    }
}
