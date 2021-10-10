<?php
    header('Access-Control-Allow-Origin: *');
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: *"); //To allow for sending custom headers


    //Include database and classes files
    include_once '../../config/database.php';
    include_once '../../clases/Compra.php';
    include_once '../../clases/DetalleCompra.php';
    include_once '../../clases/Usuario.php';
    include_once '../../clases/Autorizacion.php';
    include_once '../../util/validaciones.php';

    //COMPROBAMOS QUE EL METODO USADO SEA GET
    if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
        return;
    }


    
    $mensaje = '';
    $exito = false;
    $code_error = null;
    $hay_guia = 0;
    $GUIA_NRO_SERIE = '';
    $GUIA_NRO_COMPROBANTE = '';
    $P_GUIA_FECHA_EMISION = '';
    $Compra_id = '';
    //Autorización
    $headers = apache_request_headers();
    $auth = new Autorizacion();

    foreach ($headers as $header => $value) {
        if(strtolower($header) == $auth->FIRST_HEADER){//se compara si existe la cabecera authorization
            $auth->USE_SUB = $value;//se obtiene el valor
            $auth->FIRST_HEADER = "";//se limpia la variable para que dentro del for no se vuelva a comparar
            $auth->HEADER_COUNT += 1;//se suma uno cuando se encuentra la cabecera
        }

        if(strtolower($header)==$auth->SECOND_HEADER){//se compara si existe la cabecera user
            $auth->TYPE_USER = $value;// se obtiene el valor
            $auth->SECOND_HEADER = "";//se limpia la variable para que dentro del for no se vuelva a comparar
            $auth->HEADER_COUNT += 1;//se suma uno cuando se encuentra la cabecera
        }

        if($auth->HEADER_COUNT == 2)//si es 2 es porque se encontraron las 2 cabeceras
            break;
    }

    if($auth->HEADER_COUNT != 2){//si no se encontraron las 2 cabeceras
        $code_error = "error_autorizacion";
        $mensaje = 'Hubo un error de autorización';
        $exito = false;
        echo json_encode(array("error"=>$code_error,"mensaje"=>$mensaje, "exito"=>false));
        header('HTTP/1.0 401 Unauthorized');
    }else{
        if(!isset($auth->TYPE_USER)){
            if($auth->TYPE_USER == ''){
                $code_error = "error_autorizacion";
                $mensaje = 'Hubo un error de autorización';
                $exito = false;
                echo json_encode(array("error"=>$code_error,"mensaje"=>$mensaje, "exito"=>false));
                header('HTTP/1.0 401 Unauthorized');
            }
        }
    }

    if($auth->USE_SUB!=""){
        # Basic 29$$101109112108101$2y$10$YYKcURLLTlYGMuKVTkklVeUVfXtpzUAwbRL35P03P1vNQjo91NaYW97100105116111
        $VALUE_HEADER_USE_SUB = explode(' ',$auth->USE_SUB); // Lo trasforma en un arreglo todo lo que este separado el parametro ingresado => ''
        # $VALUE_HEADER_USE_SUB[0] = Basic
        # $VALUE_HEADER_USE_SUB[1] = 29$$101109112108101$2y$10$YYKcURLLTlYGMuKVTkklVeUVfXtpzUAwbRL35P03P1vNQjo91NaYW97100105116111
        
        $TOKEN_USE_SUB = explode('$$',$VALUE_HEADER_USE_SUB[1]);//el segundo es todo el texto no legible
        # TOKEN_USE_SUB[0] = 29
        # TOKEN_USE_SUB[1] = 101109112108101$2y$10$YYKcURLLTlYGMuKVTkklVeUVfXtpzUAwbRL35P03P1vNQjo91NaYW97100105116111
        $VALID_TOKEN = (is_numeric($TOKEN_USE_SUB[0]))? true:false;//verifico si los caracteres antes del separador '$$' sean numericos

        if($VALID_TOKEN){
            # TOKEN_USE_SUB[0] = 29
            $FIRST_HALF_TOKEN_0 = round(intval($TOKEN_USE_SUB[0])/2);//dividir el numero inicial del valor de la cabecera a la mitad (redondeado)
            # TOKEN_USE_SUB[0])/2 => 29 / 2 => 14.5 => round(14.5) => 15
            # FIRST_HALF_TOKEN_0 = 15

            $SECOND_HALF_TOKEN_0 = intval($TOKEN_USE_SUB[0]) - $FIRST_HALF_TOKEN_0;
            # SECOND_HALF_TOKEN = 29 - 15 = 14;
            
            # TOKEN_USE_SUB[1] = 101109112108101$2y$10$YYKcURLLTlYGMuKVTkklVeUVfXtpzUAwbRL35P03P1vNQjo91NaYW97100105116111
            $TOKEN = substr($TOKEN_USE_SUB[1],$FIRST_HALF_TOKEN_0,-$SECOND_HALF_TOKEN_0);//Saca un substring desde el indice FIRST_HALF_TOKEN_0 hasta el SECOND_HALF_TOKEN_0 (De derecha a izquierda por ser negativo)
            # FIRST_HALF_TOKEN_0 = 15 -> 101109112108101 ****** $2y$10$YYKcURLLTlYGMuKVTkklVeUVfXtpzUAwbRL35P03P1vNQjo91NaYW97100105116111
            # SECOND_HALF_TOKEN_0 = -14 -> $2y$10$YYKcURLLTlYGMuKVTkklVeUVfXtpzUAwbRL35P03P1vNQjo91NaYW ****** 97100105116111
            # TOKEN = $2y$10$YYKcURLLTlYGMuKVTkklVeUVfXtpzUAwbRL35P03P1vNQjo91NaYW (La contraseña hasheada del usuario)
            

            if($auth->TYPE_USER == $auth->AUTH_ADM){
                $database = new Database();
                $db = $database->getConnection();
                $usuario = new Usuario($db);
                $exito_verify = $usuario->tokenVerify($TOKEN,$auth->ROL,$code_error,$mensaje);
                // echo json_encode(array("exito verify"=>$exito_verify,"rol"=>$auth->ROL,"error"=>$code_error,"mensaje"=>$mensaje));
                if($exito_verify && $auth->ROL == 2){
                    $exito = true;
                }else{
                    $code_error = "error_autorizacion";
                    $mensaje = 'Hubo un error de autorización, el usuario no es administrador.';
                    $exito = false;
                    echo json_encode(array("error"=>$code_error,"mensaje"=>$mensaje, "exito"=>false));
                    header('HTTP/1.0 401 Unauthorized');
                }
            }else{
                $code_error = "error_autorizacion";
                $mensaje = 'Hubo un error de autorización, no se envió código de autorización de administrador.';
                $exito = false;
                echo json_encode(array("error"=>$code_error,"mensaje"=>$mensaje, "exito"=>false));
                header('HTTP/1.0 401 Unauthorized');
            }
        }
        else{
            $code_error = "error_autorizacion";
            $mensaje = 'Hubo un error de autorización, error verificando el token.';
            $exito = false;
            echo json_encode(array("error"=>$code_error,"mensaje"=>$mensaje, "exito"=>false));
            header('HTTP/1.0 401 Unauthorized');
        }

    }else{
        $code_error = "error_autorizacion";
        $mensaje = 'Hubo un error de autorización, token de verificación vacío.';
        $exito = false;
        echo json_encode(array("error"=>$code_error,"mensaje"=>$mensaje, "exito"=>false));
        header('HTTP/1.0 401 Unauthorized');
    }
    
    if($exito){
        $datos = json_decode(file_get_contents("php://input"));
        if(esValido($mensaje,$datos,$hay_guia)){
            $COMPRA = $datos->COMPRA;
            $GUIA_DE_REMISION = $datos->GUIA_DE_REMISION;

            $db->begin_transaction(); // INICIO DE LAS TRANSACCIONES. 

        }else{
    
            $code_error = "error_deCampo";
            echo json_encode(array("error"=>$code_error,"mensaje"=>$mensaje, "exito"=>false));
            header('HTTP/1.1 400 Bad Request');
        
        }
    }
    
    function esValido(&$m, $d,&$hay_guia){
        

        if(!isset($d->COMPRA)){
            $m = "la variable COMPRA  no ha sido enviada.";
            return false;
        }else{
            $COMPRA = $d->COMPRA;
            //validaciones de la variable USU_ID
            if(!isset($COMPRA->USU_ID)){
                $m = "la variable USU_ID no ha sido enviada.";
                return false;
            }else{  
                if($COMPRA->USU_ID == ""){
                    $m = "la variable USU_ID no puede estar vacía o ser null.";
                    return false; 
                }else{
                    if(!is_numeric($COMPRA->USU_ID)){
                    $m = "la variable USU_ID solo acepta caracteres numéricos.";
                    return false;  
                    }else{
                        if($COMPRA->USU_ID < 1 ){
                            $m = "la variable USU_ID no puede ser menor o igual a 0.";
                            return false; 
                        }
                    }
                }
            }

            //validaciones de la variable COMPROBANTE_ID
            if(!isset($COMPRA->COMPROBANTE_ID)){
                $m = "la variable COMPROBANTE_ID no ha sido enviada.";
                return false;
            }else{  
                if($COMPRA->COMPROBANTE_ID == ""){
                    $m = "la variable COMPROBANTE_ID no puede estar vacía o ser null.";
                    return false; 
                }else{
                    if(!is_numeric($COMPRA->COMPROBANTE_ID)){
                    $m = "la variable COMPROBANTE_ID solo acepta caracteres numéricos.";
                    return false;  
                    }else{
                        if($COMPRA->COMPROBANTE_ID < 1 ){
                            $m = "la variable COMPROBANTE_ID no puede ser menor o igual a 0.";
                            return false; 
                        }
                    }
                }
            }

            //validaciones de la variable PROV_ID
            if(!isset($COMPRA->PROV_ID)){
                $m = "la variable PROV_ID no ha sido enviada.";
                return false;
            }else{  
                if($COMPRA->PROV_ID == ""){
                    $m = "la variable PROV_ID no puede estar vacía o ser null.";
                    return false; 
                }else{
                    if(!is_numeric($COMPRA->PROV_ID)){
                    $m = "la variable PROV_ID solo acepta caracteres numéricos.";
                    return false;  
                    }else{
                        if($COMPRA->PROV_ID < 1 ){
                            $m = "la variable PROV_ID no puede ser menor o igual a 0.";
                            return false; 
                        }
                    }
                }
            }

            //validación de la variable COMPRA_FECHA_EMISION_COMPROBANTE
            if(!isset($COMPRA->COMPRA_FECHA_EMISION_COMPROBANTE)){
                $m = "La variable COMPRA_FECHA_EMISION_COMPROBANTE no ha sido enviada.";
                return false; 
            }else{
                if(($COMPRA->COMPRA_FECHA_EMISION_COMPROBANTE=="")){
                    $m = "La variable COMPRA_FECHA_EMISION_COMPROBANTE no puede ser null.";
                    return false;
                }else{
                    if(!verificarFecha($COMPRA->COMPRA_FECHA_EMISION_COMPROBANTE)){
                        $m = "La variable COMPRA_FECHA_EMISION_COMPROBANTE no contiene una fecha válida o no tiene el formato permitido.";
                        return false;
                    }else{
                        if(!esMenorFechaActual($COMPRA->COMPRA_FECHA_EMISION_COMPROBANTE)){
                            $m = "La variable COMPRA_FECHA_EMISION_COMPROBANTE no puede ser mayor a la fecha actual.";
                            return false;
                        }
                    }
                }
            }

            //validación de la variable COMPRA_FECHA_REGISTRO
            if(!isset($COMPRA->COMPRA_FECHA_REGISTRO)){
                $m = "La variable COMPRA_FECHA_REGISTRO no ha sido enviada.";
                return false; 
            }else{
                if(($COMPRA->COMPRA_FECHA_REGISTRO=="")){
                    $m = "La variable COMPRA_FECHA_REGISTRO no puede ser null.";
                    return false;
                }else{
                    if(!verificarFecha($COMPRA->COMPRA_FECHA_REGISTRO)){
                        $m = "La variable COMPRA_FECHA_REGISTRO no contiene una fecha válida o no tiene el formato permitido.";
                        return false;
                    }else{
                        if(!esMenorFechaActual($COMPRA->COMPRA_FECHA_REGISTRO)){
                            $m = "La variable COMPRA_FECHA_REGISTRO no puede ser mayor a la fecha actual.";
                            return false;
                        }
                    }
                }
            }

            //validaciones de la variable COMPRA_NRO_SERIE
            if(!isset($COMPRA->COMPRA_NRO_SERIE)){
                $m = "la variable COMPRA_NRO_SERIE no ha sido enviada.";
                return false;
            }else{  
                if($COMPRA->COMPRA_NRO_SERIE == ""){
                    $m = "la variable COMPRA_NRO_SERIE no puede estar vacía o ser null.";
                    return false; 
                }else{
                    if(!ctype_digit($COMPRA->COMPRA_NRO_SERIE)){
                        $m = "La variable COMPRA_NRO_SERIE debe estar conformada por caracteres numéricos.";
                        return false;
                    }else{
                        if(obtenerCantidadDeCaracteres($COMPRA->COMPRA_NRO_SERIE)!=5){
                            $m = "La variable COMPRA_NRO_SERIE debe tener una longitud de 5 caracteres numericos.";
                            return false;
                        }
                    }
                }
            }
            //validaciones de la variable COMPRA_NRO_COMPROBANTE
            if(!isset($COMPRA->COMPRA_NRO_COMPROBANTE)){
                $m = "la variable COMPRA_NRO_COMPROBANTE no ha sido enviada.";
                return false;
            }else{  
                if($COMPRA->COMPRA_NRO_COMPROBANTE == ""){
                    $m = "la variable COMPRA_NRO_COMPROBANTE no puede estar vacía o ser null.";
                    return false; 
                }else{
                    if(!ctype_digit($COMPRA->COMPRA_NRO_COMPROBANTE)){
                        $m = "La variable COMPRA_NRO_COMPROBANTE debe estar conformada por caracteres numéricos.";
                        return false;
                    }else{
                        if(obtenerCantidadDeCaracteres($COMPRA->COMPRA_NRO_COMPROBANTE)!=10){
                            $m = "La variable COMPRA_NRO_COMPROBANTE debe tener una longitud de 5 caracteres numericos.";
                            return false;
                        }
                    }
                }
            }

            //validaciones de la variable COMPRA_SUBTOTAL
            if(!isset($COMPRA->COMPRA_SUBTOTAL)){
                $m = "la variable COMPRA_SUBTOTAL no ha sido enviada.";
                return false;
            }else{  
                if(ctype_digit($COMPRA->COMPRA_SUBTOTAL) || is_numeric($COMPRA->COMPRA_SUBTOTAL)){
                    if($COMPRA->COMPRA_SUBTOTAL <= 0) { 
                        $m = 'El valor de la variable COMPRA_SUBTOTAL debe ser mayor a 0.';
                        return false;
                    }
                }else{
                    $m = 'La variable COMPRA_SUBTOTAL no es un numero o es null.';
                    return false;
                }
            }

            //validaciones de la variable COMPRA_TOTAL
            if(!isset($COMPRA->COMPRA_TOTAL)){
                $m = "la variable COMPRA_TOTAL no ha sido enviada.";
                return false;
            }else{  
                if(ctype_digit($COMPRA->COMPRA_TOTAL) || is_numeric($COMPRA->COMPRA_TOTAL)){
                    if($COMPRA->COMPRA_TOTAL <= 0) { 
                        $m = 'El valor de la variable COMPRA_TOTAL debe ser mayor a 0.';
                        return false;
                    }
                }else{
                    $m = 'La variable COMPRA_TOTAL no es un numero o es null.';
                    return false;
                }
            }

            //validaciones de la descripción de la compra
            if(isset($COMPRA->COMPRA_DESCRIPCION)){
                if(obtenerCantidadDeCaracteres($COMPRA->COMPRA_DESCRIPCION)>200){
                    $m = "La variable COMPRA_DESCRIPCION supera los 200 caracteres permitidos.";
                    return false;
                }
            }
        }

        //validaciones de la guía de remisión
        if(isset($d->GUIA_DE_REMISION)){
            $hay_guia = 1; 
            $GUIA_DE_REMISION = $d->GUIA_DE_REMISION;

            //validaciones de la variable GUIA_NRO_SERIE
            if(!isset($GUIA_DE_REMISION->GUIA_NRO_SERIE)){
                $m = "la variable GUIA_NRO_SERIE no ha sido enviada.";
                return false;
            }else{  
                if($GUIA_DE_REMISION->GUIA_NRO_SERIE == ""){
                    $m = "la variable GUIA_NRO_SERIE no puede estar vacía o ser null.";
                    return false; 
                }else{
                    if(!ctype_digit($GUIA_DE_REMISION->GUIA_NRO_SERIE)){
                        $m = "La variable GUIA_NRO_SERIE debe estar conformada por caracteres numéricos.";
                        return false;
                    }else{
                        if(obtenerCantidadDeCaracteres($GUIA_DE_REMISION->GUIA_NRO_SERIE)!=5){
                            $m = "La variable GUIA_NRO_SERIE debe tener una longitud de 5 caracteres numericos.";
                            return false;
                        }
                    }
                }
            }
            //validaciones de la variable GUIA_NRO_COMPROBANTE
            if(!isset($GUIA_DE_REMISION->GUIA_NRO_COMPROBANTE)){
                $m = "la variable GUIA_NRO_COMPROBANTE no ha sido enviada.";
                return false;
            }else{  
                if($GUIA_DE_REMISION->GUIA_NRO_COMPROBANTE == ""){
                    $m = "la variable GUIA_NRO_COMPROBANTE no puede estar vacía o ser null.";
                    return false; 
                }else{
                    if(!ctype_digit($GUIA_DE_REMISION->GUIA_NRO_COMPROBANTE)){
                        $m = "La variable GUIA_NRO_COMPROBANTE debe estar conformada por caracteres numéricos.";
                        return false;
                    }else{
                        if(obtenerCantidadDeCaracteres($GUIA_DE_REMISION->GUIA_NRO_COMPROBANTE)!=10){
                            $m = "La variable GUIA_NRO_COMPROBANTE debe tener una longitud de 5 caracteres numericos.";
                            return false;
                        }
                    }
                }
            }
            //validaciones de la variable GUIA_FECHA_EMISION  
            if(!isset($GUIA_DE_REMISION->GUIA_FECHA_EMISION)){
                $m = "La variable GUIA_FECHA_EMISION de la guía de remisión no ha sido enviada.";
                return false; 
            }else{
                if(($GUIA_DE_REMISION->GUIA_FECHA_EMISION=="")){
                    $m = "La variable GUIA_FECHA_EMISION de la guía de remisión no puede ser null.";
                    return false;
                }else{
                    if(!verificarFecha($GUIA_DE_REMISION->GUIA_FECHA_EMISION)){
                        $m = "La variable GUIA_FECHA_EMISION de la guía de remisión no contiene una fecha válida o no tiene el formato permitido.";
                        return false;
                    }else{
                        if(!esMenorFechaActual($GUIA_DE_REMISION->GUIA_FECHA_EMISION)){
                            $m = "La variable GUIA_FECHA_EMISION de la guía de remisión no puede ser mayor a la fecha actual.";
                            return false;
                        }
                    }
                }
            }
        } 
            
       return true;  
    }
?>