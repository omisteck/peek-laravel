<?php

namespace Omisteck\Peek\Payloads;

class NotifyPayload extends Payload
{
    /** @var string */
    protected $text;

    public function __construct(string $text)
    {
        $this->text = $text;
    }

    public function getType(): string
    {
        return 'notify';
    }

    public function getContent(): array
    {
        return [
            'value' => $this->text,
        ];
    }
}
