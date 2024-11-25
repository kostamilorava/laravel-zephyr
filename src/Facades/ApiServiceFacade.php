<?php

namespace RedberryProducts\Zephyr\Facades;

use Illuminate\Support\Facades\Facade;
use RedberryProducts\Zephyr\Services\ApiService;

class ApiServiceFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ApiService::class;
    }
}
