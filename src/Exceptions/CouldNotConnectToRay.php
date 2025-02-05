<?php

namespace Omisteck\Peek\Exceptions;

use Exception;

class CouldNotConnectToRay extends Exception
{
    public static function make(string $host, int $portNumber): self
    {
        return new static("Couldn't connect to Peek It doesn't seem to be running at {$host}:{$portNumber}");
    }
}
