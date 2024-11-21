<?php

namespace RedberryProducts\Zephyr;

use RedberryProducts\Zephyr\Commands\GenerateCommand;
use RedberryProducts\Zephyr\Services\ApiService;
use RedberryProducts\Zephyr\Services\TestFilesManagerService;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ZephyrServiceProvider extends PackageServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ApiService::class);
        $this->app->singleton(TestFilesManagerService::class);
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-zephyr')
            ->hasConfigFile('zephyr')
            ->hasCommands([GenerateCommand::class]);
    }
}
