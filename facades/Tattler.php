<?php namespace Grohman\Tattler\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Tattler
 * @package Grohman\Tattler\Facades
 */
class Tattler extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'Grohman\Tattler\Lib\Processor'; }

}