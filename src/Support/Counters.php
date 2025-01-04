<?php

namespace Omisteck\Peek\Support;

use Omisteck\Peek\BasePeek;

class Counters
{
    /** @var array */
    protected $counters = [];

    public function increment(string $name): array
    {
        if (! isset($this->counters[$name])) {
            $this->counters[$name] = [peek(), 0];
        }

        [$peek, $times] = $this->counters[$name];

        $newTimes = $times + 1;

        $this->counters[$name] = [$peek, $newTimes];

        return [$peek, $newTimes];
    }

    public function get(string $name): int
    {
        if (! isset($this->counters[$name])) {
            return 0;
        }

        return $this->counters[$name][1];
    }

    public function clear(): void
    {
        $this->counters = [];
    }

    public function setPeek(string $name, BasePeek $peek): void
    {
        $this->counters[$name][0] = $peek;
    }
}
