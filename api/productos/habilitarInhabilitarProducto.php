<?php
    header('Access-Control-Allow-Origin: *');
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: PUT");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: *"); //To allow for sending custom headers


    //Include database and classes files
    include_once '../../config/database.php';
    include_once '../../clases/Producto.php';

    //COMPROBAMOS QUE EL METODO USADO SEA put
    if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
        exit;
    }


    $data = json_decode(file_get_contents("php://input"));
    $mensaje = '';
    $exito = false;
    $code_error = null;

    //Instantiate database
    $database = new Database();
    $db = $database->getConnection();
    
    $productoC = new Producto($db);

    function esValido($d,&$mensaje){
        if(!isset($d->PRO_ID)){
            $mensaje = "la variable PRO_ID no ha sido enviada.";
            return false;
        }else{  
            if($d->PRO_ID == ""){
                $mensaje = "la variable PRO_ID no puede estar vacía o ser null.";
                return false; 
            }else{
                if(!is_numeric($d->PRO_ID)){
                   $mensaje = "la variable PRO_ID solo acepta caracteres numéricos.";
                   return false;  
                }else{
                    if($d->PRO_ID < 1 ){
                        $mensaje = "la variable PRO_ID no puede ser menor o igual a 0.";
                        return false; 
                    }
                }
            }
        }
        
        if(!isset($d->PRO_ESTADO)){
            $mensaje = "la variable PRO_ESTADO no ha sido enviada.";
            return false;
        }else{  
            if($d->PRO_ESTADO == ""){
                $mensaje = "la variable PRO_ESTADO no puede estar vacía o ser null.";
                return false; 
            }else{
                if(!is_numeric($d->PRO_ESTADO)){
                   $mensaje = "la variable PRO_ESTADO solo acepta caracteres numéricos.";
                   return false;  
                }else{
                    if($d->PRO_ESTADO < 1  || $d->PRO_ESTADO > 2){
                        $mensaje = "la variable PRO_ESTADO no puede ser menor a 1 o mayor a 2.";
                        return false; 
                    }
                }
            }
        }
        return true ; 
    }

    if(esValido($data,$mensaje)){
        $productoC->PRO_ID = $data->PRO_ID;
        $productoC->PRO_ESTADO = $data->PRO_ESTADO;
        $exito =  $productoC->habilitarInhabilitarProducto($mensaje,$code_error);
        if($exito == true)
            header('HTTP/1.1 200 OK');
        else{
            header('HTTP/1.1 400 Bad Request');
        }
            
        echo json_encode( array("error"=>$code_error,"mensaje"=>$mensaje,"exito"=>$exito));
    }else{
        $code_error = "error_deCampo";
        echo json_encode(array("error"=>$code_error,"mensaje"=>$mensaje, "exito"=>false));
        header('HTTP/1.1 400 Bad Request');
    }

?>