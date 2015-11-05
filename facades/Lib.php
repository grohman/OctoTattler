<?php namespace Grohman\Tattler\Facades;

use Illuminate\Support\Facades\Facade;

class Lib extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'Grohman\Tattler\Lib\Tattler'; }

}