<?php

namespace Omisteck\Peek\Watchers;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Facades\Event;
use Omisteck\Peek\Payloads\JobEventPayload;
use Omisteck\Peek\Peek;
use Omisteck\Peek\Settings\Settings;

class JobWatcher extends Watcher
{
    public function register(): void
    {
        $settings = app(Settings::class);

        $this->enabled = $settings->send_jobs_to_peek;

        Event::listen([
            JobQueued::class,
            JobProcessing::class,
            JobProcessed::class,
            JobFailed::class,
        ], function (object $event) {
            if (! $this->enabled()) {
                return;
            }

            $payload = new JobEventPayload($event);

            $peek = app(Peek::class)->sendRequest($payload);

            optional($this->peekProxy)->applyCalledMethods($peek);
        });
    }
}
