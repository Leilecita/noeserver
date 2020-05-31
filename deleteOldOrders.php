<?php

include 'models/OrderModel.php';


    $model= new OrderModel();


    $filter=array();

    $previous_date = date('Y-m-d', strtotime(  date("Y-m-d").' -1 day'))." 00:00:00";

    $filter[]='delivery_date < "' .$previous_date.'"';
    $filter[]='state = "' ."pendiente".'"';
    $model->deleteOldPendientOrders($filter);


