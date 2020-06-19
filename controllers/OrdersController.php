<?php
/**
 * Created by PhpStorm.
 * User: leila
 * Date: 20/05/2020
 * Time: 10:38
 */
require_once 'BaseController.php';
require_once __DIR__.'/../models/OrderModel.php';
require_once __DIR__.'/../models/ClientModel.php';
require_once __DIR__.'/../models/ProductModel.php';
require_once __DIR__.'/../models/ItemOrderModel.php';
require_once __DIR__.'/../template/template.php';
require_once __DIR__.'/../config/config.php';

class OrdersController extends BaseController
{
    private $clients;
    private $items_order;
    private $products;

    function __construct(){
        parent::__construct();
        $this->model = new OrderModel();
        $this->clients= new ClientModel();
        $this->items_order= new ItemOrderModel();
        $this->products= new ProductModel();

    }

    function getOrdersValues(){
        $date=$this->getDates($_GET['delivery_date']);

        $pendients=$this->model->count($date['date'],$date['dateTo'],'pendiente');
        $sends=$this->model->count($date['date'],$date['dateTo'],'entregado');

        $resp=array('pendients' => $pendients, 'sends' => $sends);

        $this->returnSuccess(200,$resp);
    }

    function getTotalOrdersPendient(){

        $pendients=$this->model->countTotalPendientOrders('pendiente');
        $sends=$this->model->countTotalPendientOrders('pendiente');

        $resp=array('pendients' => $pendients, 'sends' => $sends);

        $this->returnSuccess(200,$resp);

    }

    public function post()
    {
        $data = (array)json_decode(file_get_contents("php://input"));

        $client=$this->clients->findById($data['client_id']);
        $this->clients->update($client['id'],array('pendient_orders'=> $client['pendient_orders']+1));

        unset($data['id']);
        $res = $this->getModel()->save($data);
        if($res<0){
            $this->returnError(404,null);
        }else{
            $inserted = $this->getModel()->findById($res);

            //esto por si se llega a querer borar una orden pendiente. mientras se esta creando la orden esta en borrador.
            $this->model->update($inserted['id'],array('state'=>"pendiente"));

            $this->returnSuccess(201,$inserted);
        }
    }

    function calculateStockToAdd($item_order){
        if($item_order['price_type'] == "kg" ){
            return $item_order['quantity'];
        }else if($item_order['price_type'] == "half_kg"){
            return $item_order['quantity']*0.5;
        }else{
            return $item_order['quantity']*0.25;
        }
    }

    public function delete()
    {
        if(isset($_GET['id'])){

            $order=$this->model->findById($_GET['id']);

            if($order['state'] != "entregado"){

                $list_items_order=$this->items_order->findAllByOrderId(array('order_id = "' .$_GET['id'].'"'));
                for ($j = 0; $j < count($list_items_order); ++$j) {
                    $product= $this->products->findById($list_items_order[$j]['product_id']);
                    $this->products->update($product['id'],array('stock'=> $product['stock'] + $this->calculateStockToAdd($list_items_order[$j])));
                }
            }

            $this->items_order->deleteAll($_GET['id']);

            $client=$this->clients->findById($order['client_id']);
            $this->clients->update($client['id'],array('pendient_orders'=> $client['pendient_orders']-1));
        }

        parent::delete();
    }

    function finish(){

        if(isset($_GET['order_id'])){
            $order=$this->model->findById($_GET['order_id']);
            if($order){
                $client=$this->clients->findById($order['client_id']);
                if($order['state'] == "pendiente"){
                    $this->model->update($order['id'],array('state'=> "entregado"));
                    $this->clients->update($client['id'],array('pendient_orders'=> $client['pendient_orders']-1));

                }else if ($order['state'] == "entregado"){
                    $this->model->update($order['id'],array('state'=> "pendiente"));
                    $this->clients->update($client['id'],array('pendient_orders'=> $client['pendient_orders']+1));
                }

                $this->returnSuccess(200,$this->model->findById($order['id']));
            }else{
                $this->returnError(404,"ENTITY NOT FOUND");
            }
        }
    }

