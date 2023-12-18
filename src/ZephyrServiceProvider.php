<?php

namespace RedberryProducts\Zephyr;

use RedberryProducts\Zephyr\Commands\GenerateCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;


class ZephyrServiceProvider extends PackageServiceProvider
{

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
