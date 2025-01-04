<?php

namespace Omisteck\Peek\Watchers;

use Omisteck\Peek\PeekProxy;

abstract class Watcher
{
    /** @var bool */
    protected $enabled = false;

    /** @var \Omisteck\Peek\PeekProxy|null */
    protected $peekProxy;

    abstract public function register(): void;

    public function enabled(): bool
    {
        return $this->enabled;
    }

    public function enable(): Watcher
    {
        $this->enabled = true;

        return $this;
    }

    public function disable(): Watcher
    {
        $this->enabled = false;

        return $this;
    }

    public function setPeekProxy(PeekProxy $peekProxy): Watcher
    {
        $this->peekProxy = $peekProxy;

        return $this;
    }
}
