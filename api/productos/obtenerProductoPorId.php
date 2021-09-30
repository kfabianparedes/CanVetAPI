<?php
    header('Access-Control-Allow-Origin: *'); //Change
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: GET");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    include_once '../../clases/Producto.php';
    include_once '../../config/database.php';


    if ($_SERVER['REQUEST_METHOD'] != 'GET') {
        $code_error="error_requestMethodInvalid";
        $mensaje = "El tipo de petición no es la correcta";
        header('HTTP/1.0  405 Method Not Allowed');
        echo json_encode(array("error"=>$code_error, "mensaje"=>$mensaje,"exito"=>false));
    }

    $database = new Database();
    $db = $database->getConnection();
    $productoC = new Producto($db);

    $mensaje = '';
    $exito = false;
    $code_error = null;


    function esValido(&$m){ 
        
        if(!isset($_GET['PRO_ID'])){
            $m = "El campo PRO_ID no ha sido enviado";
            return false;
        }else{
            if($_GET['PRO_ID'] == ''){
                $m = "El campo PRO_ID no puede estar vacío o ser null.";
                return false;
            }else{
                if(!is_numeric($_GET['PRO_ID'])){
                    $m = "El campo PRO_ID debe ser numérico";
                    return false;
                }else{
                    if($_GET['PRO_ID'] <=0){
                        $m = "El valor de PRO_ID debe no debe ser negativo o igual a 0.";
                        return false;
                    }
                }
            }
            
            
        }
        return true;
    }


    if(esValido($mensaje)){

        $productoC->PRO_ID = $_GET['PRO_ID'];
        $productoC = $productoC->obtenerProductoPorId($mensaje, $exito, $code_error);  
        if($exito==true){
            header('HTTP/1.1 200 OK');
            echo json_encode( array("error"=>$code_error, "resultado"=>$productoC, "mensaje"=>$mensaje,"exito"=>true));
        }else{
            header('HTTP/1.1 400 Bad Request');
            echo json_encode( array("error"=>$code_error, "resultado"=>$productoC, "mensaje"=>$mensaje,"exito"=>false));
        }

    }else{

        $code_error = "error_deCampo";
        echo json_encode(array("error"=>$code_error,"mensaje"=>$mensaje, "exito"=>false));
        header('HTTP/1.1 400 Bad Request');

    }

?>