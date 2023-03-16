<?php

declare(strict_types=1);

namespace Leifos\CascadingSelect\DataObjects;

class Factory
{
    public function columnsDefinition(array $definition): ColumnsDefinition
    {
        return new ColumnsDefinition($definition);
    }

    public function cascadingOptions(object $json_object): CascadingOptions
    {
        return new CascadingOptions($json_object);
    }
}
