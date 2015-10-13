<?php

class CreateTableQueries extends Doctrine_Migration_Base
{
    private $_tableName = 'queries';

    public function up()
    {
        
        $this->createTable($this->_tableName, array(
            'id' => array(
                'type' => 'integer',
                'notnull' => true,
                'primary' => true,
                'autoincrement' => true,
            ),
            'datetime' => array(
                'type' => 'Datetime',
                'notnull' => false,
            ),
            'site' => array(
                'type' => 'character varying(32)',
                'notnull' => false,
            ),
            'q' => array(
                'type' => 'Text',
                'notnull' => false,
            ),            
            'word' => array(
                'type' => 'character varying(64)',
                'notnull' => false,
            ),
            'ip' => array(
                'type' => 'character varying(32)',
                'notnull' => false,
            ),
            

        ), array('charset'=>'utf8'));
        
        $this->addIndex($this->_tableName,$this->_tableName.'_site_key',array('fields'=>array('site')));
   
    }

    public function down()
    {
        $this->dropTable($this->_tableName);
    }
}
