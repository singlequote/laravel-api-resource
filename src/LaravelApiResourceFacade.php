<?php

namespace SingleQuote\LaravelApiResource;

use Illuminate\Support\Facades\Facade;

class LaravelApiResourceFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'LaravelApiResource';
    }
}
