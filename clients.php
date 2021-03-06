<?php
/**
 * Created by PhpStorm.
 * User: leila
 * Date: 20/05/2020
 * Time: 10:45
 */
include 'controllers/ClientsController.php';


$controller = new ClientsController();

$method = $_SERVER['REQUEST_METHOD'];


switch ($method) {
    case 'GET':
        $controller->get();
        break;
    case 'POST':
        $controller->post();
        break;
    case 'DELETE':
        $controller->delete();
        break;
    case 'PUT':
        $controller->put();
        break;
    default:
        $controller->returnError(400,'INVALID METHOD');
        break;
}