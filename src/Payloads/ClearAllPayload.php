<?php

namespace Omisteck\Peek\Payloads;

class ClearAllPayload extends Payload
{
    public function getType(): string
    {
        return 'clear_all';
    }
}
