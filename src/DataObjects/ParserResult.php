<?php

declare(strict_types=1);

namespace Leifos\CascadingSelect\DataObjects;

class ParserResult
{
    public function __construct(
        protected Definition $definition,
        protected array $errors
    ) {
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    public function definition(): Definition
    {
        return $this->definition;
    }

    /**
     * @return string[]
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
