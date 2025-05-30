<?php

namespace Omisteck\Peek\Payloads;

use Omisteck\Peek\ArgumentConverter;

class DecodedJsonPayload extends Payload
{
    /** @var string */
    protected $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getType(): string
    {
        return 'custom';
    }

    public function getContent(): array
    {
        $decodedJson = json_decode($this->value, true);

        return [
            'content' => ArgumentConverter::convertToPrimitive($decodedJson),
            'label' => '',
        ];
    }
}
