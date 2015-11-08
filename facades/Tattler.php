<?php namespace Grohman\Tattler\Facades;

use Illuminate\Support\Facades\Facade;

class Tattler extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'Grohman\Tattler\Lib\Processor'; }

}