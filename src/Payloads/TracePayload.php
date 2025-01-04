<?php

namespace Omisteck\Peek\Payloads;

use Spatie\Backtrace\Frame;

class TracePayload extends Payload
{
    /** @var array */
    protected $frames;

    /** @var int|null */
    protected $startFromIndex = null;

    /** @var int|null */
    protected $limit = null;

    public function __construct(array $frames)
    {
        $this->frames = $frames;
    }

    public function startFromIndex(int $index): self
    {
        $this->startFromIndex = $index;

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function getType(): string
    {
        return 'trace';
    }

    public function getContent(): array
    {
        // Convert the backtrace into a standardized format
        $frames = array_map(function ($frame) {
            return [
                'file' => $frame['file'] ?? null,
                'line' => $frame['line'] ?? null,
                'class' => $frame['class'] ?? null,
                'method' => $frame['function'] ?? null,
                'args' => $frame['args'] ?? [],
                // Clean the file path if base_path is available
                'relative_file' => function_exists('base_path')
                    ? str_replace(base_path(), '', $frame['file'] ?? '')
                    : ($frame['file'] ?? null),
            ];
        }, $this->frames);

        // If we have a starting frame filter
        if ($this->startFromIndex) {
            $shouldInclude = false;
            $frames = array_filter($frames, function ($frame) use (&$shouldInclude) {
                if ($shouldInclude) {
                    return true;
                }
                if ($this->startFromIndex($frame['line'])) {
                    $shouldInclude = true;

                    return true;
                }

                return false;
            });
        }

        return compact('frames');
    }
}
