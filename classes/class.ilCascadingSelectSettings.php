<?php
class ilCascadingSelectSettings
{
	private static $instance = null;
	
	
	/**
	 * Singleton constructor
	 */
	protected function __construct()
	{
		
	}
	
	/**
	 * Get instance
	 * @return type
	 */
	public static function getInstance()
	{
		if(self::$instance)
		{
			return self::$instance;
		}
		return self::$instance = new self();
	}
	
	public function set($a_keyword, $a_value)
	{
		global $ilDB;
		
		$this->delete($a_keyword);
		$ilDB->insert('udf_plugin_cselect', 
			array(
				"keyword" => array("text", $a_keyword),
				"keyword_value" => array('clob', $a_value)
			)
		);
	}
	
	/**
	 * Get value
	 * @global type $ilDB
	 * @param type $a_keyword
	 * @param type $a_default
	 * @return type
	 */
	public function get($a_keyword, $a_default = '')
	{
		global $ilDB;
		
		$query = "SELECT * FROM udf_plugin_cselect WHERE  keyword =".$ilDB->quote($a_keyword, "text");
		$res = $ilDB->query($query);
		
		while ($row = $ilDB->fetchAssoc($res))
		{
			return $row['keyword_value'];
		}
		return $a_default;
	}
	
	public function delete($a_keyword)
	{
		global $ilDB;
		$ilDB->manipulate("DELETE FROM udf_plugin_cselect WHERE keyword = ".$ilDB->quote($a_keyword, "text"));
	}
}