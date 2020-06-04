<?php

include 'models/OrderModel.php';
include 'models/ClientModel.php';


    $model= new OrderModel();
    $clients= new ClientModel();


    $filter=array();

    $previous_date = date('Y-m-d', strtotime(  date("Y-m-d").' -1 day'))." 00:00:00";

    $filter[]='delivery_date < "' .$previous_date.'"';
    $filter[]='state = "' ."pendiente".'"';

    $listPendients= $model->getAllPendientsOrders($filter);

    for ($j = 0; $j < count($listPendients); ++$j) {

        $client=$clients->findById($listPendients[$j]['client_id']);
        $clients->update($client['id'],array('pendient_orders'=> $client['pendient_orders']-1));

        $model->delete($listPendients[$j]['id']);

    }

   // $model->deleteOldPendientOrders($filter);


