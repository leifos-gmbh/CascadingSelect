<?php

declare(strict_types=1);

namespace Leifos\CascadingSelect\DataObjects;

/**
 * This class is currently a compromise between working with StdObjects
 * and proper data objects. I implemented it this way to keep backwards
 * compatibility, and to avoid having to reimplement most of the plugin
 * at once.
 * In the future, whoever touches this next should move this class toward
 * being a proper data object, and adapt everything else accordingly.
 */
class CascadingOptions
{
    protected object $json_object;

    public function __construct(object $json_object)
    {
        $this->json_object = $json_object;
    }

    public function name(): string
    {
        return (string) ($this->json_object->name ?? '');
    }

    public function deprecated(): ?bool
    {
        if (!isset($this->json_object->deprecated)) {
            return null;
        }
        return (bool) $this->json_object->deprecated;
    }

    public function deprecatedSince(): string
    {
        return (string) ($this->json_object->deprecatedSince ?? '');
    }

    public function deprecatedUntil(): string
    {
        return (string) ($this->json_object->deprecatedUntil ?? '');
    }

    /**
     * @return \Generator|CascadingOptions[]
     */
    public function options(Factory $factory): \Generator
    {
        foreach ($this->json_object->options ?? [] as $option) {
            yield $factory->cascadingOptions($option);
        }
    }

    public function countOptions(): int
    {
        return count($this->json_object->options ?? []);
    }

    public function raw(): object
    {
        return $this->json_object;
    }
}
