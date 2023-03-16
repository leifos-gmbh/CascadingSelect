<?php

declare(strict_types=1);

namespace Leifos\CascadingSelect\DataObjects;

class ColumnsDefinition
{
    protected array $defintion;

    public function __construct(array $definition)
    {
        $this->validate($definition);
        $this->defintion = $definition;
    }

    public function count(): int
    {
        return count($this->defintion);
    }

    /**
     * @return \Generator|string[]
     */
    public function names(): \Generator
    {
        foreach ($this->defintion as $column) {
            yield (string) ($column['name'] ?? '');
        }
    }

    /**
     * @return \Generator|string[]
     */
    public function defaults(): \Generator
    {
        foreach ($this->defintion as $column) {
            $name = (string) ($column['name'] ?? '');
            $default = (string) ($column['default'] ?? '');
            yield $name => $default;
        }
    }

    public function rawSerializedArray(): string
    {
        return serialize($this->defintion);
    }

    public function rawEncodedJSON(): string
    {
        return json_encode($this->defintion);
    }

    /**
     * @throws \ilException
     */
    protected function validate(array $definition): void
    {
        foreach ($definition as $column) {
            if (!isset($column['name'])) {
                throw new \ilException(
                    'Invalid columns definition'
                );
            }
        }
    }
}
