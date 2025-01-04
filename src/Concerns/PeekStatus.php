<?php

namespace Omisteck\Peek\Concerns;

/** @mixin \Omisteck\Peek\BasePeek */
trait PeekStatus
{
    public function success(): self
    {
        return $this->status('success');
    }

    public function warning(): self
    {
        return $this->status('warning');
    }

    public function error(): self
    {
        return $this->status('error');
    }

    public function info(): self
    {
        return $this->status('info');
    }

    public function debug(): self
    {
        return $this->status('debug');
    }
}
