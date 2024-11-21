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
        $this->app->singleton('zephyr-api', function () {
            return new ApiService;
        });

        $this->app->singleton('zephyr-test-files-manager', function () {
            return new TestFilesManagerService;
        });
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
            ->hasConfigFile('zephyr')
            ->hasCommands([GenerateCommand::class]);
    }
}