    function prepared(){

        if(isset($_GET['order_id'])){
            $order=$this->model->findById($_GET['order_id']);
            if($order){
                if($order['prepared'] == "true"){
                    $this->model->update($order['id'],array('prepared'=> "false"));

                }else if ($order['prepared'] == "false"){
                    $this->model->update($order['id'],array('prepared'=> "true"));
                }

                $this->returnSuccess(200,$this->model->findById($order['id']));
            }else{
                $this->returnError(404,"ENTITY NOT FOUND");
            }
        }
    }

    function priority(){
        if(isset($_GET['order_id'])){
            $order=$this->model->findById($_GET['order_id']);
            if($order){
                $this->model->update($order['id'],array('priority'=> $_GET['priority']));

                $this->returnSuccess(200,$this->model->findById($order['id']));
            }else{
                $this->returnError(404,"ENTITY NOT FOUND");
            }
        }
    }

    function getSum($list){
        $res=0.0;
        for ($j = 0; $j < count($list); ++$j) {
            $items_order_list = $this->items_order->findAllAll(array('order_id = "' . $list[$j]['id'] . '"'));

            for ($i = 0; $i < count($items_order_list); ++$i) {

                $quantity=$items_order_list[$i]['quantity'];
                $price=$items_order_list[$i]['price'];

                $res= $res + ($quantity * $price);
            }
        }
        return $res;
    }

    function getDates($data){

        $parts = explode(" ", $data);
        $date=$parts[0]." 00:00:00";
        $next_date = date('Y-m-d', strtotime( $parts[0].' +1 day'));
        $dateTo=$next_date." 00:00:00";
        $result=array('date' => $date, 'dateTo' => $dateTo);
        return $result;
    }

    function summaryDayValues(){

        if(isset($_GET['delivery_date'])){

            $dates=$this->getDates($_GET['delivery_date']);

            $filter=array();
            $filter[]='delivery_date < "' .$dates['dateTo'].'"';
            $filter[]='delivery_date >= "' .$dates['date'].'"';

            $sumTot=$this->getSum($this->getModel()->findAllAll($filter));

            $filter[]='state = "' ."pendiente".'"';

            $sumPendient=$this->getSum($this->getModel()->findAllAll($filter));

            $filter2=array();
            $filter2[]='delivery_date < "' .$dates['dateTo'].'"';
            $filter2[]='delivery_date >= "' .$dates['date'].'"';

            $filter2[]='state = "' ."entregado".'"';

            $sumDone=$this->getSum( $this->getModel()->findAllAll($filter2));

            $valuesDay=array('sumDone' => $sumDone,'sumPendient' => $sumPendient,'sumTot' => $sumTot);
            $this->returnSuccess(200,$valuesDay);


        }else{
            $this->returnError(404,"ENTITY NOT FOUND");
        }

    }

    function summaryDay(){

        if(isset($_GET['delivery_date'])) {

            $summaryReport = array();

            $dates=$this->getDates($_GET['delivery_date']);
            $filter=array();
            $filter[]='delivery_date < "' .$dates['dateTo'].'"';
            $filter[]='delivery_date >= "' .$dates['date'].'"';

            $list_orders_by_deliver_date = $this->getModel()->findAllAll($filter);

            for ($j = 0; $j < count($list_orders_by_deliver_date); ++$j) {

                $items_order_list = $this->items_order->findAllAll(array('order_id = "' . $list_orders_by_deliver_date[$j]['id'] . '"'));

                for ($i = 0; $i < count($items_order_list); ++$i) {

                    $quantity=$items_order_list[$i]['quantity'];
                    $price=$items_order_list[$i]['price'];

                    $totalPrice=$quantity*$price;

                    $res = false;
                    foreach($summaryReport as $key => $value )
                    {
                        if($summaryReport[$key]['nameProduct'] === $items_order_list[$i]['product_name'] &&
                            $summaryReport[$key]['typePrice'] === $items_order_list[$i]['price_type']){

                            $summaryReport[$key]['totalQuantity'] = $summaryReport[$key]['totalQuantity'] + $quantity;
                            $summaryReport[$key]['totalPrice'] = $summaryReport[$key]['totalPrice'] + $totalPrice;

                            $res=true;
                        }
                    }

                    if(!$res){
                        $summaryReport[]= array('productId' => $items_order_list[$i]['product_id'], 'nameProduct' =>$items_order_list[$i]['product_name'],
                            'totalQuantity' => $quantity,'totalPrice' =>  $totalPrice , 'typePrice' => $items_order_list[$i]['price_type']);
                    }



                }
            }
            $this->returnSuccess(200,$summaryReport);
        }else{
            $this->returnError(404,"ENTITY NOT FOUND");
        }
    }

