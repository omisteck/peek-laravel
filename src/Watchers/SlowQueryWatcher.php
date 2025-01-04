<?php

namespace Omisteck\Peek\Watchers;

use Omisteck\Peek\Peek;
use Omisteck\Peek\Settings\Settings;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Events\QueryExecuted;
use Omisteck\Peek\Payloads\ExecutedSlowQueryPayload;

class SlowQueryWatcher extends QueryWatcher
{
    protected $minimumTimeInMs = 500;

    public function register(): void
    {
        $settings = app(Settings::class);

        $this->enabled = $settings->send_slow_queries_to_peek ?? false;
        $this->minimumTimeInMs = $settings->slow_query_threshold_in_ms ?? $this->minimumTimeInMs;

        Event::listen(QueryExecuted::class, function (QueryExecuted $query) {
            if (! $this->enabled()) {
                return;
            }

            $peek = app(Peek::class);

            if ($query->time >= $this->minimumTimeInMs) {
                $payload = new ExecutedSlowQueryPayload($query, $this->minimumTimeInMs);

                $peek->sendRequest($payload);
            }

            optional($this->peekProxy)->applyCalledMethods($peek);
        });
    }

    public function setMinimumTimeInMilliseconds(float $milliseconds): self
    {
        $this->minimumTimeInMs = $milliseconds;

        return $this;
    }
}
