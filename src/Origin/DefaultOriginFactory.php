<?php

namespace Omisteck\Peek\Origin;

use Omisteck\Peek\BasePeek;

class DefaultOriginFactory implements OriginFactory
{
    public function getOrigin(): Origin
    {
        $frame = $this->getFrame();

        return new Origin(
            $frame ? $frame['file'] : null,
            $frame ? $frame['line'] : null,
            Hostname::get()
        );
    }

    protected function getFrame(): ?array
    {
        $frames = $this->getAllFrames();
        $indexOfPeek = $this->getIndexOfPeekFrame($frames);

        // Get the frame where peek() was actually called
        $callerIndex = $this->findPeekCaller($frames, $indexOfPeek);

        return $frames[$callerIndex] ?? null;
    }

    protected function getAllFrames(): array
    {
        // Get debug backtrace with more details to find the peek() call
        $frames = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        // Don't reverse the frames - we want to work with the natural call order
        return $frames;
    }

    protected function getIndexOfPeekFrame(array $frames): ?int
    {
        return $this->search(function (array $frame) {
            // Look specifically for the peek() function call
            if (isset($frame['function']) && $frame['function'] === 'peek') {
                return true;
            }

            // Check if the frame is from the Peek class
            if (isset($frame['class']) && $frame['class'] === BasePeek::class) {
                return true;
            }

            return false;
        }, $frames);
    }

    protected function findPeekCaller(array $frames, ?int $peekIndex): ?int
    {
        if ($peekIndex === null) {
            return null;
        }

        // Look at frames after the peek() call to find the actual caller
        for ($i = $peekIndex + 1; $i < count($frames); $i++) {
            if (!isset($frames[$i]['file'])) {
                continue;
            }

            $file = $frames[$i]['file'];

            // Skip vendor files
            if (str_contains($file, '/vendor/')) {
                continue;
            }

            // Skip framework files
            if (str_contains($file, '/framework/')) {
                continue;
            }

            // Look for app files, controllers, or routes
            if (
                str_contains($file, '/app/') ||
                str_contains($file, '/app/Http/Controllers/') ||
                str_contains($file, '/routes/') ||
                (isset($frames[$i]['class']) && str_contains($frames[$i]['class'], 'Controller'))
            ) {
                return $i;
            }
        }

        // If no suitable frame found, return the frame right after peek
        return $peekIndex + 1;
    }

    protected function startsWith(string $haystack, string $needle): bool
    {
        return strpos($haystack, $needle) === 0;
    }

    protected function isUsingGlobalPeek(array $frames): bool
    {
        return $this->getIndexOfGlobalPeekFrame($frames) !== null;
    }

    protected function getIndexOfGlobalPeekFrame(array $frames): ?int
    {
        return $this->search(function (array $frame) {
            if (! isset($frame['file'])) {
                return false;
            }

            // Check if the frame is from a PHAR file
            if (! $this->startsWith($frame['file'], 'phar:')) {
                return false;
            }

            // Check if the frame is from the global peek installation
            if (strpos($frame['file'], 'global-peek/peek-phars') === false) {
                return false;
            }

            return true;
        }, $frames);
    }

    protected function search(callable $callable, array $items): ?int
    {
        foreach ($items as $key => $item) {
            if ($callable($item, $key)) {
                return $key;
            }
        }

        return null;
    }
}
