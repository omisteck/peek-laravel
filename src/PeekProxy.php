<?php

namespace Omisteck\Peek;

class PeekProxy
{
    /** @var array */
    protected $methodsCalled = [];

    public function __call($method, $arguments)
    {
        $this->methodsCalled[] = compact('method', 'arguments');
    }

    public function applyCalledMethods(BasePeek $peek)
    {
        foreach ($this->methodsCalled as $methodCalled) {
            call_user_func_array([$peek, $methodCalled['method']], $methodCalled['arguments']);
        }
    }
}
