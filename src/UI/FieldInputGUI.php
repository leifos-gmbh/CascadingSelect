<?php

declare(strict_types=1);

namespace Leifos\CascadingSelect\UI;

use Leifos\CascadingSelect\DataObjects\Option;
use Leifos\CascadingSelect\Service\Language;
use Leifos\CascadingSelect\Service\Templates;
use ilSubEnabledFormPropertyGUI;
use ilTemplate;
use Leifos\CascadingSelect\DataObjects\Definition;
use Leifos\CascadingSelect\Service\Access;
use DateTimeImmutable;

/**
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 *
 * TODO replace this by a tree select, when user profile is moved to KS.
 */
class FieldInputGUI extends ilSubEnabledFormPropertyGUI
{
    public const string SEPARATOR = ' → ';
    public const string INNER_SEPARATOR = '  ↕  ';

    private ?string $value = null;

    public function __construct(
        protected Access $access,
        protected Language $language,
        protected Templates $templates,
        protected InputParser $input_parser,
        protected Definition $definition,
        string $title
    ) {
        parent::__construct($title, 'dummy_postvar');
        $this->setType('cascadingSelect');
    }

    public function setValue(?string $value): void
    {
        $this->value = $value;
    }

    public function getValue(): ?string
    {
        return $this->value;
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
        $confirmed_values = $this->getParsedDataFromPost();
        if (
            !$confirmed_values->isComplete() &&
            ($this->getRequired() || !$confirmed_values->isEmpty())
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
        $confirmed_values = $this->getParsedDataFromPost();
        return $confirmed_values->serializedValue();
    }

    protected function getParsedDataFromPost(): ParsedInput
    {
        $post_req = $this->request->getParsedBody();
        $raw = (string) $post_req[$this->getPostVar()];

        return $this->input_parser->parse($raw, $this->definition);
    }

    public function insert(ilTemplate $tpl): void
    {
        $tpl->setCurrentBlock("prop_generic");
        $tpl->setVariable("PROP_GENERIC", $this->render());
        $tpl->parseCurrentBlock();
    }

    public function render(string $mode = ''): string
    {
        $parsed_value = $this->input_parser->parse((string) $this->getValue(), $this->definition);

        $template = $this->templates->get('tpl.prop_cascading_select.html');
        $js_template = $this->templates->get('tpl.prop_cascading_select.js');

        $clean_options = $this->getEncodableCleanOptions(
            $this->definition->options(),
            $parsed_value->cleanValue(),
            $this->access->canCurrentUserChangeProfileSettings()
        );
        $num_levels = $this->parseLevels($clean_options);

        $js_template->setVariable('NUM_LEVELS', $num_levels);
        $js_template->setVariable('JSON_DEF', json_encode($clean_options));
        $js_template->setVariable('JSON_COL', json_encode($this->definition->columns()));
        $js_template->setVariable('TXT_SEL', $this->lng->txt('please_select'));
        $js_template->setVariable('POST_VAR', $this->getPostVar());
        $js_template->setVariable('NOOPT', $this->language->txt('cascading_no_opts'));

        $template->setVariable('VALUE', $parsed_value->serializedValue());
        $template->setVariable('UNIQUE_ID_SEL', 'udf_' . $this->getFieldId() . '_select');
        $template->setVariable('POST_VAR', $this->getPostVar());

        // column titles
        foreach ($this->definition->columns() as $column) {
            $template->setCurrentBlock('level_text');
            $template->setVariable('TXT_COL', $column->name());
            $template->parseCurrentBlock();
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

    protected function parseLevels(array $options): int
    {
        static $depth = 0;
        static $maxdepth = 0;

        $depth++;
        if ($options === []) {
            $depth--;
            return $maxdepth;
        }

        if ($depth > $maxdepth) {
            $maxdepth = $depth;
        }

        foreach ($options as $option) {
            $this->parseLevels($option->options);
        }
        $depth--;
        return $maxdepth;
    }

    /**
     * Deprecated options are removed, but not if they are currently selected.
     * For Compatibility to the old js implementation.
     * @param Option[] $options
     * @param string[] $value
     */
    protected function getEncodableCleanOptions(
        array $options,
        array $value,
        bool $keep_deprecated,
        int $depth = 0
    ): array {
        $clean_options = [];

        if ($depth > 20) {
            return $clean_options;
        }

        $currently_selected_option = array_shift($value);
        foreach ($options as $option) {
            $name = $option->name();
            if (($deprecation = $this->getOptionDeprecationNotice($option)) !== null) {
                if (!$keep_deprecated && $name !== $currently_selected_option) {
                    continue;
                }
                if ($keep_deprecated) {
                    $name .= self::INNER_SEPARATOR . $deprecation;
                }
            }

            $result_option = new \stdClass();
            $result_option->name = $name;
            $result_option->options = $this->getEncodableCleanOptions(
                $option->childOptions(),
                $value,
                $keep_deprecated,
                $depth + 1
            );
            $clean_options[] = $result_option;
        }
        return $clean_options;
    }

    /**
     * Returns null if option is not deprecated, else suffix for label.
     */
    protected function getOptionDeprecationNotice(Option $option): ?string
    {
        if ($option->deprecated()) {
            return 'deprecated';
        }
        $now = new DateTimeImmutable();
        if ($option->deprecatedUntil() !== null && $now <= $option->deprecatedUntil()) {
            return ' + ' . $option->deprecatedUntil()->format('Y-m-d');
        }
        if ($option->deprecatedSince() !== null && $now >= $option->deprecatedSince()) {
            return ' - ' . $option->deprecatedSince()->format('Y-m-d');
        }
        return null;
    }
}
