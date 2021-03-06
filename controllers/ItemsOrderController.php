<?php
/**
 * Created by PhpStorm.
 * User: leila
 * Date: 20/05/2020
 * Time: 10:40
 */

require_once 'BaseController.php';
require_once __DIR__.'/../models/ItemOrderModel.php';
require_once __DIR__.'/../models/ProductModel.php';
class ItemsOrderController extends BaseController
{
    private $products;

    function __construct(){
        parent::__construct();
        $this->model = new ItemOrderModel();
        $this->products = new ProductModel();
    }

    function delete()
    {
        if($_GET['id']){
            $item_order=$this->model->findById($_GET['id']);
            $product= $this->products->findById($item_order['product_id']);

            $stockToAdd=$this->calculateStockToRestOrAdd($item_order);

            $this->products->update($product['id'],array('stock'=> $product['stock'] + $stockToAdd));
        }

        parent::delete(); // TODO: Change the autogenerated stub
    }


    function calculateStockToRestOrAdd($item_order){
        if($item_order['price_type'] == "kg" ){
            return $item_order['quantity'];
        }else if($item_order['price_type'] == "half_kg"){
            return $item_order['quantity']*0.5;
        }else{
            return $item_order['quantity']*0.25;
        }
    }

    function post()
    {
        $item_order = (array)json_decode(file_get_contents("php://input"));

        $product= $this->products->findById($item_order['product_id']);

        $stockToRest=$this->calculateStockToRestOrAdd($item_order);
        //$this->products->update($product['id'],array('stock'=> $product['stock'] - $item_order['quantity']));
        $this->products->update($product['id'],array('stock'=> $product['stock'] - $stockToRest));

        parent::post(); // TODO: Change the autogenerated stub
    }

    function amount(){

        if(isset($_GET['order_id'])){

            $filter2=array();
            $filter2[]='order_id = "' . $_GET['order_id'] . '"';

            $items_order_list = $this->model->findAllByOrderId($filter2);

            $response["total"]=0.0;

            for ($i = 0; $i < count($items_order_list); ++$i) {

                // $product = $this->products->findById($items_order_list[$i]['product_id']);
                $response["total"]=$response["total"]+ ($items_order_list[$i]['quantity']*$items_order_list[$i]['price']);
            }

            $this->returnSuccess(200,$response);
        }
    }





}