<#1>
<?php

if (!$ilDB->tableExists('udf_plugin_cselect')) {
    $ilDB->createTable(
        'udf_plugin_cselect',
        array(
            'keyword'       => array(
                'type'    => 'text',
                'length'  => 255,
                'notnull' => false
                ),
            'keyword_value'        => array(
                'type'    => 'clob',
                'notnull' => false
            ),
        )
    );
    $ilDB->addPrimaryKey('udf_plugin_cselect', array('keyword'));
}
?>

<#2>
<?php

$query  = 'select * from settings where module = ' . $ilDB->quote('udfcs');
$res = $ilDB->query($query);
while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
    $ilDB->insert(
        'udf_plugin_cselect',
        array(
            'keyword' => array('text', $row->keyword),
            'keyword_value' => array('clob', $row->value)
        )
    );
}
?>