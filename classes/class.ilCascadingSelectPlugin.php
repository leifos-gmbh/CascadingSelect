<?php

declare(strict_types=1);

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

use Leifos\CascadingSelect\Settings;
use Leifos\CascadingSelect\UI\Presenter;
use Leifos\CascadingSelect\DataObjects\Factory;
use Leifos\CascadingSelect\DataObjects\CascadingOptions;
use Leifos\CascadingSelect\DataObjects\ColumnsDefinition;
use Leifos\CascadingSelect\Service\Container;

/**
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilCascadingSelectPlugin extends ilUDFDefinitionPlugin
{
    public const CASCADING_SELECT_NAME = 'CascadingSelect';
    public const CASCADING_SELECT_ID = 'udfcs';

    public const CASCADING_TYPE_ID = 51;

    public const INNER_SEPERATOR = ilCascadingSelectInputGUI::INNER_SEPERATOR;

    private static ?ilCascadingSelectPlugin $instance = null;

    private Container $container;

    protected function init(): void
    {
        $this->container = new Container($this);
    }

    public function getPluginName(): string
    {
        return self::CASCADING_SELECT_NAME;
    }

    /**
     * @return int
     */
    public function getDefinitionType(): int
    {
        return self::CASCADING_TYPE_ID;
    }

    public function getDefinitionTypeName(): string
    {
        return $this->container->language()->txt('cascading_type_name');
    }

    public function getDefinitionUpdateFormTitle(): string
    {
        return $this->container->language()->txt('cascading_type_form_update');
    }

    /**
     * Values are store in udf_text => nothing to do here
     */
    public function lookupUserData(
        array $a_user_ids,
        array $a_field_ids
    ): array {
        return [];
    }

    public static function getInstance(): ilCascadingSelectPlugin
    {
        global $DIC;

        if (self::$instance) {
            return self::$instance;
        }
        self::$instance = new self(
            $DIC->database(),
            $DIC['component.repository'],
            self::CASCADING_SELECT_ID
        );

        return self::$instance;
    }

    public function getFactory(): Factory
    {
        return $this->container->factory();
    }

    public function addDefinitionTypeOptionsToRadioOption(
        ilRadioOption $option,
        int $field_id
    ): void {
        $file_input = new ilFileInputGUI(
            $this->container->language()->txt('definition_values'),
            'cspl_file'
        );
        $file_input->setSuffixes(['xml']);
        $file_input->setRequired(true);
        $option->addSubItem($file_input);

        if (!$field_id) {
            $this->container->logger()->debug('No field id given');
            return;
        }

        $columns = $this->container->settings()->getColSpec($field_id);
        $options = $this->container->settings()->getJSONDeprecated($field_id);
        if (is_null($options)) {
            $this->container->logger()->debug('No cascading options found for id ' . $field_id);
            return;
        }

        $custom = new ilCustomInputGUI();
        $custom->setHtml($this->container->presenter()->render($columns, $options));
        $option->addSubItem($custom);
    }

    public function updateDefinitionFromForm(
        ilPropertyFormGUI $form,
        int $a_definition_id = 0
    ): void {
        /*
         * It does not seem like IRSS can be used here just yet, since we
         * are still working with a legacy ilFileInputGUI.
         */
        if ($_FILES['cspl_file']['tmp_name']) {
            $xml = file_get_contents($_FILES['cspl_file']['tmp_name']);
            $dtd = $this->getDirectory() . '/xml/cascading_select.xsd';

            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            $dom->loadXML($xml);
            if (!$dom->schemaValidate($dtd)) {
                $errors = [];
                foreach (libxml_get_errors() as $error) {
                    $this->container->logger()->warning($error->message);
                    $errors[] = $error->message;
                }

                $this->container->mainTemplate()->setOnScreenMessage(
                    'failure',
                    implode('<br/>', $errors),
                    true
                );
                return;
            }

            $this->container->settings()->setXML($a_definition_id, $xml);

            $xml_obj = simplexml_load_string($xml);

            // parse colspec
            $colspec = $this->transformXmlColSpec($xml_obj);
            $this->container->settings()->setColSpec($a_definition_id, $colspec);

            // create json from xml
            $json = $this->transformXml($xml_obj, true);
            $this->container->settings()->setJSON($a_definition_id, $json);

            // parse deprecated list
            $json_new = $this->transformXml($xml_obj, false);
            $this->container->settings()->setJSONDeprecated($a_definition_id, $json_new);
        }
    }

    public function getFormPropertyForDefinition(
        array $definition,
        bool $a_changeable = true,
        ?string $a_default_value = null
    ): ilFormPropertyGUI {
        $fullmode = $this->container->rbacSystem()->checkAccess("edit_permission", 7);

        $cascading_select = new ilCascadingSelectInputGUI(
            $this->container->factory(),
            $this->container->language(),
            $this->container->templates(),
            $definition['field_name'],
            'udf_' . $definition['field_id']
        );
        $cascading_select->setDisabled(!$a_changeable);

        // check if values are available for field
        $value = $a_default_value;
        if ($id = $this->container->user()->getId()) {
            $udf_data = ilUserDefinedData::lookupData([$id], [$definition['field_id']]);
            if (
                is_array($udf_data[$id] ?? null) &&
                array_key_exists($definition['field_id'], $udf_data[$id])
            ) {
                $value = $udf_data[$id][$definition['field_id']];
            }
        }


        $with_deprecated = $this->container->settings()->getJSON((int) $definition['field_id']);

        $today = new ilDate(time(), IL_CAL_UNIX);
        $without_deprecated = $this->removeDeprecatedOptions($with_deprecated, $today, $fullmode);

        try {
            $json_obj = $this->addValueToJsonIfDeprecated(
                $value,
                $without_deprecated,
                $this->container->settings()->getJSON((int) $definition['field_id'])
            );

            $cascading_select->setCascadingOptions($json_obj);
        } catch (InvalidArgumentException $exception) {
            $this->container->logger()->error($exception->getMessage());
            $definition['required'] = false;
        }

        $coldef = $this->container->settings()->getColSpec((int) $definition['field_id']);
        $cascading_select->setColumnDefinition($coldef);
        $cascading_select->setValue($value);
        $cascading_select->setRequired($definition['required'] ? true : false);

        return $cascading_select;
    }

    protected function removeDeprecatedOptions(
        CascadingOptions $with_deprecated,
        ilDate $today,
        bool $all = false
    ): CascadingOptions {
        $options = [];

        foreach ($with_deprecated->options($this->container->factory()) as $option) {
            $optname = $option->name();

            if (strlen($option->deprecatedSince())) {
                $deprecated_date = new ilDate($option->deprecatedSince(), IL_CAL_DATE);
                if (
                    ilDateTime::_after($today, $deprecated_date, IL_CAL_DAY) ||
                    ilDateTime::_equals($today, $deprecated_date, IL_CAL_DAY)
                ) {
                    if ($all) {
                        $optname = $option->name() . self::INNER_SEPERATOR . " - " . $option->deprecatedSince();
                    } else {
                        continue;
                    }
                }
            }

            if (strlen($option->deprecatedUntil())) {
                $deprecated_date = new ilDate($option->deprecatedUntil(), IL_CAL_DATE);
                if (
                    ilDateTime::_before($today, $deprecated_date, IL_CAL_DAY) ||
                    ilDateTime::_equals($today, $deprecated_date, IL_CAL_DAY)
                ) {
                    if ($all) {
                        $optname = $option->name() . self::INNER_SEPERATOR . " + " . $option->deprecatedUntil();
                    } else {
                        continue;
                    }
                }
            }

            $option_without_deprecated = $this->removeDeprecatedOptions(
                $option,
                $today,
                $all
            )->raw();
            $option_without_deprecated->name = $optname;
            $options[] = $option_without_deprecated;
        }

        $without_deprecated = new StdClass();
        if (!empty($options)) {
            $without_deprecated->options = $options;
        }
        return $this->container->factory()->cascadingOptions($without_deprecated);
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function addValueToJsonIfDeprecated(
        ?string $value,
        CascadingOptions $json_clean,
        ?CascadingOptions $json_deprecated
    ): CascadingOptions {
        $json_clean = $json_clean->raw();
        $json_deprecated = $json_deprecated->raw();

        $single_values = explode(" → ", (string) $value);
        if (!count($single_values)) {
            return $this->container->factory()->cascadingOptions($json_clean);
        }

        if (!is_object($json_deprecated)) {
            throw new InvalidArgumentException("Deprecated JSON value is not set in Database");
        }

        $json_clean->options = $this->addValueToJsonIfDeprecatedForOptions(
            $single_values,
            (array) $json_clean->options,
            (array) $json_deprecated->options
        );
        return $this->container->factory()->cascadingOptions($json_clean);
    }

    protected function addValueToJsonIfDeprecatedForOptions(
        array $values,
        array $options_clean,
        array $options_deprecated
    ): array {
        $current_value = array_shift($values);

        foreach ($options_deprecated as $option) {
            $this->container->logger()->debug('Comparing ' . $current_value . ' with: ' . $option->name);
            if ($option->name == $current_value) {
                $this->container->logger()->debug('Options are equal');
                // add
                $found = null;
                foreach ($options_clean as $cleaned_option) {
                    if ($cleaned_option->name == $current_value) {
                        $this->container->logger()->debug('Found option: ' . $cleaned_option->name);
                        $found = $cleaned_option;
                        break;
                    }
                }
                if (!is_object($found)) {
                    $found = new stdClass();
                    $found->name = $current_value;
                    $found->options = [];
                    $options_clean[] = $found;
                }
                // call subnode
                $found->options = $this->addValueToJsonIfDeprecatedForOptions(
                    $values,
                    (array) ($found->options ?? []),
                    (array) ($option->options ?? [])
                );
            }
        }
        return $options_clean;
    }

    protected function transformXmlColSpec(
        SimpleXMLElement $root
    ): ColumnsDefinition {
        $columns = [];
        foreach ($root->children() as $child) {
            if ($child->getName() != 'colspec') {
                continue;
            }
            $n = 0;
            foreach ($child->children() as $column) {
                $columns[$n]['name'] = (string) $column['name'];
                $columns[$n]['default'] = (string) $column['default'];
                $n++;
            }
        }
        return $this->container->factory()->columnsDefinition($columns);
    }

    protected function transformXml(
        SimpleXMLElement $root,
        bool $a_filter_deprecated = true
    ): CascadingOptions {
        $node = new stdClass();

        $ret = $this->addOptions(0, $root, $a_filter_deprecated);
        if (is_array($ret)) {
            $node->options = $ret;
        }
        return $this->container->factory()->cascadingOptions($node);
    }

    protected function addOptions(
        int $level,
        SimpleXMLElement $element,
        bool $a_filter_deprecated = true,
        bool $a_is_deprecated = false
    ): array {
        ++$level;
        $options = [];

        foreach ($element->children() as $select_option) {
            if ($select_option->getName() != 'option') {
                continue;
            }
            if ($a_filter_deprecated && $select_option['deprecated'] == 1) {
                continue;
            }

            $option = new stdClass();
            $option->name = (string) $select_option['name'];

            $is_deprecated = 0;
            if ($a_is_deprecated || (string) $select_option['deprecated']) {
                $is_deprecated = 1;
            }
            if (!$is_deprecated) {
                if (strlen((string) $select_option['deprecatedSince'])) {
                    $option->deprecatedSince = (string) $select_option['deprecatedSince'];
                }

                if (strlen((string) $select_option['deprecatedUntil'])) {
                    $option->deprecatedUntil = (string) $select_option['deprecatedUntil'];
                }
            }

            $option->deprecated = $is_deprecated;

            $ret = $this->addOptions(
                $level,
                $select_option,
                $a_filter_deprecated,
                (bool) $is_deprecated
            );
            if (count($ret)) {
                $option->options = $ret;
            }
            $options[] = $option;
        }
        return $options;
    }
}
