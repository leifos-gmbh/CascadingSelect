<?php

declare(strict_types=1);

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

use Leifos\CascadingSelect\DataObjects\Factory;
use Leifos\CascadingSelect\DataObjects\CascadingOptions;
use Leifos\CascadingSelect\DataObjects\ColumnsDefinition;
use Leifos\CascadingSelect\Service\Language;
use Leifos\CascadingSelect\Service\Templates;

/**
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilCascadingSelectInputGUI extends ilSubEnabledFormPropertyGUI
{
    public const SEPERATOR = ' → ';
    public const INNER_SEPERATOR = ' ↕ ';

    private Factory $factory;
    private Language $language;
    private Templates $templates;

    private ?CascadingOptions $cascading_values = null;
    private ColumnsDefinition $column_definition;
    private ?string $value = null;

    public function __construct(
        Factory $factory,
        Language $language,
        Templates $templates,
        string $title = '',
        string $postvar = ''
    ) {
        $this->factory = $factory;
        $this->language = $language;
        $this->templates = $templates;

        parent::__construct($title, $postvar);
        $this->setType("cascadingSelect");
    }

    public function setColumnDefinition(ColumnsDefinition $coldef): void
    {
        $this->column_definition = $coldef;
    }

    public function getColumnDefinition(): ColumnsDefinition
    {
        return $this->column_definition;
    }

    public function setValue(?string $value): void
    {
        $this->value = $value;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setCascadingOptions(CascadingOptions $cascading_options): void
    {
        $this->cascading_values = $cascading_options;
    }

    public function getCascadingOptions(): ?CascadingOptions
    {
        return $this->cascading_values;
    }

    public function setValueByArray(array $values): void
    {
        $this->setValue($values[$this->getPostVar()]);
        foreach ($this->getSubItems() as $item) {
            $item->setValueByArray($values);
        }
    }

    public function checkInput(): bool
    {
        $confirmed_values = $this->getPostData();

        $levels = $this->parseLevels($this->getCascadingOptions());
        if (
            (count($confirmed_values) < $levels) &&
            ($this->getRequired() || count($confirmed_values) > 0)
        ) {
            $this->setAlert($this->language->txt(
                $this->getRequired() ? 'cascading_required' : 'cascading_incomplete'
            ));
            return false;
        }

        return $this->checkSubItemsInput();
    }

    public function getInput(): string
    {
        $confirmed_values = $this->getPostData();
        return implode(self::SEPERATOR, $confirmed_values);
    }

    protected function getPostData(): array
    {
        $post_req = $this->request->getParsedBody();

        // validate options against options
        $values = explode(
            self::SEPERATOR,
            ilUtil::stripSlashes((string) $post_req[$this->getPostVar()])
        );

        $options = $this->getCascadingOptions();

        $confirmed_values = [];
        foreach ($values as $value) {
            foreach ($options->options($this->factory) as $option) {
                // clean out everything from first INNER_SEPERATOR
                if ($option->name() == trim($value)) {
                    if (strpos($option->name(), self::INNER_SEPERATOR)) {
                        $confirmed = trim(explode(self::INNER_SEPERATOR, $option->name()) [0]);
                    } else {
                        $confirmed = trim($option->name());
                    }
                    $confirmed_values[] = trim($confirmed);
                    $options = $option;
                    break;
                }
            }
        }
        // set default if no data is given for a level (if a default is set)
        $level = 0;
        foreach ($this->getColumnDefinition()->defaults() as $default) {
            if (
                !array_key_exists($level, $confirmed_values) &&
                !array_key_exists($level, $values) &&
                $default
            ) {
                $confirmed_values[$level] = $default;
            }
            $level++;
        }

        return $confirmed_values;
    }

    public function insert(ilTemplate $tpl): void
    {
        $tpl->setCurrentBlock("prop_generic");
        $tpl->setVariable("PROP_GENERIC", $this->render());
        $tpl->parseCurrentBlock();
    }

    public function render(string $mode = ''): string
    {
        $template = $this->templates->get('tpl.prop_cascading_select.html');
        $js_template = $this->templates->get('tpl.prop_cascading_select.js');

        $num_levels = $this->parseLevels($this->getCascadingOptions());

        $js_template->setVariable('NUM_LEVELS', $num_levels);
        $js_template->setVariable('JSON_DEF', json_encode($this->getCascadingOptions()->raw()));
        $js_template->setVariable('JSON_COL', $this->getColumnDefinition()->rawEncodedJSON());
        $js_template->setVariable('TXT_SEL', $this->lng->txt('please_select'));
        $js_template->setVariable('POST_VAR', $this->getPostVar());

        $template->setVariable('VALUE', $this->getValue());
        $template->setVariable('UNIQUE_ID_SEL', 'udf_' . $this->getFieldId() . '_select');
        $template->setVariable('POST_VAR', $this->getPostVar());

        // column titles
        if ($this->getColumnDefinition()->count()) {
            foreach ($this->getColumnDefinition()->names() as $name) {
                $template->setCurrentBlock('level_text');
                $template->setVariable('TXT_COL', $name);
                $template->parseCurrentBlock();
            }
        }

        for ($i = 0; $i < $num_levels; $i++) {
            $template->setCurrentBlock('level_options');
            $template->setVariable('TXT_LEVEL_OPTION', $this->lng->txt('please_select'));
            $template->setVariable('VAL_LEVEL_OPTION', '');

            $template->setCurrentBlock('level_select');
            if ($this->getDisabled()) {
                $template->setVariable('DISABLED', 'disabled="disabled"');
            }
            $template->setVariable('ID', $this->getFieldId() . '_' . $i);
            $template->parseCurrentBlock();
        }

        $this->global_tpl->addOnLoadCode($js_template->get());
        return $template->get();
    }

    protected function parseLevels(?CascadingOptions $cascading_options): int
    {
        static $depth = 0;
        static $maxdepth = 0;

        $depth++;
        if ($cascading_options->countOptions() === 0) {
            $depth--;
            return $maxdepth;
        }

        if ($depth > $maxdepth) {
            $maxdepth = $depth;
        }

        foreach ($cascading_options->options($this->factory) as $option) {
            $this->parseLevels($option);
        }
        $depth--;
        return $maxdepth;
    }
}
