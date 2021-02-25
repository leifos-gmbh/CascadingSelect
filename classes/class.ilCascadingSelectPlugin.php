<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/User/classes/class.ilUDFDefinitionPlugin.php';

/**
 * Description of class class 
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de> 
 *
 */
class ilCascadingSelectPlugin extends ilUDFDefinitionPlugin
{
	const CASCADING_SELECT_NAME = 'CascadingSelect';
	
	const CASCADING_TYPE_ID = 51;
	
	private static $instance = null; 
	
	/**
	 * Get plugin name
	 * @return string
	 */
	public function getPluginName() : string
	{
		return self::CASCADING_SELECT_NAME;
	}
	
	/**
	 * @return int
	 */
	public function getDefinitionType() : int
	{
		return self::CASCADING_TYPE_ID;
	}

	public function getDefinitionTypeName() : string
	{
		return $this->txt('cascading_type_name');
	}
	
	public function getDefinitionUpdateFormTitle() : string
	{
		return $this->txt('cascading_type_form_update');
	}
	
	/**
	 * Lookup user data
	 * Values are store in udf_text => nothing todo here
	 * @param array $a_user_ids
	 * @param array $a_field_ids
	 * @return array
	 */
	public function lookupUserData($a_user_ids, $a_field_ids)
	{
		return array();
	}
	
	
	/**
	 * Get singleton instance
     *
	 * @return \ilCascadingSelectPlugin
	 */
	public static function getInstance() : ilCascadingSelectPlugin
	{
		if(self::$instance)
		{
			return self::$instance;
		}
		include_once './Services/Component/classes/class.ilPluginAdmin.php';
		return self::$instance = ilPluginAdmin::getPluginObject(
			self::UDF_C_TYPE,
			self::UDF_C_NAME,
			self::UDF_SLOT_ID,
			self::CASCADING_SELECT_NAME
		);
	}
	
	
	
	/**
	 * Init auto load
	 */
	protected function init()
	{
		$this->initAutoLoad();
	}
		
	/**
	 * Init auto loader
	 * @return void
	 */
	protected function initAutoLoad()
	{
		spl_autoload_register(
			array($this,'autoLoad')
		);
	}

	/**
	 * Auto load implementation
	 *
	 * @param string class name
	 */
	private final function autoLoad(string $a_classname)
	{
		$class_file = $this->getClassesDirectory().'/class.'.$a_classname.'.php';
		if(file_exists($class_file))
		{
            include_once($class_file);
		}
	}

	/**
	 * @param \ilRadioOption $option
	 * @param int $field_id
	 */
	public function addDefinitionTypeOptionsToRadioOption(\ilRadioOption $option, $field_id)
	{
		$file_input = new ilFileInputGUI($this->txt('definition_values'),'cspl_file');
		$file_input->setSuffixes(['xml','json']);
        $file_input->setRequired('true');
		$option->addSubItem($file_input);
		
		if(!$field_id)
		{
			ilLoggerFactory::getLogger('udfcs')->debug('No field id given');
			return true;
		}
		
		$settings = ilCascadingSelectSettings::getInstance();
		$xml_string = $settings->get('xml_'.$field_id);
		if(!strlen($xml_string))
		{
			ilLoggerFactory::getLogger('udfcs')->debug('No xml string found for id ' . $field_id);
			return true;
		}
		
		$xml = simplexml_load_string($xml_string);
		$template = $this->getTemplate('tpl.options_info.html', true, true);
		
		
		$custom = new ilCustomInputGUI();
		$custom->setHtml(htmlentities($xml_string));
		$option->addSubItem($custom);
	}
	
	/**
	 * Update from form
     *
     * @param ilPropertyFormGUI $form
     * @param int $a_field_id
	 */
	public function updateDefinitionFromForm(ilPropertyFormGUI $form, $a_field_id = 0)
	{
		$settings = ilCascadingSelectSettings::getInstance();
		
		if($_FILES['cspl_file']['tmp_name'])
		{
			$xml = file_get_contents($_FILES['cspl_file']['tmp_name']);
			$dtd = $this->getDirectory().'/xml/cascading_select.xsd';
			
			libxml_use_internal_errors(true);
			$dom = new DOMDocument();
			$dom->loadXML($xml);
			if(!$dom->schemaValidate($dtd))
			{
				ilLoggerFactory::getLogger('udfcs')->notice($xml);
				$errors = [];
				foreach(libxml_get_errors() as $error)
				{
					ilLoggerFactory::getLogger('udfcs')->warning($error->message);
					$errors[] = $error->message;
				}
				ilUtil::sendFailure(implode('<br/>', $errors),true);
				return false;
			}
			
			$settings->set('xml_'.$a_field_id, $xml);

			$xml_obj = simplexml_load_string($xml);

			// create json from xml
			$json = $this->transformXml($xml_obj,true);
			$settings->set('json_'.$a_field_id, $json);
			ilLoggerFactory::getLogger('udfcs')->dump($json);
			
			// parse deprecated list
			$json_new = $this->transformXml($xml_obj,false);
			$settings->set('json_deprecated_'.$a_field_id, $json_new);
			ilLoggerFactory::getLogger('udfcs')->dump($json_new);
			
			// parse colspec
			$colspec = $this->transformXmlColSpec($xml_obj);
			$settings->set('colspec_'.$a_field_id,  serialize($colspec));
			ilLoggerFactory::getLogger('udfcs')->dump($colspec);
		}
	}
	
