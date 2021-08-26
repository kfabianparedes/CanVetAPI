<?php
    header('Access-Control-Allow-Origin: *');
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: *"); //To allow for sending custom headers


    //Include database and classes files
    include_once '../../config/database.php';
    include_once '../../clases/Producto.php';

    //COMPROBAMOS QUE EL METODO USADO SEA GET
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

        if(!isset($d->PRO_TAMANIO_TALLA)){
            $mensaje = "la variable PRO_TAMANIO_TALLA no ha sido enviada.";
            return false;
        }else{  
            if($d->PRO_TAMANIO_TALLA == ""){
                $mensaje = "la variable PRO_TAMANIO_TALLA no puede estar vacía o ser null.";
                return false; 
            }
        }

        if(!isset($d->PRO_NOMBRE)){
            $mensaje = "la variable PRO_NOMBRE no ha sido enviada.";
            return false;
        }else{  
            if($d->PRO_NOMBRE == ""){
                $mensaje = "la variable PRO_NOMBRE no puede estar vacía o ser null.";
                return false; 
            }else{
                if(!preg_match("/^[a-zA-Z ñÑáéíóúÁÉÍÓÚ]+$/i", $d->PRO_NOMBRE)){
                   $mensaje = "la variable PRO_NOMBRE no acepta caracteres numéricos.";
                   return false;  
                }
            }
        }
        

        if(!isset($d->PRO_CODIGO)){
            $mensaje = "la variable PRO_CODIGO no ha sido enviada.";
            return false;
        }else{  
            if($d->PRO_CODIGO == ""){
                $mensaje = "la variable PRO_CODIGO no puede estar vacía o ser null.";
                return false; 
            }
                
        }
        if(!isset($d->PRO_PRECIO_VENTA)){
            $m = 'La variable PRO_PRECIO_VENTA no ha sido enviada.';
            return false;
        }else{
            if(ctype_digit($d->PRO_PRECIO_VENTA) || is_numeric($d->PRO_PRECIO_VENTA)){
                if($d->PRO_PRECIO_VENTA <= 0) { 
                    $m = 'El valor de la variable PRO_PRECIO_VENTA debe ser mayor a 0.';
                    return false;
                }
            }else{
                $m = 'La variable PRO_PRECIO_VENTA no es un numero o es null.';
                return false;
            }
        }
        if(!isset($d->PRO_PRECIO_COMPRA)){
            $m = 'La variable PRO_PRECIO_COMPRA no ha sido enviada.';
            return false;
        }else{
            if(ctype_digit($d->PRO_PRECIO_COMPRA) || is_numeric($d->PRO_PRECIO_COMPRA)){
                if($d->PRO_PRECIO_COMPRA <= 0) { 
                    $m = 'El valor de la variable PRO_PRECIO_COMPRA debe ser mayor a 0.';
                    return false;
                }
            }else{
                $m = 'La variable PRO_PRECIO_COMPRA no es un numero o es null.';
                return false;
            }
        }
        if(!isset($d->PRO_STOCK)){
            $m = 'La variable PRO_STOCK no ha sido enviada.';
            return false;
        }else{
            if(ctype_digit($d->PRO_STOCK) || is_numeric($d->PRO_STOCK)){
                if($d->PRO_STOCK < 0) { 
                    $m = 'El valor de la variable PRO_STOCK no puede ser menor que 0.';
                    return false;
                }
            }else{
                $m = 'La variable PRO_STOCK no es un numero o es null.';
                return false;
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
        
        $productoC->PRO_NOMBRE = $data->PRO_NOMBRE;
        $productoC->PRO_CODIGO = $data->PRO_CODIGO;
        $productoC->PRO_PRECIO_VENTA = $data->PRO_PRECIO_VENTA;
        $productoC->PRO_TAMANIO_TALLA = $data->PRO_TAMANIO_TALLA;
        $productoC->PRO_PRECIO_COMPRA = $data->PRO_PRECIO_COMPRA;
        $productoC->PRO_STOCK = $data->PRO_STOCK;
        $productoC->CAT_ID = $data->CAT_ID;
        $exito =  $productoC->crearProducto($mensaje,$code_error);
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