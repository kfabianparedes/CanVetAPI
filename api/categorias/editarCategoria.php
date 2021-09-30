<?php

header('Access-Control-Allow-Origin: *');
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: *"); //To allow for sending custom headers


//Include database and classes files
include_once '../../config/database.php';
include_once '../../clases/Categoria.php';

//COMPROBAMOS QUE EL METODO USADO SEA GET
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

$categoriaC = new Categoria($db);

function esValido($d,&$mensaje){


    if(!isset($d->CAT_NUEVO_NOMBRE)){
        $mensaje = "la variable CAT_NUEVO_NOMBRE no ha sido enviada.";
        return false;
    }else{  
        if($d->CAT_NUEVO_NOMBRE == ""){
            $mensaje = "la variable CAT_NUEVO_NOMBRE no puede estar vacía o ser null.";
            return false; 
        }else{
            if(!preg_match("/^[a-zA-Z ñÑáéíóúÁÉÍÓÚ]+$/i", $d->CAT_NUEVO_NOMBRE)){
               $mensaje = "la variable CAT_NUEVO_NOMBRE no acepta caracteres numéricos.";
               return false;  
            }
        }
    }


    if(!isset($d->CAT_NOMBRE)){
        $mensaje = "la variable CAT_NOMBRE no ha sido enviada.";
        return false;
    }else{  
        if($d->CAT_NOMBRE == ""){
            $mensaje = "la variable CAT_NOMBRE no puede estar vacía o ser null.";
            return false; 
        }else{
            if(!preg_match("/^[a-zA-Z ñÑáéíóúÁÉÍÓÚ]+$/i", $d->CAT_NOMBRE)){
               $mensaje = "la variable CAT_NOMBRE no acepta caracteres numéricos.";
               return false;  
            }
        }
    }

    

    if(!isset($d->CAT_ID)){
        $mensaje = "la variable CAT_ID no ha sido enviada.";
        return false;
    }else{  
        if($d->CAT_ID == ""){
            $mensaje = "la variable CAT_ID no puede estar vacía o ser null.";
            return false; 
        }else{
            if(!is_numeric($d->CAT_ID)){
               $mensaje = "la variable CAT_ID solo acepta caracteres numéricos.";
               return false;  
            }else{
                if($d->CAT_ID < 1 ){
                    $mensaje = "la variable CAT_ID no puede ser menor o igual a 0.";
                    return false; 
                }
            }
        }
    }
    return true ; 
}   

if(esValido($data,$mensaje)){
    $categoriaC->CAT_NOMBRE = $data->CAT_NOMBRE;
    $categoriaC->CAT_ID = $data->CAT_ID;
    $exito =  $categoriaC->editarCategoria($mensaje,$code_error,$data->CAT_NUEVO_NOMBRE);
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