	/**
	 * Get form property for definition
	 * Context: edit user; registration; edit user profile 
	 * @return ilFormPropertyGUI
	 */
	public function getFormPropertyForDefinition($definition, $a_changeable = true, $a_default_value = null) : ilFormPropertyGUI
	{
	    global $DIC;

		$cascading_select = new ilCascadingSelectInputGUI(
			$definition['field_name'],
			'udf_'.$definition['field_id']
		);
		$cascading_select->setDisabled(!$a_changeable);
		
		$settings = ilCascadingSelectSettings::getInstance();

		// check if values are available for field
		$usr_id = $DIC->user()->getId();
		$value = $a_default_value;
		if($usr_id)
		{
			include_once './Services/User/classes/class.ilUserDefinedData.php';
			$udf_data = ilUserDefinedData::lookupData([$usr_id], [$definition['field_id']]);
			if(is_array($udf_data[$usr_id]) && array_key_exists($definition['field_id'], $udf_data[$usr_id]))
			{
				$value = $udf_data[$usr_id][$definition['field_id']];
			}
		}
		
		$today = new ilDate(time(),IL_CAL_UNIX);
		$with_deprecated = json_decode($settings->get('json_'.$definition['field_id']));
		$without_deprecated_options = $this->removeDeprecatedOptions((array) $with_deprecated->options, $today);
		$without_deprecated = new stdClass();
		$without_deprecated->options = $without_deprecated_options;

        try {
            $json_obj = $this->addValueToJsonIfDeprecated(
                $value,
                $without_deprecated,
                json_decode($settings->get('json_deprecated_'.$definition['field_id']))
            );

            $cascading_select->setCascadingOptions($json_obj);
        } catch (InvalidArgumentException $exception) {
            ilLoggerFactory::getLogger('udfcs')->error($exception->getMessage());
            $definition['required'] = false;
        }
		
		$coldef = $settings->get('colspec_'.$definition['field_id'],  serialize(array()));
		$cascading_select->setColumnDefinition(unserialize($coldef));
		$cascading_select->setValue($value);
		$cascading_select->setRequired($definition['required'] ? true : false);
		
		return $cascading_select;
	}

    /**
     * Remove deprecated options
     * @param array $a_with_deprecated
     * @param ilDate $today
     *
     * @return array
     */
    protected function removeDeprecatedOptions(array $a_with_deprecated, ilDate $today) : array
    {
        $options = [];

        foreach((array) $a_with_deprecated as $idx => $option)
        {
            if(strlen($option->deprecatedSince))
            {
                $deprecated_date = new ilDate($option->deprecatedSince, IL_CAL_DATE);
                if(
                    ilDateTime::_after($today, $deprecated_date, IL_CAL_DAY) ||
                    ilDateTime::_equals($today, $deprecated_date, IL_CAL_DAY)
                )
                {
                    continue;
                }
            }

            if(strlen($option->deprecatedUntil))
            {
                $deprecated_date = new ilDate($option->deprecatedUntil, IL_CAL_DATE);
                if(
                    ilDateTime::_before($today, $deprecated_date, IL_CAL_DAY) ||
                    ilDateTime::_equals($today, $deprecated_date, IL_CAL_DAY)
                )
                {
                    continue;
                }
            }

            $option_without_deprecated = new stdClass();
            $option_without_deprecated->name = $option->name;

            if(count((array) $option->options))
            {
                $option_without_deprecated->options = $this->removeDeprecatedOptions($option->options, $today);
            }

            $options[] = $option_without_deprecated;
        }
        return $options;
    }
	
