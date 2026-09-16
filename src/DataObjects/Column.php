<?php

declare(strict_types=1);

namespace Leifos\CascadingSelect\DataObjects;

use JsonSerializable;

class Column implements JsonSerializable
{
    public function __construct(
        protected string $name,
        protected string $default
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function default(): string
    {
        return $this->default;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->name,
            'default' => $this->default
        ];
    }
}
