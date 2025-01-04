<?php

namespace Omisteck\Peek\Payloads;

use Throwable;

class ExceptionPayload extends Payload
{
    /** @var \Throwable */
    protected $exception;

    /** @var array */
    protected $meta = [];

    public function __construct(Throwable $exception, array $meta = [])
    {
        $this->exception = $exception;

        $this->meta = $meta;
    }

    public function getType(): string
    {
        return 'exception';
    }

    public function getContent(): array
    {
        return [
            'class' => get_class($this->exception),
            'message' => $this->exception->getMessage(),
            'frames' => $this->getFrames(),
            'meta' => $this->meta,
        ];
    }

    protected function getFrames(): array
    {
        $trace = $this->exception->getTrace();
        $frames = array_reverse($trace);

        return array_map(function (array $frame) {
            $file = $frame['file'] ?? '[internal]';
            $line = $frame['line'] ?? 0;

            return [
                'file_name' => $this->replaceRemotePathWithLocalPath($file),
                'line_number' => $line,
                'class' => $frame['class'] ?? null,
                'method' => $frame['function'] ?? null,
                'vendor_frame' => $this->isVendorFrame($file),
                'snippet' => $this->getCodeSnippet($file, $line),
            ];
        }, $frames);
    }

    protected function isVendorFrame(string $file): bool
    {
        return strpos($file, '/vendor/') !== false;
    }

    protected function getCodeSnippet(string $file, int $lineNumber): array
    {
        if (! file_exists($file) || $file === '[internal]') {
            return [];
        }

        $lines = file($file);
        $snippet = [];
        $range = 12; // Same as original

        $start = max($lineNumber - $range, 0);
        $end = min($lineNumber + $range, count($lines));

        for ($i = $start; $i < $end; $i++) {
            $snippet[$i + 1] = rtrim($lines[$i] ?? '');
        }

        return [
            'line_number' => $lineNumber,
            'snippet' => $snippet,
        ];
    }
}
