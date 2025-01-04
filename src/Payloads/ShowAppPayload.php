<?php

namespace Omisteck\Peek\Payloads;

class ShowAppPayload extends Payload
{
    public function getType(): string
    {
        return 'show_app';
    }
}
