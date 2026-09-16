<?php

declare(strict_types=1);

namespace Leifos\CascadingSelect\Setup;

use ilDatabaseUpdateSteps;
use ilDBInterface;
use ilDBConstants;
use Leifos\CascadingSelect\UserFieldType;
use Leifos\CascadingSelect\DataObjects\Parser;

// TODO in the next version, delete udf_plugin_cselect
class DBUpdateSteps11 implements ilDatabaseUpdateSteps
{
    protected ilDBInterface $db;

    public function prepare(ilDBInterface $db): void
    {
        $this->db = $db;
    }

    // Migrate existing cascading select fields
    public function step_1(): void
    {
        if (!$this->db->tableExists('udf_plugin_cselect')) {
            // fresh install, nothing to do
            return;
        }

        $old_type = 51;
        $new_type = UserFieldType::class;

        $parser = new Parser(null);

        // find all cascading select fields
        $res = $this->db->queryF(
            'SELECT field_id FROM udf_definition WHERE field_type = %s',
            [ilDBConstants::T_INTEGER],
            [$old_type]
        );
        $new_ids = [];
        while ($row = $res->fetchAssoc()) {
            $new_ids[] = (string) $row['field_id'];
        }

        foreach ($new_ids as $new_id) {
            $old_id = null;
            $xml_definition = '';

            // find old ID via mapping table
            $res = $this->db->queryF(
                'SELECT old_field_id FROM udf_field_id_map WHERE field_id = %s',
                [ilDBConstants::T_TEXT],
                [$new_id]
            );
            if ($row = $res->fetchAssoc()) {
                $old_id = (int) $row['old_field_id'];
            }
            if ($old_id === null) {
                continue;
            }

            // find definition xml via old plugin table
            $res = $this->db->queryF(
                'SELECT keyword_value FROM udf_plugin_cselect WHERE keyword = %s',
                [ilDBConstants::T_TEXT],
                ['xml_' . $old_id]
            );
            if ($row = $res->fetchAssoc()) {
                $xml_definition = (string) $row['keyword_value'];
            }
            if ($xml_definition === '') {
                continue;
            }

            // parse definition from xml
            $parsed_definition = $parser->definitionFromXML($xml_definition);
            if (!$parsed_definition->isValid()) {
                continue;
            }

            $this->db->update(
                'udf_definition',
                [
                    'field_type' => [ilDBConstants::T_TEXT, $new_type],
                    'field_values' => [ilDBConstants::T_TEXT, $parsed_definition->definition()->serialize()]
                ],
                [
                    'field_id' => [ilDBConstants::T_TEXT, $new_id]
                ]
            );
        }
    }
}
