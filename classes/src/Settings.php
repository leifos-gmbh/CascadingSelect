<?php

declare(strict_types=1);

namespace Leifos\CascadingSelect;

use Leifos\CascadingSelect\DataObjects\ColumnsDefinition;
use Leifos\CascadingSelect\DataObjects\Factory;
use Leifos\CascadingSelect\DataObjects\CascadingOptions;

class Settings
{
    private \ilDBInterface $db;
    private Factory $factory;

    private const KEYWORD_JSON = 'json';
    private const KEYWORD_JSON_DEPRECATED = 'json_deprecated';
    private const KEYWORD_COL_SPEC = 'colspec';
    private const KEYWORD_XML = 'xml';

    public function __construct(
        \ilDBInterface $db,
        Factory $factory
    ) {
        $this->db = $db;
        $this->factory = $factory;
    }

    public function getJSON(int $field_id): ?CascadingOptions
    {
        $res = $this->get(
            self::KEYWORD_JSON,
            $field_id,
            ''
        );
        if (!$res) {
            return null;
        }
        return $this->factory->cascadingOptions(
            json_decode($res)
        );
    }

    public function setJSON(
        int $field_id,
        CascadingOptions $value
    ): void {
        $this->set(
            self::KEYWORD_JSON,
            $field_id,
            json_encode($value->raw())
        );
    }

    public function deleteJSON(int $field_id): void
    {
        $this->delete(
            self::KEYWORD_JSON,
            $field_id
        );
    }

    public function getJSONDeprecated(int $field_id): ?CascadingOptions
    {
        $res = $this->get(
            self::KEYWORD_JSON_DEPRECATED,
            $field_id,
            ''
        );
        if (!$res) {
            return null;
        }
        return $this->factory->cascadingOptions(
            json_decode($res)
        );
    }

    public function setJSONDeprecated(
        int $field_id,
        CascadingOptions $value
    ): void {
        $this->set(
            self::KEYWORD_JSON_DEPRECATED,
            $field_id,
            json_encode($value->raw())
        );
    }

    public function deleteJSONDeprecated(int $field_id): void
    {
        $this->delete(
            self::KEYWORD_JSON_DEPRECATED,
            $field_id
        );
    }

    public function getColSpec(int $field_id): ColumnsDefinition
    {
        $serialized = $this->get(
            self::KEYWORD_COL_SPEC,
            $field_id,
            serialize([])
        );
        return $this->factory->columnsDefinition(
            unserialize($serialized)
        );
    }

    public function setColSpec(
        int $field_id,
        ColumnsDefinition $value
    ): void {
        $this->set(
            self::KEYWORD_COL_SPEC,
            $field_id,
            $value->rawSerializedArray()
        );
    }


    public function deleteColSpec(int $field_id): void
    {
        $this->delete(
            self::KEYWORD_COL_SPEC,
            $field_id
        );
    }

    public function getXML(int $field_id): ?\SimpleXMLElement
    {
        $res = $this->get(
            self::KEYWORD_XML,
            $field_id,
            ''
        );
        if (!$res) {
            return null;
        }
        return simplexml_load_string($res);
    }

    public function setXML(int $field_id, string $value): void
    {
        $this->set(
            self::KEYWORD_XML,
            $field_id,
            $value
        );
    }

    public function deleteXML(int $field_id): void
    {
        $this->delete(
            self::KEYWORD_XML,
            $field_id
        );
    }

    protected function set(
        string $keyword,
        int $field_id,
        string $value
    ): void {
        $this->delete($keyword, $field_id);

        $full_keyword = $keyword . '_' . $field_id;
        $this->db->insert(
            'udf_plugin_cselect',
            array(
                "keyword" => array("text", $full_keyword),
                "keyword_value" => array('clob', $value)
            )
        );
    }

    protected function get(
        string $keyword,
        int $field_id,
        string $default = ''
    ): string {
        $keyword = $keyword . '_' . $field_id;

        $query = "SELECT * FROM udf_plugin_cselect WHERE  keyword =" . $this->db->quote($keyword, "text");
        $res = $this->db->query($query);

        while ($row = $this->db->fetchAssoc($res)) {
            return (string) $row['keyword_value'];
        }
        return $default;
    }

    protected function delete(
        string $keyword,
        int $field_id
    ): void {
        $keyword = $keyword . '_' . $field_id;

        $this->db->manipulate("DELETE FROM udf_plugin_cselect WHERE keyword = " . $this->db->quote(
            $keyword,
            "text"
        ));
    }
}
