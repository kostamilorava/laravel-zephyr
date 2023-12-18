<?php

namespace RedberryProducts\Zephyr\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \RedberryProducts\Zephyr\Zephyr
 */
class Zephyr extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \RedberryProducts\Zephyr\Zephyr::class;
    }
}
