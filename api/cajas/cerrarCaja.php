<?php
    header('Access-Control-Allow-Origin: *');
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: PUT");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: *"); //To allow for sending custom headers

    //Include database and classes files
    include_once '../../config/database.php';
    include_once '../../clases/Usuario.php';
    include_once '../../clases/Autorizacion.php';
    include_once '../../clases/Caja.php';
    include_once '../../util/validaciones.php';

    if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
        return;
    }

    $mensaje = '';
    $exito = false;
    $code_error = null;
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
        
        if(esValido($mensaje,$datos)){
            $exito_ = false;
            $caja = new Caja($db);
            $caja->USU_ID = $datos->USU_ID;
            $caja->CAJA_CIERRE = $datos->CAJA_CIERRE;
            $caja->CAJA_MONTO_FINAL = $datos->CAJA_MONTO_FINAL;
            $caja->CAJA_DESCUENTO_GASTOS = $datos->CAJA_DESCUENTO_GASTOS;
            $caja->CAJA_CODIGO = $datos->CAJA_CODIGO;
            $exito_ = $caja->cerrarCaja($mensaje,$code_error);
            if($exito_){
                header('HTTP/1.1 200 OK');
                echo json_encode( array("error"=>$code_error,"mensaje"=>$mensaje,"exito"=>$exito_));
            }else{
                header('HTTP/1.1 400 Bad Request');
                echo json_encode( array("error"=>$code_error,"mensaje"=>$mensaje,"exito"=>false));
            }
        }else{
            $code_error = "error_deCampo";
            echo json_encode(array("error"=>$code_error,"mensaje"=>$mensaje, "exito"=>false));
            header('HTTP/1.1 400 Bad Request');
        }
    }

    function esValido(&$m, &$d){
        if(!isset($d)){
            $m = "Se debe respetar el formato json.";
            return false;
        }else{
            if(!isset($d->USU_ID)){
                $m = "La variable USU_ID no ha sido enviada.";
                return false;
            }else{  
                if($d->USU_ID == ""){
                    $m = "La variable USU_ID no puede estar vacía o ser null.";
                    return false; 
                }else{
                    if(!is_numeric($d->USU_ID)){
                        $m = "La variable USU_ID solo acepta caracteres numéricos.";
                        return false;  
                    }else{
                        if($d->USU_ID < 1 ){
                            $m = "La variable USU_ID no puede ser menor o igual a 0.";
                            return false; 
                        }
                    }
                }
            }

            if(!isset($d->CAJA_CIERRE)){
                $m = "La variable CAJA_CIERRE no ha sido enviada.";
                return false; 
            }else{
                if($d->CAJA_CIERRE==""){
                    $m = "La variable CAJA_CIERRE no puede ser null.";
                    return false;
                }else{
                    if(!verificarFecha($d->CAJA_CIERRE)){
                        $m = "La variable CAJA_CIERRE no contiene una fecha válida o no tiene el formato permitido.";
                        return false;
                    }else{
                        if(!esIgualFechaActual($d->CAJA_CIERRE)){
                            $m = "La variable CAJA_CIERRE debe tener la fecha de hoy.";
                            return false;
                        }
                    }
                }
            }

            if(!isset($d->CAJA_MONTO_FINAL)){
                $m = 'La variable CAJA_MONTO_FINAL no ha sido enviada.';
                return false;
            }else{
                if(!ctype_digit($d->CAJA_MONTO_FINAL) || !is_numeric($d->CAJA_MONTO_FINAL)){
                    $m = 'La variable CAJA_MONTO_FINAL no es un numero o es null.';
                    return false;
                }else{
                    if($d->CAJA_MONTO_FINAL < 0) { 
                        $m = 'El valor de la variable CAJA_MONTO_FINAL debe ser mayor o igual 0.';
                        return false;
                    }
                }
            }

            if(!isset($d->CAJA_DESCUENTO_GASTOS)){
                $m = 'La variable CAJA_DESCUENTO_GASTOS no ha sido enviada.';
                return false;
            }else{
                if(!ctype_digit($d->CAJA_DESCUENTO_GASTOS) || !is_numeric($d->CAJA_DESCUENTO_GASTOS)){
                    $m = 'La variable CAJA_DESCUENTO_GASTOS no es un numero o es null.';
                    return false;
                }else{
                    if($d->CAJA_DESCUENTO_GASTOS < 0) { 
                        $m = 'El valor de la variable CAJA_DESCUENTO_GASTOS debe ser mayor o igual 0.';
                        return false;
                    }
                }
            }

            if(!isset($d->CAJA_CODIGO)){
                $m = 'La variable CAJA_CODIGO no ha sido enviada.';
                return false;
            }else{
                if($d->CAJA_CODIGO == ''){
                    $m = 'La variable CAJA_CODIGO no es debe estar vacía o ser null.';
                    return false;
                }else{
                    if(obtenerCantidadDeCaracteres($d->CAJA_CODIGO) < 50) { 
                        $m = 'El valor de la variable CAJA_CODIGO debe ser mayor a 50.';
                        return false;
                    }
                }
            }
        }
        return true;
    }
?>