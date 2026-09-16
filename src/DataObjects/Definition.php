<?php

declare(strict_types=1);

namespace Leifos\CascadingSelect\DataObjects;

use JsonSerializable;

class Definition implements JsonSerializable
{
    /**
     * @param Column[] $columns
     * @param Option[] $options
     */
    public function __construct(
        protected array $columns,
        protected array $options
    ) {
    }

    /**
     * @return Column[]
     */
    public function columns(): array
    {
        return $this->columns;
    }

    /**
     * @return Option[]
     */
    public function options(): array
    {
        return $this->options;
    }

    public function serialize(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR);
    }

    public function jsonSerialize(): mixed
    {
        return [
            'columns' => $this->columns,
            'options' => $this->options, JSON_THROW_ON_ERROR
        ];
    }
}
