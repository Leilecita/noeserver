<?php
/**
 * Created by PhpStorm.
 * User: leila
 * Date: 20/05/2020
 * Time: 10:48
 */
require_once 'BaseController.php';
require_once __DIR__.'/../models/ZoneModel.php';
class ZonesController extends BaseController
{
    function __construct(){
        parent::__construct();
        $this->model = new ZoneModel();
    }

    function existZone(){
        if(isset($_GET['name'])){
            $response=$this->model->findByName($_GET['name']);
            $this->returnSuccess(200,$response);
        }
    }

    function getZones(){
        $this->returnSuccess(200,$this->model->findAllAll($this->getFilters()));
    }
}