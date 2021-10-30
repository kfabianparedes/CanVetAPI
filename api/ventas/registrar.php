<?php
    header('Access-Control-Allow-Origin: *');
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, User");

    include_once '../../clases/Usuario.php';
    include_once '../../clases/Autorizacion.php';
    include_once '../../config/database.php';
    include_once '../../util/validaciones.php';
    include_once '../../clases/Venta.php';
    include_once '../../clases/DetalleVenta.php';

    if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
        return;
    }
    $VentaId = '';
    $headers = apache_request_headers();
    $auth = new Autorizacion();
    $code_error = null;
    $mensaje = '';
    $exito = false;
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
            

            if($auth->TYPE_USER == $auth->AUTH_ADM || $auth->TYPE_USER == $auth->AUTH_EMP){
                $database = new Database();
                $db = $database->getConnection();
                $usuario = new Usuario($db);
                $exito_verify = $usuario->tokenVerify($TOKEN,$auth->ROL,$code_error,$mensaje);
                // echo json_encode(array("exito verify"=>$exito_verify,"rol"=>$auth->ROL,"error"=>$code_error,"mensaje"=>$mensaje));
                if($exito_verify && ($auth->ROL == 2 || $auth->ROL == 1)){
                    $exito = true;
                }else{
                    $code_error = "error_autorizacion";
                    $mensaje = 'Hubo un error de autorización, el usuario no está autorizado, vuelva a iniciar sesión.';
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
        $ventaC = new Venta($db);

        if(esValido($mensaje,$datos)){
            $VENTA = $datos->VENTA;

            $ventaC->VENTA_FECHA_EMISION_COMPROBANTE = $VENTA->VENTA_FECHA_EMISION_COMPROBANTE;
            $ventaC->VENTA_FECHA_REGISTRO = $VENTA->VENTA_FECHA_REGISTRO;
            $ventaC->VENTA_NRO_SERIE = $VENTA->VENTA_NRO_SERIE;
            $ventaC->VENTA_NRO_COMPROBANTE = $VENTA->VENTA_NRO_COMPROBANTE;
            $ventaC->VENTA_SUBTOTAL = $VENTA->VENTA_SUBTOTAL/100;
            $ventaC->VENTA_TOTAL = $VENTA->VENTA_TOTAL/100;
            $ventaC->COMPROBANTE_ID = $VENTA->COMPROBANTE_ID;
            $ventaC->USU_ID = $VENTA->USU_ID;
            $ventaC->METODO_DE_PAGO_ID = $VENTA->METODO_DE_PAGO_ID;
            $ventaC->CLIENTE_ID = $VENTA->CLIENTE_ID;

            $db->begin_transaction(); // INICIO DE LAS TRANSACCIONES. 

            $exito = $ventaC->registrar($mensaje,$code_error,$VentaId);

            if($exito){

                $detalleVenta = new DetalleVenta($db);
                $DETALLE_VENTA =  $datos->DETALLE_DE_VENTA;

                foreach($DETALLE_VENTA as $det){

                    $detalleVenta->DET_CANTIDAD = $det->DET_CANTIDAD; 
                    $detalleVenta->DET_IMPORTE = $det->DET_IMPORTE/100;
                    $detalleVenta->PRO_ID = $det->PRO_ID;
                    $exito = $detalleVenta->agregarDetalleVenta($mensaje,$code_error,$VentaId);

                    if(!$exito)
                        break;
                }

                if($exito){

                    header('HTTP/1.1 200 OK');
                    $db->commit(); // SI NO EXISTE NINGÚN ERROR SE EJECUTA LA TRANSACCIÓN
                    
                }else{

                    $db->rollback(); //SE DESHACEN LOS CAMBIOS REALIZADOS
                    header('HTTP/1.1 400 Bad Request');
                }

            }else{
                $db->rollback(); //SE DESHACEN LOS CAMBIOS REALIZADOS
                header('HTTP/1.1 400 Bad Request');
            }
            echo json_encode( array("error"=>$code_error,"mensaje"=>$mensaje,"exito"=>$exito));
        }else{
            $db->rollback(); //SE DESHACEN LOS CAMBIOS REALIZADOS
            $code_error = "error_deCampo";
            echo json_encode(array("error"=>$code_error,"mensaje"=>$mensaje, "exito"=>false));
            header('HTTP/1.1 400 Bad Request');

        }
    }

    function esValido(&$m,$d){
         

        if(!isset($d->VENTA)){

            $m = "La variable VENTA no ha sido enviada.";
            return false;

        }else{
            
            $VENTA = $d->VENTA;

            //validamos METODO_DE_PAGO_ID
            if(!isset($VENTA->METODO_DE_PAGO_ID)){
                $m = "La variable METODO_DE_PAGO_ID no ha sido enviada.";
                return false;
            }else{  
                if($VENTA->METODO_DE_PAGO_ID == ""){
                    $m = "La variable METODO_DE_PAGO_ID no puede estar vacía o ser null.";
                    return false; 
                }else{
                    if(!is_numeric($VENTA->METODO_DE_PAGO_ID)){
                    $m = "La variable METODO_DE_PAGO_ID solo acepta caracteres numéricos.";
                    return false;  
                    }else{
                        if($VENTA->METODO_DE_PAGO_ID < 1 ){
                            $m = "La variable METODO_DE_PAGO_ID no puede ser menor o igual a 0.";
                            return false; 
                        }
                    }
                }
            }

            //validamos CLIENTE_ID
            if(!isset($VENTA->CLIENTE_ID)){
                $m = "La variable CLIENTE_ID no ha sido enviada.";
                return false;
            }else{  
                if($VENTA->CLIENTE_ID == ""){
                    $m = "La variable CLIENTE_ID no puede estar vacía o ser null.";
                    return false; 
                }else{
                    if(!is_numeric($VENTA->CLIENTE_ID)){
                    $m = "La variable CLIENTE_ID solo acepta caracteres numéricos.";
                    return false;  
                    }else{
                        if($VENTA->CLIENTE_ID < 1 ){
                            $m = "La variable CLIENTE_ID no puede ser menor o igual a 0.";
                            return false; 
                        }
                    }
                }
            }

            //validamos usu id 
            if(!isset($VENTA->USU_ID)){
                $m = "La variable USU_ID no ha sido enviada.";
                return false;
            }else{  
                if($VENTA->USU_ID == ""){
                    $m = "La variable USU_ID no puede estar vacía o ser null.";
                    return false; 
                }else{
                    if(!is_numeric($VENTA->USU_ID)){
                    $m = "La variable USU_ID solo acepta caracteres numéricos.";
                    return false;  
                    }else{
                        if($VENTA->USU_ID < 1 ){
                            $m = "La variable USU_ID no puede ser menor o igual a 0.";
                            return false; 
                        }
                    }
                }
            }

            //validamos comprobante id 
            if(!isset($VENTA->COMPROBANTE_ID)){
                $m = "La variable COMPROBANTE_ID no ha sido enviada.";
                return false;
            }else{  
                if($VENTA->COMPROBANTE_ID == ""){
                    $m = "La variable COMPROBANTE_ID no puede estar vacía o ser null.";
                    return false; 
                }else{
                    if(!is_numeric($VENTA->COMPROBANTE_ID)){
                    $m = "La variable COMPROBANTE_ID solo acepta caracteres numéricos.";
                    return false;  
                    }else{
                        if($VENTA->COMPROBANTE_ID < 1 ){
                            $m = "La variable COMPROBANTE_ID no puede ser menor o igual a 0.";
                            return false; 
                        }
                    }
                }
            }

            //validación de la variable VENTA_FECHA_EMISION_COMPROBANTE
            if(!isset($VENTA->VENTA_FECHA_EMISION_COMPROBANTE)){
                $m = "La variable VENTA_FECHA_EMISION_COMPROBANTE no ha sido enviada.";
                return false; 
            }else{
                if(($VENTA->VENTA_FECHA_EMISION_COMPROBANTE=="")){
                    $m = "La variable VENTA_FECHA_EMISION_COMPROBANTE no puede ser null.";
                    return false;
                }else{
                    if(!verificarFecha($VENTA->VENTA_FECHA_EMISION_COMPROBANTE)){
                        $m = "La variable VENTA_FECHA_EMISION_COMPROBANTE no contiene una fecha válida o no tiene el formato permitido.";
                        return false;
                    }else{
                        if(!esMenorFechaActual($VENTA->VENTA_FECHA_EMISION_COMPROBANTE)){
                            $m = "La variable VENTA_FECHA_EMISION_COMPROBANTE no puede ser mayor a la fecha actual.";
                            return false;
                        }
                    }
                }
            }

            //validación de la variable VENTA_FECHA_EMISION_COMPROBANTE
            if(!isset($VENTA->VENTA_FECHA_REGISTRO)){
                $m = "La variable VENTA_FECHA_REGISTRO no ha sido enviada.";
                return false; 
            }else{
                if(($VENTA->VENTA_FECHA_REGISTRO=="")){
                    $m = "La variable VENTA_FECHA_REGISTRO no puede ser null.";
                    return false;
                }else{
                    if(!verificarFecha($VENTA->VENTA_FECHA_REGISTRO)){
                        $m = "La variable VENTA_FECHA_REGISTRO no contiene una fecha válida o no tiene el formato permitido.";
                        return false;
                    }else{
                        if(!esMenorFechaActual($VENTA->VENTA_FECHA_REGISTRO)){
                            $m = "La variable VENTA_FECHA_REGISTRO no puede ser mayor a la fecha actual.";
                            return false;
                        }
                    }
                }
            }

            //validaciones de la variable COMPRA_NRO_SERIE
            if(!isset($VENTA->VENTA_NRO_SERIE)){
                $m = "La variable VENTA_NRO_SERIE no ha sido enviada.";
                return false;
            }
            // else{  
            //     if($VENTA->VENTA_NRO_SERIE == ""){
            //         $m = "La variable VENTA_NRO_SERIE no puede estar vacía o ser null.";
            //         return false; 
            //     }else{
            //         if(!ctype_digit($VENTA->VENTA_NRO_SERIE)){
            //             $m = "La variable VENTA_NRO_SERIE debe estar conformada por caracteres numéricos.";
            //             return false;
            //         }else{
            //             if(obtenerCantidadDeCaracteres($VENTA->VENTA_NRO_SERIE)<3 && 
            //             obtenerCantidadDeCaracteres($VENTA->VENTA_NRO_SERIE)>5){
            //                 $m = "La variable VENTA_NRO_SERIE debe tener una longitud de entre 3 y 5 caracteres numericos.";
            //                 return false;
            //             }
            //         }
            //     }
            // }
            //validaciones de la variable COMPRA_NRO_COMPROBANTE
            if(!isset($VENTA->VENTA_NRO_COMPROBANTE)){
                $m = "La variable VENTA_NRO_COMPROBANTE no ha sido enviada.";
                return false;
            }
            // else{  
            //     if($VENTA->VENTA_NRO_COMPROBANTE == ""){
            //         $m = "La variable VENTA_NRO_COMPROBANTE no puede estar vacía o ser null.";
            //         return false; 
            //     }else{
            //         if(!ctype_digit($VENTA->VENTA_NRO_COMPROBANTE)){
            //             $m = "La variable VENTA_NRO_COMPROBANTE debe estar conformada por caracteres numéricos.";
            //             return false;
            //         }else{
            //             if(obtenerCantidadDeCaracteres($VENTA->VENTA_NRO_COMPROBANTE)<7 && 
            //             obtenerCantidadDeCaracteres($VENTA->VENTA_NRO_COMPROBANTE)>10){
            //                 $m = "La variable VENTA_NRO_COMPROBANTE debe tener una longitud de entre 7 y 10 caracteres numericos.";
            //                 return false;
            //             }
            //         }
            //     }
            // }

            //validaciones de la variable COMPRA_SUBTOTAL
            if(!isset($VENTA->VENTA_SUBTOTAL)){
                $m = "La variable VENTA_SUBTOTAL no ha sido enviada.";
                return false;
            }else{  
                if(ctype_digit($VENTA->VENTA_SUBTOTAL) || is_numeric($VENTA->VENTA_SUBTOTAL)){
                    if($VENTA->VENTA_SUBTOTAL <= 0) { 
                        $m = 'El valor de la variable VENTA_SUBTOTAL debe ser mayor a 0.';
                        return false;
                    }
                }else{
                    $m = 'La variable COMPRA_SUBTOTAL no es un numero o es null.';
                    return false;
                }
            }

            //validaciones de la variable COMPRA_TOTAL
            if(!isset($VENTA->VENTA_TOTAL)){
                $m = "La variable VENTA_TOTAL no ha sido enviada.";
                return false;
            }else{  
                if(ctype_digit($VENTA->VENTA_TOTAL) || is_numeric($VENTA->VENTA_TOTAL)){
                    if($VENTA->VENTA_TOTAL <= 0) { 
                        $m = 'El valor de la variable VENTA_TOTAL debe ser mayor a 0.';
                        return false;
                    }
                }else{
                    $m = 'La variable VENTA_TOTAL no es un numero o es null.';
                    return false;
                }
            }

        }

        if(!isset($d->DETALLE_DE_VENTA)){
            $m = "La variable DETALLE_DE_VENTA no ha sido enviada.";
            return false;
        }else{

            $DETALLE_VENTA = $d->DETALLE_DE_VENTA;
            $DET_VENTA_TEMP = $d->DETALLE_DE_VENTA;

            $importeTotalDetalleVenta = 0 ; 
            $i = 0 ; //indice del primer bucle
            $j = 0;  //indice del segundo bucle 
            foreach($DETALLE_VENTA as $det){
                
                 //validaciones de la cantidad del detalle de compra
                 if(!isset($det->DET_CANTIDAD)){
                    $m = "La variable DET_CANTIDAD no ha sido enviada.";
                    return false;
                }else{  
                    if(ctype_digit($det->DET_CANTIDAD) || is_numeric($det->DET_CANTIDAD)){
                        if($det->DET_CANTIDAD <= 0) { 
                            $m = 'El valor de la variable DET_CANTIDAD debe ser mayor a 0.';
                            return false;
                        }
                    }else{
                        $m = 'La variable DET_CANTIDAD no es un numero o es null.';
                        return false;
                    }
                }

                //validaciones del importe del detalle de compra
                if(!isset($det->DET_IMPORTE)){
                    $m = "La variable DET_IMPORTE no ha sido enviada.";
                    return false;
                }else{  
                    if(ctype_digit($det->DET_IMPORTE) || is_numeric($det->DET_IMPORTE)){
                        if($det->DET_IMPORTE <= 0) { 
                            $m = 'El valor de la variable DET_IMPORTE debe ser mayor a 0.';
                            return false;
                        }
                    }else{
                        $m = 'La variable DET_IMPORTE no es un numero o es null.';
                        return false;
                    }
                }
                //se van sumando los importes de los detalles de venta para verificar sea igual al total de la compra
                $importeTotalDetalleVenta += $det->DET_IMPORTE;

                //validaciones del id del producto ingresado en el detalle de compra
                if(!isset($det->PRO_ID)){
                    $m = "La variable PRO_ID no ha sido enviada.";
                    return false;
                }else{  
                    if($det->PRO_ID == ""){
                        $m = "La variable PRO_ID no puede estar vacía o ser null.";
                        return false; 
                    }else{
                        if(!is_numeric($det->PRO_ID)){
                        $m = "La variable PRO_ID solo acepta caracteres numéricos.";
                        return false;  
                        }else{
                            if($det->PRO_ID < 1 ){
                                $m = "La variable PRO_ID no puede ser menor o igual a 0.";
                                return false; 
                            }
                        }
                    }
                }
                //sirve para validar que no se repita ningún producto
                foreach($DET_VENTA_TEMP as $deta){
                    if($deta->PRO_ID == $det->PRO_ID &&  $i!=$j){

                        $m = "No pueden haber productos repetidos en los detalles de compra.";
                        return false;
                    }
                    $j++;
                }
                $j=0;
                $i++;

            }      


        }
        if($importeTotalDetalleVenta != $VENTA->VENTA_TOTAL){
            
            $m ="La suma de los importes de los detalles de venta no es igual al total de la venta.";
            return false; 
        }

        return true; 
    }
?>