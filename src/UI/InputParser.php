<?php

declare(strict_types=1);

namespace Leifos\CascadingSelect\UI;

use Leifos\CascadingSelect\DataObjects\Definition;
use ilUtil;

class InputParser
{
    // TODO when moving the input to tree select, append default values here as needed, and not in the input itself
    public function parse(string $raw_value, Definition $definition): ParsedInput
    {
        // validate options against values
        $values = explode(
            FieldInputGUI::SEPARATOR,
            ilUtil::stripSlashes($raw_value)
        );

        $current_options = $definition->options();
        $columns = $definition->columns();

        $confirmed_values = [];
        foreach ($values as $value) {
            $current_column = array_shift($columns);
            $current_default = trim($current_column?->default() ?? '');
            // TODO when moving the input to tree select, don't have the INNER_SEPARATOR and what follows it in the value, just in the label
            $value = trim(explode(FieldInputGUI::INNER_SEPARATOR, $value)[0]);
            if ($current_default !== '' && $current_default === $value) {
                $confirmed_values[] = $value;
                continue;
            }

            foreach ($current_options as $option) {
                if ($option->name() === $value) {
                    $confirmed = trim(explode(FieldInputGUI::INNER_SEPARATOR, $value)[0]);
                    $confirmed_values[] = $value;
                    $current_options = $option->childOptions();
                    break;
                }
            }
        }

        return new ParsedInput(
            $confirmed_values,
            implode(FieldInputGUI::SEPARATOR, $confirmed_values),
            $current_options === []
        );
    }
}
