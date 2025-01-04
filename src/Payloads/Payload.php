<?php

namespace Omisteck\Peek\Payloads;

use Omisteck\Peek\Origin\DefaultOriginFactory;
use Omisteck\Peek\Origin\Origin;

abstract class Payload
{
    /** @var string */
    public static $originFactoryClass = DefaultOriginFactory::class;

    abstract public function getType(): string;

    /** @var string|null */
    public $remotePath = null;

    /** @var string|null */
    public $localPath = null;

    /** @var string|null */
    public $status = null;


    public function replaceRemotePathWithLocalPath(string $filePath): string
    {
        if (is_null($this->remotePath) || is_null($this->localPath)) {
            return $filePath;
        }

        $pattern = '~^' . preg_quote($this->remotePath, '~') . '~';

        return preg_replace($pattern, $this->localPath, $filePath);
    }

    public function getContent(): array
    {
        return [];
    }

    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'content' => $this->getContent(),
            'origin' => $this->getOrigin()->toArray(),
            'status' => $this->status,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    protected function getOrigin(): Origin
    {
        /** @var \Omisteck\Peek\Origin\OriginFactory $originFactory */
        $originFactory = new self::$originFactoryClass;

        $origin = $originFactory->getOrigin();

        $origin->file = $this->replaceRemotePathWithLocalPath($origin->file);

        return $origin;
    }
}
