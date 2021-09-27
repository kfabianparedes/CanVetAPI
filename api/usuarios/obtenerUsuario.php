<?php
    header('Access-Control-Allow-Origin: *'); //Change
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: GET");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    include_once '../../clases/Usuario.php';
    include_once '../../config/database.php';


    if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
        return;
    }

    $database = new Database();
    $db = $database->getConnection();
    $usuario = new Usuario($db);

    $mensaje = '';
    $exito = false;
    $code_error = null;
    $user = null;

    function esValido(&$m){ 
        
        if(!isset($_GET['USU_ID'])){
            $m = "El campo USU_ID no ha sido enviado";
            return false;
        }else{
            if($_GET['USU_ID'] == ''){
                $m = "El campo USU_ID no puede estar vacío o ser null.";
                return false;
            }else{
                if(!is_numeric($_GET['USU_ID'])){
                    $m = "El campo USU_ID debe ser numérico";
                    return false;
                }else{
                    if($_GET['USU_ID'] <=0){
                        $m = "El valor de USU_ID debe no debe ser negativo o igual a 0.";
                        return false;
                    }
                }
            }
            
            
        }
        return true;
    }

    if(esValido($mensaje)){
        $usuario->USU_ID = $_GET['USU_ID'];
        $user = $usuario->obtenerUsuario($mensaje, $exito, $code_error);  
        if($exito){
            header('HTTP/1.1 200 OK');
            echo json_encode( array("error"=>$code_error, "user"=>$user, "mensaje"=>$mensaje,"exito"=>true));
        }else{
            header('HTTP/1.1 400 Bad Request');
            echo json_encode( array("error"=>$code_error, "user"=>$user, "mensaje"=>$mensaje,"exito"=>false));
        }
    }else{
        $code_error = "error_deCampo";
        echo json_encode(array("error"=>$code_error,"mensaje"=>$mensaje, "exito"=>false));
        header('HTTP/1.1 400 Bad Request');
    }
?>