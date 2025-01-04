<?php

namespace Omisteck\Peek\Payloads;

class ConfettiPayload extends Payload
{
    public function getType(): string
    {
        return 'confetti';
    }

    public function getContent(): array
    {
        return [];
    }
}
