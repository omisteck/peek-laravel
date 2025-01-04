<?php

namespace Omisteck\Peek\Watchers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Omisteck\Peek\Payloads\ExecutedQueryPayload;
use Omisteck\Peek\Peek;
use Omisteck\Peek\Settings\Settings;

class QueryWatcher extends Watcher
{
    /** @var QueryExecuted[] */
    protected $executedQueries = [];

    /** @var bool */
    protected $keepExecutedQueries = false;

    /** @var bool */
    protected $sendIndividualQueries = true;

    public function register(): void
    {
        $settings = app(Settings::class);

        $this->enabled = $settings->send_queries_to_peek;

        Event::listen(QueryExecuted::class, function (QueryExecuted $query) {
            if (! $this->enabled()) {
                return;
            }

            if ($this->keepExecutedQueries) {
                $this->executedQueries[] = $query;
            }

            if (! $this->sendIndividualQueries) {
                return;
            }

            $payload = new ExecutedQueryPayload($query);

            $peek = app(Peek::class)->sendRequest($payload);

            optional($this->peekProxy)->applyCalledMethods($peek);
        });
    }

    public function enable(): Watcher
    {
        if (app()->bound('db')) {
            collect(DB::getConnections())->each(function ($connection) {
                $connection->enableQueryLog();
            });
        }

        parent::enable();

        return $this;
    }

    public function keepExecutedQueries(): self
    {
        $this->keepExecutedQueries = true;

        return $this;
    }

    public function getExecutedQueries(): array
    {
        return $this->executedQueries;
    }

    public function sendIndividualQueries(): self
    {
        $this->sendIndividualQueries = true;

        return $this;
    }

    public function doNotSendIndividualQueries(): self
    {
        $this->sendIndividualQueries = false;

        return $this;
    }

    public function stopKeepingAndClearExecutedQueries(): self
    {
        $this->keepExecutedQueries = false;

        $this->executedQueries = [];

        return $this;
    }

    public function disable(): Watcher
    {
        DB::disableQueryLog();

        parent::disable();

        return $this;
    }
}
