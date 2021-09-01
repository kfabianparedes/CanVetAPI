<?php
    header('Access-Control-Allow-Origin: *'); //Change
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: GET");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    include_once '../../clases/Usuario.php';
    include_once '../../clases/Proveedor.php';
    include_once '../../config/database.php';


    if ($_SERVER['REQUEST_METHOD'] != 'GET') {
        $code_error="error_requestMethodInvalid";
        $mensaje = "El tipo de petición no es la correcta";
        header('HTTP/1.0  405 Method Not Allowed');
        echo json_encode(array("error"=>$code_error, "mensaje"=>$mensaje,"exito"=>false));
    }
    $database = new Database();
    $db = $database->getConnection();
    $proveedorC = new Proveedor($db);

    $mensaje = '';
    $exito = false;
    $code_error = null;
    $proveedorL = [];


    $proveedorL = $proveedorC->listarProveedor($mensaje, $exito, $code_error);  
    if($exito==true){
        header('HTTP/1.1 200 OK');
        echo json_encode( array("error"=>$code_error, "resultado"=>$proveedorL, "mensaje"=>$mensaje,"exito"=>true));
    }else{
        header('HTTP/1.1 400 Bad Request');
        echo json_encode( array("error"=>$code_error, "resultado"=>$proveedorL, "mensaje"=>$mensaje,"exito"=>false));
    }
?>