<?php

class ilCascadingSelectSettings
{
    private static $instance = null;

    private $ilDB;

    /**
     * Singleton constructor
     */
    protected function __construct()
    {
        global $DIC;

        $this->ilDB = $DIC->database();

    }

    /**
     * Get instance
     * @return self
     */
    public static function getInstance() : self
    {
        if (self::$instance) {
            return self::$instance;
        }
        return self::$instance = new self();
    }

    /**
     * @param string $a_keyword
     * @param string $a_value
     */
    public function set(string $a_keyword, string $a_value)
    {

        $this->delete($a_keyword);
        $this->ilDB->insert('udf_plugin_cselect',
            array(
                "keyword" => array("text", $a_keyword),
                "keyword_value" => array('clob', $a_value)
            )
        );
    }

    /**
     * Get value
     * @param string  $a_keyword
     * @param string  $a_default
     * @return string
     * @global object $ilDB
     */
    public function get(string $a_keyword, string $a_default = '') : string
    {

        $query = "SELECT * FROM udf_plugin_cselect WHERE  keyword =" . $this->ilDB->quote($a_keyword, "text");
        $res = $this->ilDB->query($query);

        while ($row = $this->ilDB->fetchAssoc($res)) {
            return $row['keyword_value'];
        }
        return $a_default;
    }

    /**
     * @param string $a_keyword
     */
    public function delete(string $a_keyword)
    {
        $this->ilDB->manipulate("DELETE FROM udf_plugin_cselect WHERE keyword = " . $this->ilDB->quote($a_keyword,
                "text"));
    }
}