    function listAllOrders(){

        $filter=array();

        $list_orders_by_deliver_date = $this->model->findAllOrders($filter,$this->getPaginator());

        $listReport=$this->getReportOrder($list_orders_by_deliver_date);
        $this->returnSuccess(200, $listReport);

    }


    function getReportOrder($list_orders_by_deliver_date){
        $listReport = array();

        for ($j = 0; $j < count($list_orders_by_deliver_date); ++$j) {

            $items_order_list = $this->items_order->findAllItems(array('order_id = "' . $list_orders_by_deliver_date[$j]['order_id'] . '"'));

            $array_product = array();
            $total_amount=0;
            for ($i = 0; $i < count($items_order_list); ++$i) {

                $array_product[] = array('name' => $items_order_list[$i]['product_name'], 'price' => $items_order_list[$i]['price'],
                    'quantity' => $items_order_list[$i]['quantity'],'price_type' => $items_order_list[$i]['price_type']);

                $total_amount=$total_amount+($items_order_list[$i]['price']*$items_order_list[$i]['quantity']);

            }

            $listReport[] = array('defaulter' => $list_orders_by_deliver_date[$j]['defaulter'],
                'delivery_time' => $list_orders_by_deliver_date[$j]['delivery_time'],'order_created' => $list_orders_by_deliver_date[$j]['created'],
                'order_obs' => $list_orders_by_deliver_date[$j]['observation'],'order_id' => $list_orders_by_deliver_date[$j]['order_id'],
                'client_id' => $list_orders_by_deliver_date[$j]['client_id'],
                'name' => $list_orders_by_deliver_date[$j]['name'],
                'prepared' => $list_orders_by_deliver_date[$j]['prepared'],
                'address' => $list_orders_by_deliver_date[$j]['address'],'zone' => $list_orders_by_deliver_date[$j]['zone'],'phone' => $list_orders_by_deliver_date[$j]['phone'],
                'delivery_date' => $list_orders_by_deliver_date[$j]['delivery_date'],'total_amount' => $total_amount, 'items' => $array_product,
                'state' => $list_orders_by_deliver_date[$j]['state'],'priority' => $list_orders_by_deliver_date[$j]['priority']
            );
        }

        return $listReport;
    }

