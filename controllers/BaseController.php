<?php
/**
 * Created by PhpStorm.
 * User: leila
 * Date: 20/05/2020
 * Time: 10:36
 */
date_default_timezone_set('UTC');
define('PAGE_SIZE',30);
class BaseController
{
    protected $model;

    function __construct()
    {
    }

    function getModel(){
        return $this->model;
    }

    function getPaginator(){
        $paginator = array('offset' => 0, 'limit' => PAGE_SIZE);
        if(isset($_GET['page'])){
            $paginator['offset'] = PAGE_SIZE * $_GET['page'];
        }
        return $paginator;
    }

    function getFilters(){
        $filters= array();

        if(isset($_GET['order_id'])){
            $filters[] = 'order_id = "'.$_GET['order_id'].'"';
        }
        if(isset($_GET['since'])){
            $filters[] = 'created >= "'.$_GET['since'].'"';
        }

        if(isset($_GET['observation'])){
            $filters[] = 'observation = "'.$_GET['observation'].'"';
        }

        if(isset($_GET['state'])){
            $filters[] = 'state = "'.$_GET['state'].'"';
        }

        return $filters;
    }

    function validateId(){
        return isset($_GET['id']);
    }

    function method(){
        if(method_exists($this,$_GET['method'])){
            $this->{$_GET['method']}();
        }else {
            $this->returnError(404, "CONTROLLER METHOD NOT FOUND");
        }
    }



    /* function get(){

         if($this->validateId()){
             $entity = $this->getModel()->findById($_GET['id']);
             if(!empty($entity)){
                 $this->returnSuccess(200,$entity);
             }else{
                 $this->returnError(404,"ENTITY NOT FOUND");
             }
         }else{
             $this->returnSuccess(200,$this->getModel()->findAll($this->getFilters(),$this->getPaginator()));
         }
     }*/

    function get(){
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
            $this->returnSuccess(200,$this->getModel()->findAll($this->getFilters(),$this->getPaginator()));
        }
    }


    function post(){
        $data = (array)json_decode(file_get_contents("php://input"));
        unset($data['id']);
        $res = $this->getModel()->save($data);
        if($res<0){
            $this->returnError(404,null);
        }else{
            $inserted = $this->getModel()->findById($res);
            $this->returnSuccess(201,$inserted);
        }
    }

    function put(){
        $data = (array) json_decode(file_get_contents("php://input"));

        if(isset($data['id'])){

            $id = $data['id'];
            unset($data['id']);

            $object=$this->getModel()->findById($id);
            if($object){

                $this->getModel()->update($id,$data);

                $updated=$this->getModel()->findById($id);

                $this->returnSuccess(200,$updated);
            }else{
                $this->getModel()->save($data);
                $this->returnSuccess(201,$data);
            }
        }else{
            $this->getModel()->save($data);
            $this->returnSuccess(201,$data);
        }
    }

    function delete(){
        if($this->getModel()->delete($_GET['id'])){
            $this->returnSuccess(204,null);
        }else{
            $this->returnError(404,"ENTITY #".$_GET['id']." NOT FOUND");
        }
    }

    function validateCreated(){
        return isset($_GET['created']);
    }

    private function returnJson($code, $data=null){
        http_response_code($code);
        header('Content-Type: application/json');
        if($data!=null){
            echo json_encode($data);
        }
    }

    function returnSuccess($code,$data){
        $this->returnJson($code,array('result'=>'success', 'data'=>$data));
    }

    function returnError($code,$message){
        $this->returnJson($code,array('result'=>'error', 'message'=>$message));
    }

    function returnCreated(){
        http_response_code(201);
        header('Content-Type: application/json');
    }
}