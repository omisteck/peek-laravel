<?php

namespace Omisteck\Peek\Watchers;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Omisteck\Peek\Payloads\CachePayload;
use Omisteck\Peek\Peek;
use Omisteck\Peek\Settings\Settings;

class CacheWatcher extends Watcher
{
    public function register(): void
    {
        $settings = app(Settings::class);

        $this->enabled = $settings->send_cache_to_peek;

        app('events')->listen(CacheHit::class, function (CacheHit $event) {
            if (! $this->enabled()) {
                return;
            }

            $payload = new CachePayload('Hit', $event->key, $event->tags, $event->value);

            $peek = $this->peek()->sendRequest($payload);

            optional($this->peekProxy)->applyCalledMethods($peek);
        });

        app('events')->listen(CacheMissed::class, function (CacheMissed $event) {
            if (! $this->enabled()) {
                return;
            }

            $payload = new CachePayload('Missed', $event->key, $event->tags);

            $this->peek()->sendRequest($payload);
        });

        app('events')->listen(KeyWritten::class, function (KeyWritten $event) {
            if (! $this->enabled()) {
                return;
            }

            $payload = new CachePayload(
                'Key written',
                $event->key,
                $event->tags,
                $event->value,
                $this->formatExpiration($event),
            );

            $this->peek()->sendRequest($payload);
        });

        app('events')->listen(KeyForgotten::class, function (KeyForgotten $event) {
            if (! $this->enabled()) {
                return;
            }

            $payload = new CachePayload(
                'Key forgotten',
                $event->key,
                $event->tags
            );

            $this->peek()->sendRequest($payload);
        });
    }

    protected function formatExpiration(KeyWritten $event): ?int
    {
        return $event->seconds;
    }

    public function peek(): Peek
    {
        return app(Peek::class);
    }
}
