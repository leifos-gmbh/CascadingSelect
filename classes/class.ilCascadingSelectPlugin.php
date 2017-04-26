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
			ilLoggerFactory::getLogger('udfd')->debug('No field id given');
			return;
		}
		
		$settings = new ilSetting('udfd');
		$xml_string = $settings->get('xml_'.$field_id);
		if(!strlen($xml_string))
		{
			ilLoggerFactory::getLogger('udfd')->debug('No xml string found for id ' . $field_id);
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
		$settings = new ilSetting('udfd');
		
		if($_FILES['cspl_file']['tmp_name'])
		{
			$xml = file_get_contents($_FILES['cspl_file']['tmp_name']);
			ilLoggerFactory::getLogger('udfd')->dump($xml);
			$settings->set('xml_'.$a_field_id, $xml);

			// create json from xml
			$xml_obj = simplexml_load_string($xml);
			$json = $this->transformXml($xml_obj);
			$settings->set('json_'.$a_field_id, $json);
			ilLoggerFactory::getLogger('udfd')->dump($json);
		}
	}
	
	/**
	 * Get form property for definition
	 * Context: edit user; registration; edit user profile 
	 * @return ilFormPropertyGUI
	 */
	public function getFormPropertyForDefinition($definition, $a_default_value = null)
	{
		$cascading_select = new ilCascadingSelectInputGUI(
			$definition['field_name'],
			'udf_'.$definition['field_id']
		);
		
		$settings = new ilSetting('udfd');
			
		$json_string = $settings->get('json_'.$definition['field_id']);
		$options = json_decode($json_string);
		$cascading_select->setCascadingOptions($options);
		$cascading_select->setValue($a_default_value);
		$cascading_select->setRequired($definition['required'] ? true : false);
		
		return $cascading_select;
	}
	
	/**
	 * Parse xml to json
	 * @return string json
	 */
	protected function transformXml(SimpleXMLElement $root)
	{
		$node = new stdClass();
		
		$ret = $this->addOptions($root);
		if(is_array($ret))
		{
			$node->options = $ret;
		}
		ilLoggerFactory::getLogger('udfd')->dump($node);
		
		return json_encode($node);
	}
	
	/**
	 * Recursion for 
	 * @param SimpleXMLElement $element
	 * @return \stdClass
	 */
	protected function addOptions(SimpleXMLElement $element)
	{
		$options = array();
		
		foreach($element->children() as $select_option)
		{
			$option = new stdClass();
			$option->name = (string) $select_option['name'];

			$ret = $this->addOptions($select_option);
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