    function listAndSearchOrders(){

            $listReport = array();

            $filter=array();

            if(isset($_GET['delivery_date'])) {


                $dates=$this->getDates($_GET['delivery_date']);

                $filter[]='delivery_date < "' .$dates['dateTo'].'"';
                $filter[]='delivery_date >= "' .$dates['date'].'"';

            }
            if(isset($_GET['time'])){
                if(strcmp($_GET['time'],"Todos los horarios")!==0 ){
                    $filter[] = 'delivery_time = "' . $_GET['time'] . '"';
                }
            }

            if (isset($_GET['query']) && !empty($_GET['query'])) {
                $filter[] = 'name like "%' . $_GET['query'] . '%"';
            }

            if(isset($_GET['zone']) && !empty($_GET['zone'])){
                if(strcmp($_GET['zone'],"Todas las zonas") !== 0){
                    $filter[] = 'zone = "' . $_GET['zone'] . '"';
                }
            }

            $list_orders_by_deliver_date = $this->model->findAllOrdersAndClient($filter,$this->getPaginator());

            $listReport=$this->getReportOrder($list_orders_by_deliver_date);

           /* for ($j = 0; $j < count($list_orders_by_deliver_date); ++$j) {

                $items_order_list = $this->items_order->findAllItems(array('order_id = "' . $list_orders_by_deliver_date[$j]['order_id'] . '"'));

                $array_product = array();
                $total_amount=0;
                for ($i = 0; $i < count($items_order_list); ++$i) {

                    $array_product[] = array('name' => $items_order_list[$i]['product_name'], 'price' => $items_order_list[$i]['price'],
                        'quantity' => $items_order_list[$i]['quantity'],'price_type' => $items_order_list[$i]['price_type']);

                    $total_amount=$total_amount+($items_order_list[$i]['price']*$items_order_list[$i]['quantity']);

                }

                $listReport[] = array('defaulter' => $list_orders_by_deliver_date[$j]['defaulter'],
                    'delivery_time' => $list_orders_by_deliver_date[$j]['delivery_time'],'order_created' => $list_orders_by_deliver_date[$j]['created'],
                    'order_obs' => $list_orders_by_deliver_date[$j]['observation'],'order_id' => $list_orders_by_deliver_date[$j]['order_id'],
                    'client_id' => $list_orders_by_deliver_date[$j]['client_id'],
                    'name' => $list_orders_by_deliver_date[$j]['name'],
                    'prepared' => $list_orders_by_deliver_date[$j]['prepared'],
                    'address' => $list_orders_by_deliver_date[$j]['address'],'zone' => $list_orders_by_deliver_date[$j]['zone'],'phone' => $list_orders_by_deliver_date[$j]['phone'],
                    'delivery_date' => $list_orders_by_deliver_date[$j]['delivery_date'],'total_amount' => $total_amount, 'items' => $array_product,
                    'state' => $list_orders_by_deliver_date[$j]['state'],'priority' => $list_orders_by_deliver_date[$j]['priority']
                    );
                //'debt_value' => $list_orders_by_deliver_date[$j]['debt_value']
            }*/

            $this->returnSuccess(200, $listReport);

      /*  }else{
            $this->returnError(404,"ENTITY NOT FOUND");
        }*/
    }

    public function get()
    {
        if(isset($_GET['method'])){
            $this->method();
        }else if(isset($_GET['client_id'])){

            $listReport = array();

            $list_orders_by_user_id = $this->getModel()->findAllOrder(array('client_id = "' .$_GET['client_id'].'"'),$this->getPaginator());

            for ($j = 0; $j < count($list_orders_by_user_id); ++$j) {
                $client = $this->clients->findById($list_orders_by_user_id[$j]['client_id']);

                $items_order_list = $this->items_order->findAllItems(array('order_id = "' . $list_orders_by_user_id[$j]['id'] . '"'));

                $array_product = array();

                $total_amount=0;
                for ($i = 0; $i < count($items_order_list); ++$i) {

                    $array_product[] = array('name' => $items_order_list[$i]['product_name'], 'price' => $items_order_list[$i]['price'],
                        'quantity' => $items_order_list[$i]['quantity'],'price_type' => $items_order_list[$i]['price_type']);

                    $total_amount=$total_amount+($items_order_list[$i]['price']*$items_order_list[$i]['quantity']);

                }

                $listReport[] = array('defaulter' => $list_orders_by_user_id[$j]['defaulter'],
                    'delivery_time' => $list_orders_by_user_id[$j]['delivery_time'],
                    'order_created' => $list_orders_by_user_id[$j]['created'],'order_obs' => $list_orders_by_user_id[$j]['observation'],
                    'order_id' => $list_orders_by_user_id[$j]['id'],
                    'client_id' => $client['id'],
                    'name' => $client['name'],
                    'prepared' => $list_orders_by_user_id[$j]['prepared'],
                    'address' => $client['address'],'zone' => $client['zone'],'phone' => $client['phone'],
                    'delivery_date' => $list_orders_by_user_id[$j]['delivery_date'],'total_amount' => $total_amount, 'items' => $array_product,
                    'state' => $list_orders_by_user_id[$j]['state'], 'priority' => $list_orders_by_user_id[$j]['priority']
                    );
            }

            $this->returnSuccess(200, $listReport);

        }else{
            parent::get();
        }
    }




