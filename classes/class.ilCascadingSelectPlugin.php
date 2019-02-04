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
	public function getPluginName()
	{
		return self::CASCADING_SELECT_NAME;
	}
	
	/**
	 * @return int
	 */
	public function getDefinitionType()
	{
		return self::CASCADING_TYPE_ID;
	}

	public function getDefinitionTypeName()
	{
		return $this->txt('cascading_type_name');
	}
	
	public function getDefinitionUpdateFormTitle()
	{
		return $this->txt('cascading_type_form_update');
	}
	
	/**
	 * Lookup user data
	 * Values are store in udf_text => nothing todo here
	 * @param type $a_user_ids
	 * @param type $a_field_ids
	 * @return type
	 */
	public function lookupUserData($a_user_ids, $a_field_ids)
	{
		return array();
	}
	
	
	/**
	 * Get singleton instance
	 * @global ilPluginAdmin $ilPluginAdmin
	 * @return \ilCascadingSelectPlugin
	 */
	public static function getInstance()
	{
		global $ilPluginAdmin;

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
	private final function autoLoad($a_classname)
	{
		$class_file = $this->getClassesDirectory().'/class.'.$a_classname.'.php';
		if(@include_once($class_file))
		{
			return;
		}
	}

	/**
	 * @param \ilRadioOption $option
	 * @param type $field_id
	 */
	public function addDefinitionTypeOptionsToRadioOption(\ilRadioOption $option, $field_id)
	{
		$file_input = new ilFileInputGUI($this->txt('definition_values'),'cspl_file');
		$file_input->setSuffixes(['xml','json']);
		$option->addSubItem($file_input);
		
		if(!$field_id)
		{
			ilLoggerFactory::getLogger('udfcs')->debug('No field id given');
			return;
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
	public function getFormPropertyForDefinition($definition, $a_changeable = true, $a_default_value = null)
	{
		$cascading_select = new ilCascadingSelectInputGUI(
			$definition['field_name'],
			'udf_'.$definition['field_id']
		);
		$cascading_select->setDisabled(!$a_changeable);
		
		$settings = ilCascadingSelectSettings::getInstance();

		// check if values are available for field
		$usr_id = $GLOBALS['DIC']->user()->getId();
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
		$without_deprecated_options = $this->removeDeprecatedSince((array) $with_deprecated->options, $today);
		$without_deprecated = new stdClass();
		$without_deprecated->options = $without_deprecated_options;
		
		$json_obj = $this->addValueToJsonIfDeprecated(
			$value,
			$without_deprecated,
			json_decode($settings->get('json_deprecated_'.$definition['field_id']))
		);
		
		$cascading_select->setCascadingOptions($json_obj);
		
		$coldef = $settings->get('colspec_'.$definition['field_id'],  serialize(array()));
		$cascading_select->setColumnDefinition(unserialize($coldef));
		$cascading_select->setValue($value);
		$cascading_select->setRequired($definition['required'] ? true : false);
		
		return $cascading_select;
	}
	
	/**
	 * Remove deprecated since
	 * @param array $a_with_deprecated
	 * @return array
	 */
	protected function removeDeprecatedSince($a_with_deprecated, ilDate $today)
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
			$option_without_deprecated = new stdClass();
			$option_without_deprecated->name = $option->name;
			
			if(count((array) $option->options))
			{
				$option_without_deprecated->options = $this->removeDeprecatedSince($option->options, $today);
			}
			
			$options[] = $option_without_deprecated;
		}
		return $options;
	}
	
	/**
	 * Add value to json if deprecated
	 * @param string $value
	 * @param type $json_clean
	 * @param type $json_deprecated
	 * @return type
	 */
	protected function addValueToJsonIfDeprecated($value, $json_clean, $json_deprecated)
	{
		$single_values = explode(" → ", $value);
		if(!count($single_values))
		{
			return $json_clean;
		}
		
		$json_clean->options = $this->addValueToJsonIfDeprecatedForOptions(
			$single_values, 
			(array) $json_clean->options, 
			(array) $json_deprecated->options);
		return $json_clean;
	}	
	
	/**
	 * 
	 * @param type $values
	 * @param array $options_clean
	 * @param array $options_deprecated
	 */
	protected function addValueToJsonIfDeprecatedForOptions($values, array $options_clean, array $options_deprecated)
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
	 */
	protected function transformXmlColSpec(SimpleXMLElement $root)
	{
		$columns = [];
		foreach($root->children() as $child)
		{
			if($child->getName() != 'colspec')
			{
				continue;
			}
			foreach($child->children() as $column)
			{
				$columns[] = (string) $column['name'];
			}
		}
		return $columns;
	}


	/**
	 * Parse xml to json
	 * @return string json
	 */
	protected function transformXml(SimpleXMLElement $root, $a_filter_deprecated = true)
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
	 * @return \stdClass
	 */
	protected function addOptions(SimpleXMLElement $element,$a_filter_deprecated = true, $a_is_deprecated = false)
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