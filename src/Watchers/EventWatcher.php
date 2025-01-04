<?php

namespace Omisteck\Peek\Watchers;

use Illuminate\Support\Facades\Event;
use Omisteck\Peek\Payloads\EventPayload;
use Omisteck\Peek\Peek;

class EventWatcher extends Watcher
{
    public function register(): void
    {
        /** @var \Omisteck\Peek\Peek $peek */
        $peek = app(Peek::class);
        $this->enabled = $peek->settings->send_events_to_peek;

        Event::listen('*', function (string $eventName, array $arguments) {
            if (! $this->enabled()) {
                return;
            }

            $payload = new EventPayload($eventName, $arguments);

            $peek = app(Peek::class)->sendRequest($payload);

            optional($this->peekProxy)->applyCalledMethods($peek);
        });
    }
}
