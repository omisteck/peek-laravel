<?php

namespace Omisteck\Peek\Payloads;

use Illuminate\Database\Events\QueryExecuted;

class ExecutedSlowQueryPayload extends Payload
{
    /** @var \Illuminate\Database\Events\QueryExecuted */
    protected $query;

    protected $minimumTimeInMs;

    public function __construct(QueryExecuted $query, int $minimumTimeInMs)
    {
        $this->query = $query;
        $this->minimumTimeInMs = $minimumTimeInMs;
    }

    public function getType(): string
    {
        return 'executed_slow_query';
    }

    public function getContent(): array
    {
        $grammar = $this->query->connection->getQueryGrammar();

        $properties = method_exists($grammar, 'substituteBindingsIntoRawSql') ? [
            'sql' => $grammar->substituteBindingsIntoRawSql(
                $this->query->sql,
                $this->query->connection->prepareBindings($this->query->bindings)
            ),
        ] : [
            'sql' => $this->query->sql,
            'bindings' => $this->query->bindings,
        ];

        if ($this->hasAllProperties()) {
            $properties = array_merge($properties, [
                'connection_name' => $this->query->connectionName,
                'time' => $this->query->time,
                'threshold' => $this->minimumTimeInMs,
            ]);
        }

        return $properties;
    }

    protected function hasAllProperties(): bool
    {
        return ! is_null($this->query->time);
    }
}
