<?php

namespace Omisteck\Peek\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Omisteck\Peek\BasePeek
 */
class Peek extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Omisteck\Peek\BasePeek::class;
    }
}
