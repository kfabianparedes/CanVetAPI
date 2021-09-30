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
    $productoL = [];


    function esValido(&$m){ 
        
        if(!isset($_GET['PROV_ID'])){
            $m = "El campo PROV_ID no ha sido enviado";
            return false;
        }else{
            if($_GET['PROV_ID'] == ''){
                $m = "El campo PROV_ID no puede estar vacío o ser null.";
                return false;
            }else{
                if(!is_numeric($_GET['PROV_ID'])){
                    $m = "El campo PROV_ID debe ser numérico";
                    return false;
                }else{
                    if($_GET['PROV_ID'] <=0){
                        $m = "El valor de PROV_ID debe no debe ser negativo o igual a 0.";
                        return false;
                    }
                }
            }
            
            
        }
        return true;
    }


    if(esValido($mensaje)){

        $productoC->PROV_ID = $_GET['PROV_ID'];
        $productoL = $productoC->listarProductosPorProveedor($mensaje, $exito, $code_error);  
        if($exito==true){
            header('HTTP/1.1 200 OK');
            echo json_encode( array("error"=>$code_error, "resultado"=>$productoL, "mensaje"=>$mensaje,"exito"=>true));
        }else{
            header('HTTP/1.1 400 Bad Request');
            echo json_encode( array("error"=>$code_error, "resultado"=>$productoL, "mensaje"=>$mensaje,"exito"=>false));
        }

    }else{

        $code_error = "error_deCampo";
        echo json_encode(array("error"=>$code_error,"mensaje"=>$mensaje, "exito"=>false));
        header('HTTP/1.1 400 Bad Request');

    }
    
?>