	/**
	 * Add value to json if deprecated
	 * @param string|null $value
	 * @param object $json_clean
	 * @param object|null $json_deprecated
     *
	 * @return
     * @throws InvalidArgumentException
	 */
	protected function addValueToJsonIfDeprecated( ?string $value, object $json_clean, ?object $json_deprecated)
	{
		$single_values = explode(" → ", $value);
		if(!count($single_values))
		{
			return $json_clean;
		}

        if (!is_object($json_deprecated)) {
            throw new InvalidArgumentException("Deprecated JSON value is not set in Database");
        }

		$json_clean->options = $this->addValueToJsonIfDeprecatedForOptions(
			$single_values, 
			(array) $json_clean->options, 
			(array) $json_deprecated->options);
		return $json_clean;
	}	
	
	/**
	 * 
	 * @param array $values
	 * @param array $options_clean
	 * @param array $options_deprecated
     *
     * @return array
	 */
	protected function addValueToJsonIfDeprecatedForOptions(array $values, array $options_clean, array $options_deprecated) : array
	{
		$current_value = array_shift($values);

		foreach($options_deprecated as $option)
		{
			ilLoggerFactory::getLogger('udfcs')->debug('Comparing ' . $current_value.' with: ' . $option->name);
			if($option->name == $current_value)
			{
				ilLoggerFactory::getLogger('udfcs')->debug('Options are equal');
				// add 
				$found = null;
				foreach($options_clean as $cleaned_option)
				{
					if($cleaned_option->name == $current_value)
					{
						ilLoggerFactory::getLogger('udfcs')->debug('Found option: ' . $cleaned_option->name);
						$found = $cleaned_option;
						break;
					}
				}
				if(!is_object($found))
				{
					$found = new stdClass();
					$found->name = $current_value;
					$found->options = array();
					$options_clean[] = $found;
				}
				// call subnode
				$found->options = $this->addValueToJsonIfDeprecatedForOptions(
					$values, 
					(array) $found->options, 
					(array) $option->options);
			}
		}
		return $options_clean;
	}
	
	/**
	 * Parse column specification
	 * @param SimpleXMLElement $root
     *
     * @return array
	 */
	protected function transformXmlColSpec(SimpleXMLElement $root) : array
	{
		$columns = [];
		foreach($root->children() as $child)
		{
			if($child->getName() != 'colspec')
			{
				continue;
			}
			$n = 0;
			foreach($child->children() as $column)
			{
				$columns[$n]['name'] = (string) $column['name'];
				$columns[$n]['default'] = (string) $column['default'];
				$n++;
			}
		}
		return $columns;
	}


	/**
	 * Parse xml to json
     *
     * @param SimpleXMLElement $root
     * @param bool $a_filter_deprecated
     *
	 * @return string json
	 */
	protected function transformXml(SimpleXMLElement $root, bool $a_filter_deprecated = true) : string
	{
		$node = new stdClass();
		
		$ret = $this->addOptions($root,$a_filter_deprecated);
		if(is_array($ret))
		{
			$node->options = $ret;
		}
		ilLoggerFactory::getLogger('udfcs')->dump($node);
		
		return json_encode($node);
	}
	
	/**
	 * Recursion for 
	 * @param SimpleXMLElement $element
     * @param bool $a_filter_deprecated
     * @param bool $a_is_deprecated
     *
	 * @return array
	 */
	protected function addOptions(SimpleXMLElement $element, bool $a_filter_deprecated = true, bool $a_is_deprecated = false) : array
	{
		$options = array();
		
		foreach($element->children() as $select_option)
		{
			if($select_option->getName() != 'option')
			{
				continue;
			}
			if($a_filter_deprecated && $select_option['deprecated'] == 1)
			{
				continue;
			}
			
			$option = new stdClass();
			$option->name = (string) $select_option['name'];

			$is_deprecated = 0;
			if($a_is_deprecated || (string) $select_option['deprecated'])
			{
				$is_deprecated = 1;
			}
			if(!$is_deprecated)
			{
				if(strlen((string) $select_option['deprecatedSince']))
				{
					$option->deprecatedSince = (string) $select_option['deprecatedSince'];
				}

                if(strlen((string) $select_option['deprecatedUntil']))
                {
                    $option->deprecatedUntil = (string) $select_option['deprecatedUntil'];
                }
			}
			
			
			$option->deprecated = $is_deprecated;

			$ret = $this->addOptions($select_option, $a_filter_deprecated, $is_deprecated);
			if(count($ret))
			{
				$option->options = $ret;
			}
			$options[] = $option;
		}
		return $options;
	}

}
?>