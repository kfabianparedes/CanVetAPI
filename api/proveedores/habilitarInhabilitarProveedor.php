<?php
    header('Access-Control-Allow-Origin: *');
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: PUT");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: *"); //To allow for sending custom headers


    //Include database and classes files
    include_once '../../config/database.php';
    include_once '../../clases/Proveedor.php';

    //COMPROBAMOS QUE EL METODO USADO SEA put
    if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
        return;
    }


    $data = json_decode(file_get_contents("php://input"));
    $mensaje = '';
    $exito = false;
    $code_error = null;

    //Instantiate database
    $database = new Database();
    $db = $database->getConnection();
    
    $proveedorC = new Proveedor($db);

    function esValido($d,&$mensaje){
        if(!isset($d->PROV_ID)){
            $mensaje = "la variable PROV_ID no ha sido enviada.";
            return false;
        }else{  
            if($d->PROV_ID == ""){
                $mensaje = "la variable PROV_ID no puede estar vacía o ser null.";
                return false; 
            }else{
                if(!is_numeric($d->PROV_ID)){
                   $mensaje = "la variable PROV_ID solo acepta caracteres numéricos.";
                   return false;  
                }else{
                    if($d->PROV_ID < 1 ){
                        $mensaje = "la variable PROV_ID no puede ser menor o igual a 0.";
                        return false; 
                    }
                }
            }
        }
        
        if(!isset($d->PROV_ESTADO)){
            $mensaje = "la variable PROV_ESTADO no ha sido enviada.";
            return false;
        }else{  
            if($d->PROV_ESTADO == ""){
                $mensaje = "la variable PROV_ESTADO no puede estar vacía o ser null.";
                return false; 
            }else{
                if(!is_numeric($d->PROV_ESTADO)){
                   $mensaje = "la variable PROV_ESTADO solo acepta caracteres numéricos.";
                   return false;  
                }else{
                    if($d->PROV_ESTADO < 1  || $d->PROV_ESTADO > 2){
                        $mensaje = "la variable PROV_ESTADO no puede ser menor a 1 o mayor a 2.";
                        return false; 
                    }
                }
            }
        }
        return true ; 
    }

    if(esValido($data,$mensaje)){
        $proveedorC->PROV_ID = $data->PROV_ID;
        $proveedorC->PROV_ESTADO = $data->PROV_ESTADO;
        $exito =  $proveedorC->habilitarInhabilitarProveedor($mensaje,$code_error);
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