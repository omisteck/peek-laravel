<?php

namespace Omisteck\Peek\Payloads;

class CallerPayload extends Payload
{
    /** @var array */
    protected $frames;

    public function __construct(array $frames)
    {
        $this->frames = $frames;
    }

    public function getType(): string
    {
        return 'caller';
    }

    public function getContent(): array
    {
        $frames = [];
        if (is_array($this->frames)) {
            $frames = array_map(function ($frame) {
                return  [
                    'file' => $frame['file'] ?? null,
                    'line' => $frame['line'] ?? null,
                    'class' => $frame['class'] ?? null,
                    'method' => $frame['function'] ?? null,
                    'relative_file' => function_exists('base_path')
                        ? str_replace(base_path(), '', $caller['file'] ?? '')
                        : ($caller['file'] ?? null),
                ];
            }, $this->frames);
        } else {
            $frames = [
                'file' => $caller['file'] ?? null,
                'line' => $caller['line'] ?? null,
                'class' => $caller['class'] ?? null,
                'method' => $caller['function'] ?? null,
                'relative_file' => function_exists('base_path')
                    ? str_replace(base_path(), '', $caller['file'] ?? '')
                    : ($caller['file'] ?? null),
            ];
        }




        return $frames;
    }
}