    function deleteOldPendientOrders(){

        $filter=array();

        $previous_date = date('Y-m-d', strtotime(  date("Y-m-d").' -1 day'))." 00:00:00";


        $filter[]='delivery_date < "' .$previous_date.'"';
        $filter[]='state = "' ."pendiente".'"';

        var_dump($previous_date);
        //var_dump('delivery_date < "' .$previous_date.'"');

        $this->model->deleteOldPendientOrders($filter);
    }

    //PDF

    function getItems($orderId){
        $filter2=array();
        $filter2[]='order_id = "' . $orderId . '"';

        $items_order_list = $this->items_order->findAllItems($filter2);

        $array_product = array();
        $total_amount=0;
        for ($i = 0; $i < count($items_order_list); ++$i) {

                $array_product[] = array('name' => $items_order_list[$i]['product_name'], 'price' => $items_order_list[$i]['price'],
                    'quantity' => $items_order_list[$i]['quantity'],'price_type' => $items_order_list[$i]['price_type']);

                $total_amount=$total_amount+($items_order_list[$i]['price']*$items_order_list[$i]['quantity']);

        }
        return $list=array('items'=>$array_product,'total_amount'=>$total_amount);
    }

    function generatePdf(){

        if(isset($_GET['order_id'])){

            $orderId = $_GET['order_id'];
            $order=$this->model->findById($orderId);

            $user=$this->clients->findById($order['client_id']);

            $data = array(
                'items' => array( array('name' => 'Langostinos', "qty" => 1, "price"=>"160"), array('name' => 'Merluza', "qty" => 2, "price"=>"140"))
            );
            // echo render($data);

            // $filename="Order-".$orderId."pdf";
            $filename="Order-".$user['name']."pdf";

            // $this->generate_pdf(render($data),$filename);

            $parts = explode(" ", $this->getDate($order['delivery_date']));
            $date=$parts[0];

            $this->generate_pdf(render($this->getItems($orderId),$user,$date),$filename);


        }else{
            $this->returnError(400,"No se valida order_id");
        }

    }

    function getDate($completeDate){
        $parts = explode(" ", $completeDate);
        $date=$parts[0];

        $partsDate = explode("-", $date);

        return $partsDate[2]."-".$partsDate[1]."-".$partsDate[0];

    }

    function generate_pdf($template, $filename )
    {
        global $WKCONFIG;

        $html=$template;

        // Run wkhtmltopdf
        $descriptorspec = array(
            0 => array('pipe', 'r'), // stdin
            1 => array('pipe', 'w'), // stdout
            2 => array('pipe', 'w'), // stderr
        );

        $process = proc_open($WKCONFIG['PATH'].' -q - -', $descriptorspec, $pipes);

        // Send the HTML on stdin
        fwrite($pipes[0], $html);
        fclose($pipes[0]);

        // Read the outputs
        $pdf = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);

        // Close the process
        fclose($pipes[1]);

        $return_value = proc_close($process);

        // Output the results
        if ($errors) {
            http_response_code(400);
            echo "PDF generation failed ".$errors;
        } else {
            header('Content-Type: application/pdf');
            header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
            header('Pragma: public');
            header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s').' GMT');
            header('Content-Length: ' . strlen($pdf));
            header('Content-Disposition: inline; filename="' . $filename . '";');
            ob_clean();
            flush();
            echo $pdf;
        }
    }


}