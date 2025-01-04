<?php

namespace Omisteck\Peek\Support;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}
