<?php

/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/Form/classes/class.ilSubEnabledFormPropertyGUI.php';
/**
 * Description of class class 
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de> 
 *
 */
class ilCascadingSelectInputGUI extends ilSubEnabledFormPropertyGUI
{
	const SEPERATOR = ' → ';
	
	private $cascading_values = null;
	
	/**
	 * @var ilCascadingSelectPlugin 
	 */
	private $cascading_plugin;
	
	private $column_definition = [];
	
	/**
	 * Constructor
	 *
	 * @param	string	$a_title	Title
	 * @param	string	$a_postvar	Post Variable
	 */
	public function __construct($a_title = "", $a_postvar = "")
	{
		$this->cascading_plugin = ilCascadingSelectPlugin::getInstance();
		
		parent::__construct($a_title, $a_postvar);
		$this->setType("cascadingSelect");
	}

	/**
	 * Set Options.
	 *
	 * @param	mixed
	 */
	public function setOptions($a_options)
	{
		$this->options = $a_options;
	}

	/**
	 * Get Options.
	 *
	 * @return	mixed	Options.
	 */
	public function getOptions()
	{
		return $this->options ? $this->options : array();
	}
	
	public function setColumnDefinition($a_coldef)
	{
		$this->column_definition = $a_coldef;
	}
	
	/**
	 * Get column definition
	 * @return array 
	 */
	public function getColumnDefinition()
	{
		return $this->column_definition;
	}
	
	/**
	 * Set Value.
	 *
	 * @param	string	$a_value	Value
	 */
	public function setValue($a_value)
	{
		$this->value = $a_value;
	}

	/**
	 * Get Value.
	 *
	 * @return	string	Value
	 */
	public function getValue()
	{
		return $this->value;
	}
	
	public function setCascadingOptions($a_cascading_options)
	{
		$this->cascading_values = $a_cascading_options;
	}
	
	public function getCascadingOptions()
	{
		return $this->cascading_values;
	}
	
	/**
	 * Set values by array
	 * @param type $a_values
	 */
	public function setValueByArray($a_values)
	{
		$this->setValue($a_values[$this->getPostVar()]);
		foreach($this->getSubItems() as $item)
		{
			$item->setValueByArray($a_values);
		}
	}


	/**
	 * Check input, strip slashes etc. set alert, if input is not ok.
	 *
	 * @return	boolean		Input ok, true/false
	 */
	public function checkInput()
	{
		global $lng;
		
		$valid = true;
		$_POST[$this->getPostVar()] = ilUtil::stripSlashes($_POST[$this->getPostVar()]);

		// validate options against options
		$values = explode(self::SEPERATOR, $_POST[$this->getPostVar()]);
		
		$options = $this->getCascadingOptions();
		$options = $options->options;

		$confirmed_values = [];
		foreach($values as $value)
		{
			foreach((array) $options as $option)
			{
				if($option->name == trim($value))
				{
					$confirmed_values[] = trim($value);
					$options = $option->options;
					break;
				}
			}
		}
		
		$levels = $this->parseLevels($this->getCascadingOptions());
		if(
			$this->getRequired() &&
			(count($confirmed_values) < $levels)
		) 
		{
			$this->setAlert($lng->txt("msg_input_is_required"));
			return false;
		}
		
		$_POST[$this->getPostVar()] = implode(self::SEPERATOR, $confirmed_values);
		return $this->checkSubItemsInput();
	}
	
	/**
	 * Insert property html
	 *
	 * @return	int	Size
	 */
	public function insert($a_tpl)
	{
		$a_tpl->setCurrentBlock("prop_generic");
		$a_tpl->setVariable("PROP_GENERIC", $this->render());
		$a_tpl->parseCurrentBlock();
	}
	
	
	/**
	 * Render cascading select
	 * @param string $a_mode
	 */
	public function render($a_mode = '')
	{
		$template = $this->cascading_plugin->getTemplate('tpl.prop_cascading_select.html', true, true);
		
		$num_levels = $this->parseLevels($this->getCascadingOptions());
		
		$template->setVariable('NUM_LEVELS', $num_levels);
		$template->setVariable('UNIQUE_ID_SEL', 'udf_'.$this->getFieldId().'_select');
		$template->setVariable('JSON_DEF', json_encode($this->getCascadingOptions()));
		$template->setVariable('TXT_SEL', $GLOBALS['lng']->txt('links_select_one'));
		$template->setVariable('VALUE',$this->getValue());
		
		// colomn titles
		if(count($this->getColumnDefinition()))
		{
			foreach($this->getColumnDefinition() as $name)
			{
				$template->setCurrentBlock('level_text');
				$template->setVariable('TXT_COL',$name);
				$template->parseCurrentBlock();
			}
		}

		for($i = 0; $i < $num_levels; $i++)
		{
			$template->setCurrentBlock('level_options');
			$template->setVariable('TXT_LEVEL_OPTION', $GLOBALS['lng']->txt('links_select_one'));
			$template->setVariable('VAL_LEVEL_OPTION', '');
			
			$template->setCurrentBlock('level_select');
			if($this->getDisabled())
			{
				$template->setVariable('DISABLED', 'disabled="disabled"');
			}
			$template->setVariable('ID', $this->getFieldId().'_'.$i);
			$template->setVariable('POST_VAR', $this->getPostVar());
			$template->parseCurrentBlock();
		}
		
		return $template->get();
	}
	
	protected function getFirstLevelOptions()
	{
	}


	protected function parseLevels($a_cascading_options)
	{
		static $depth = 0;
		static $maxdepth = 0;
		
		$depth++;
		if(!is_array($a_cascading_options->options) || count($a_cascading_options->options) == 0)
		{
			$depth--;
			return $maxdepth;
		}

		
		if($depth > $maxdepth)
		{
			$maxdepth = $depth;
		}
		
		foreach($a_cascading_options->options as $option)
		{
			$this->parseLevels($option);
		}
		$depth--;
		return $maxdepth;
	}

}
?>