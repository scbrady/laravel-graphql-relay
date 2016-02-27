<?php

namespace Nuwave\Relay\Facades;

use Illuminate\Support\Facades\Facade;

class GraphQL extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \Nuwave\Relay\GraphQL::class;
    }
}
