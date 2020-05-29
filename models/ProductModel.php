<?php
/**
 * Created by PhpStorm.
 * User: leila
 * Date: 20/05/2020
 * Time: 10:43
 */
require_once 'BaseModel.php';

class ProductModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->tableName = 'products';
    }

}