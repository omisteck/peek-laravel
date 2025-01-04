<?php

namespace Omisteck\Peek\Facades;

use Illuminate\Support\Facades\Facade;

class Peek extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'peek';
    }
}
