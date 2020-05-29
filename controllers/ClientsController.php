<?php
/**
 * Created by PhpStorm.
 * User: leila
 * Date: 20/05/2020
 * Time: 10:45
 */
require_once 'BaseController.php';
require_once __DIR__.'/../models/ClientModel.php';
require_once __DIR__.'/../models/OrderModel.php';
class ClientsController extends BaseController
{
   // private $orders;
    function __construct(){
        parent::__construct();
        $this->model = new ClientModel();
       // $this->orders = new OrderModel();
    }


    function getClients(){

        $listUsers= $this->getModel()->findAllByName2($this->getFilters(),$this->getPaginator());
        $userReport=array();

       // $totalAmountDebt=$this->orders->sumAmountDebt();

        for ($j = 0; $j < count($listUsers); ++$j) {
           // $debtAmount=$this->orders->sumAmountDebtOrder($listUsers[$j]['id']);

            $userReport[]=array('id' => $listUsers[$j]['id'], 'name' => $listUsers[$j]['name'], 'address' => $listUsers[$j]['address'], 'phone' => $listUsers[$j]['phone'],
                'facebook' => $listUsers[$j]['facebook'], 'zone' => $listUsers[$j]['zone'],'instagram' => $listUsers[$j]['instagram'],
                'pendient_orders' => $listUsers[$j]['pendient_orders'], 'created' => $listUsers[$j]['created'], 'defaulter' => $listUsers[$j]['defaulter'],
                'debt_value' => 0.0);
        }
        $reportUsers=array('listClients' => $userReport,'totalDebt' => 0.0);


        $this->returnSuccess(200,$reportUsers);
    }


    function get(){
        //error_log("ale");
        //error_log("SERVER: ".print_r($_SERVER,true));
        if(isset($_GET['method'])){
            $this->method();
        }else if($this->validateId()){
            $entity = $this->getModel()->findById($_GET['id']);
            if(!empty($entity)){
                $this->returnSuccess(200,$entity);
            }else{
                $this->returnError(404,"ENTITY NOT FOUND");
            }

        }else{
            $this->returnSuccess(200,$this->getModel()->findAllByName2($this->getFilters(),$this->getPaginator()));
        }
    }

    public function getFilters()
    {

        $filters = parent::getFilters(); // TODO: Change the autogenerated stub
        if (isset($_GET['query']) && !empty($_GET['query'])) {
            $filters[] = 'name like "%' . $_GET['query'] . '%"';
        }
        return $filters;

    }
}
