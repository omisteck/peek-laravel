<?php

namespace Omisteck\Peek\Payloads;

use Omisteck\Peek\ArgumentConverter;

class ApplicationLogPayload extends Payload
{
    /** @var string */
    protected $value;

    /** @var array */
    protected $context;

    public function __construct(string $value, array $context = [])
    {
        $this->value = $value;
        $this->context = $context;
    }

    public function getType(): string
    {
        return 'application_log';
    }

    public function getContent(): array
    {
        $content = [
            'value' => $this->value,
        ];

        if (count($this->context)) {
            $content['context'] = ArgumentConverter::convertToPrimitive($this->context);
        }

        return $content;
    }
}
