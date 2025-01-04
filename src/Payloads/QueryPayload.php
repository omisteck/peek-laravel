<?php

namespace Omisteck\Peek\Payloads;

use Illuminate\Database\Query\Builder;

class QueryPayload extends Payload
{
    /** @var \Illuminate\Database\Query\Builder */
    protected $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function getType(): string
    {
        return 'executed_query';
    }

    public function getContent(): array
    {
        if (method_exists($this->query, 'toRawSql')) {
            return [
                'sql' => $this->query->toRawSql(),
            ];
        }

        return [
            'sql' => $this->query->toSql(),
            'bindings' => $this->query->getBindings(),
        ];
    }
}
