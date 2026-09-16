<?php

declare(strict_types=1);

namespace Leifos\CascadingSelect\DataObjects;

use DateTimeImmutable;
use JsonSerializable;

class Option implements JsonSerializable
{
    /**
     * @param Option[] $child_options
     */
    public function __construct(
        protected string $name,
        protected bool $deprecated,
        protected ?DateTimeImmutable $deprecated_since,
        protected ?DateTimeImmutable $deprecated_until,
        protected array $child_options
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function deprecated(): ?bool
    {
        return $this->deprecated;
    }

    public function deprecatedSince(): ?DateTimeImmutable
    {
        return $this->deprecated_since;
    }

    public function deprecatedUntil(): ?DateTimeImmutable
    {
        return $this->deprecated_until;
    }

    /**
     * @return Option[]
     */
    public function childOptions(): array
    {
        return $this->child_options;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->name,
            'deprecated' => $this->deprecated,
            'deprecated_since' => $this->deprecated_since?->format('Y-m-d') ?? '',
            'deprecated_until' => $this->deprecated_until?->format('Y-m-d') ?? '',
            'child_options' => $this->child_options, JSON_THROW_ON_ERROR
        ];
    }
}
