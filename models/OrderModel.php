<?php
/**
 * Created by PhpStorm.
 * User: leila
 * Date: 20/05/2020
 * Time: 10:38
 */

require_once 'BaseModel.php';

class OrderModel extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->tableName = 'orders';
    }

    function sumAmountDebtOrder($user_id){
        $response = $this->getDb()->fetch_row('SELECT SUM(debt_value) AS total FROM '.$this->tableName.' WHERE client_id = ? ',$user_id);

        if($response['total']!=null){
            return $response['total'];
        }else{
            $response['total']=0;
            return $response['total'];
        }
    }


    function sumAmountDebt(){
        $response = $this->getDb()->fetch_row('SELECT SUM(debt_value) AS total FROM '.$this->tableName);

        if($response['total']!=null){
            return $response['total'];
        }else{
            $response['total']=0;
            return $response['total'];
        }
    }


    function countOrders($delivery_dateSince,$delivery_dateT0,$state){
        $response = $this->getDb()->fetch_row('SELECT COUNT(id) AS total FROM '.$this->tableName.' WHERE created >= ? AND created < ? AND state = ?',$delivery_dateSince,$delivery_dateT0,$state);

        if($response['total']!=null){
            return $response['total'];
        }else{
            $response['total']=0;
            return $response['total'];
        }
    }


    function amountOrders($delivery_dateSince,$delivery_dateT0,$state){
        $response = $this->getDb()->fetch_row('SELECT SUM(id) AS total FROM '.$this->tableName.' WHERE delivery_date >= ? AND delivery_date < ? AND state = ?',$delivery_dateSince,$delivery_dateT0,$state);

        if($response['total']!=null){
            return $response['total'];
        }else{
            $response['total']=0;
            return $response['total'];
        }
    }


    function count($date,$dateTo,$state){
        $response = $this->getDb()->fetch_row('SELECT COUNT(id) AS total FROM '.$this->tableName.' WHERE delivery_date < ? AND delivery_date >= ? AND state = ?',$dateTo,$date,$state);

        if($response['total']!=null){
            return $response['total'];
        }else{
            $response['total']=0;
            return $response['total'];
        }
    }

    function countTotalPendientOrders($state){
        $response = $this->getDb()->fetch_row('SELECT COUNT(id) AS total FROM '.$this->tableName.' WHERE state = ?',$state);

        if($response['total']!=null){
            return $response['total'];
        }else{
            $response['total']=0;
            return $response['total'];
        }
    }

}