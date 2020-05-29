<?php
/**
 * Created by PhpStorm.
 * User: leila
 * Date: 20/05/2020
 * Time: 10:49
 */
require_once 'BaseModel.php';
class ZoneModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->tableName = 'zones';
    }

    function findByName($name){
        return $this->getDb()->fetch_row('SELECT * FROM '.$this->tableName.' WHERE name = ?',$name);
    }

    function findAllAll($filters=array()){
        $conditions = join(' AND ',$filters);
        $query = 'SELECT * FROM '.$this->tableName .( empty($filters) ?  '' : ' WHERE '.$conditions ).' ORDER BY name DESC';

        return $this->getDb()->fetch_all($query);
    }


}