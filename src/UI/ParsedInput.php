<?php

declare(strict_types=1);

namespace Leifos\CascadingSelect\UI;

class ParsedInput
{
    /**
     * @param string[] $clean_value
     */
    public function __construct(
        protected array $clean_value,
        protected string $serialized_value,
        protected bool $is_complete
    ) {
    }

    /**
     * @return string[]
     */
    public function cleanValue(): array
    {
        return $this->clean_value;
    }

    public function serializedValue(): string
    {
        return $this->serialized_value;
    }

    public function isEmpty(): bool
    {
        return $this->clean_value === [];
    }

    public function isComplete(): bool
    {
        return $this->is_complete;
    }
}
