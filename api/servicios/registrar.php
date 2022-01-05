<?php
    header('Access-Control-Allow-Origin: *'); //Change
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, User");

    include_once '../../clases/Usuario.php';
    include_once '../../clases/Servicio.php';
    include_once '../../config/database.php';
    include_once '../../util/validaciones.php';
    include_once '../../clases/Autorizacion.php';
    
    if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
        return;
    }

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
    
        if(esValido($mensaje,$datos)){
            
            $servicioC = new Servicio($db);

            $servicioC->SERVICIO_PRECIO = $datos->SERVICIO_PRECIO/100;
            $servicioC->SERVICIO_FECHA_HORA = $datos->SERVICIO_FECHA_HORA." ".$datos->HORA_SERVICIO;
            $servicioC->MASCOTA_ID = $datos->MASCOTA_ID;
            $servicioC->SERVICIO_TIPO = $datos->SERVICIO_TIPO;
            $servicioC->TIPO_SERVICIO_ID = $datos->TIPO_SERVICIO_ID;
            $servicioC->MDP_ID = $datos->MDP_ID;
            $servicioC->USU_ID = $datos->USU_ID;
            $servicioC->COMPROBANTE_ID = $datos->COMPROBANTE_ID;
            $servicioC->SERVICIO_ADELANTO = $datos->SERVICIO_ADELANTO/100;
            if(isset($datos->SERVICIO_DESCRIPCION))
                $servicioC->SERVICIO_DESCRIPCION = $datos->SERVICIO_DESCRIPCION;
            else    
                $servicioC->SERVICIO_DESCRIPCION = '';

            $exito = $servicioC->registrarServicio($mensaje,$code_error,$datos->CAJA_CODIGO);

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

    }

    function esValido(&$m, &$d){
    
    if(is_null($d)){
        $m = "Los datos ingresados deben respetar el formato json";
        return false;
    }else{

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

        //validaciones de la variable SERVICIO_PRECIO
        if(!isset($d->SERVICIO_PRECIO)){
            $m = "La variable SERVICIO_PRECIO no ha sido enviada.";
            return false;
        }else{  
            if(ctype_digit($d->SERVICIO_PRECIO) || is_numeric($d->SERVICIO_PRECIO)){
                if($d->SERVICIO_PRECIO <= 0) { 
                    $m = 'El valor de la variable SERVICIO_PRECIO debe ser mayor a 0.';
                    return false;
                }
            }else{
                $m = 'La variable SERVICIO_PRECIO no es un numero o es null.';
                return false;
            }
        }

         //validaciones de la variable SERVICIO_ADELANTO
         if(!isset($d->SERVICIO_ADELANTO)){
            $m = "La variable SERVICIO_ADELANTO no ha sido enviada.";
            return false;
        }else{  
            if(ctype_digit($d->SERVICIO_ADELANTO) || is_numeric($d->SERVICIO_ADELANTO)){
                if($d->SERVICIO_ADELANTO < 0) { 
                    $m = 'El valor de la variable SERVICIO_ADELANTO debe ser mayor a 0.';
                    return false;
                }
                if($d->SERVICIO_ADELANTO > $d->SERVICIO_PRECIO) { 
                    $m = 'El valor de la variable SERVICIO_ADELANTO no puede ser mayor a la variable SERVICIO_PRECIO';
                    return false;
                }
            }else{
                $m = 'La variable SERVICIO_ADELANTO no es un numero o es null.';
                return false;
            }
        }

        //validaciones de la descripción del servicio
        if(isset($d->SERVICIO_DESCRIPCION)){
            if(obtenerCantidadDeCaracteres($d->SERVICIO_DESCRIPCION)>200){
                $m = "La variable SERVICIO_DESCRIPCION supera los 200 caracteres permitidos.";
                return false;
            }
        }

        //validamos el TIPO_SERVICIO_ID
        if(!isset($d->TIPO_SERVICIO_ID)){
            $m = "El campo TIPO_SERVICIO_ID no ha sido enviado";
            return false;
        }else{
            if(!is_numeric($d->TIPO_SERVICIO_ID) || ctype_digit($d->TIPO_SERVICIO_ID)){
                $m = "El campo TIPO_SERVICIO_ID debe ser numérico";
                return false;
            }else{
                if($d->TIPO_SERVICIO_ID <=0){
                    $m = "El valor de TIPO_SERVICIO_ID debe no debe ser negativo o igual a 0.";
                    return false;
                }
            }
        }

        //validamos el USU_ID
        if(!isset($d->USU_ID)){
            $m = "El campo USU_ID no ha sido enviado";
            return false;
        }else{
            if(!is_numeric($d->USU_ID) || ctype_digit($d->USU_ID)){
                $m = "El campo USU_ID debe ser numérico";
                return false;
            }else{
                if($d->USU_ID <=0){
                    $m = "El valor de USU_ID debe no debe ser negativo o igual a 0.";
                    return false;
                }
            }
        }

        //validamos el COMPROBANTE_ID
        if(!isset($d->COMPROBANTE_ID)){
            $m = "El campo COMPROBANTE_ID no ha sido enviado";
            return false;
        }else{
            if(!is_numeric($d->COMPROBANTE_ID) || ctype_digit($d->COMPROBANTE_ID)){
                $m = "El campo COMPROBANTE_ID debe ser numérico";
                return false;
            }else{
                if($d->COMPROBANTE_ID <=0){
                    $m = "El valor de COMPROBANTE_ID debe no debe ser negativo o igual a 0.";
                    return false;
                }
            }
        }

        //validamos el MDP_ID
        if(!isset($d->MDP_ID)){
            $m = "El campo MDP_ID no ha sido enviado";
            return false;
        }else{
            if(!is_numeric($d->MDP_ID)){
                $m = "El campo MDP_ID debe ser numérico";
                return false;
            }else{
                if($d->MDP_ID <=0){
                    $m = "El valor de MDP_ID debe no debe ser negativo o igual a 0.";
                    return false;
                }
            }
        }

        //validamos el MASCOTA_ID
        if(!isset($d->MASCOTA_ID)){
            $m = "El campo MASCOTA_ID no ha sido enviado";
            return false;
        }else{
            if(!is_numeric($d->MASCOTA_ID) || ctype_digit($d->MASCOTA_ID)){
                $m = "El campo MASCOTA_ID debe ser numérico";
                return false;
            }else{
                if($d->MASCOTA_ID <=0){
                    $m = "El valor de MASCOTA_ID debe no debe ser negativo o igual a 0.";
                    return false;
                }
            }
        }

        //validamos el SERVICIO_ESTADO
        if(!isset($d->SERVICIO_TIPO)){
            $m = "El campo SERVICIO_TIPO no ha sido enviado";
            return false;
        }else{
            if(!is_numeric($d->SERVICIO_TIPO) || ctype_digit($d->SERVICIO_TIPO)){
                $m = "El campo SERVICIO_TIPO debe ser numérico";
                return false;
            }else{
                if($d->SERVICIO_TIPO < 0 || $d->SERVICIO_TIPO > 1){
                    $m = "El valor de SERVICIO_TIPO debe ser 0 o 1.";
                    return false;
                }
            }
        }

        //validación de la variable SERVICIO_FECHA_HORA
        if(!isset($d->SERVICIO_FECHA_HORA)){
            $m = "La variable SERVICIO_FECHA_HORA no ha sido enviada.";
            return false; 
        }else{
            if(($d->SERVICIO_FECHA_HORA=="")){
                $m = "La variable SERVICIO_FECHA_HORA no puede ser null.";
                return false;
            }else{
                if(!verificarFecha($d->SERVICIO_FECHA_HORA)){
                    $m = "La variable SERVICIO_FECHA_HORA no contiene una fecha válida o no tiene el formato permitido.";
                    return false;
                }
                // else{
                //     if(($d->SERVICIO_FECHA_HORA) < date('Y-m-d')){
                //         $m = "La variable SERVICIO_FECHA_HORA no puede ser menor a la fecha actual.";
                //         return false;
                //     }
                // }
            }
        }

        //VALIDAMOS LA VARIABLE HORA_SERVICIO            
        if(!isset($d->HORA_SERVICIO)){
            $m = "La variable HORA_SERVICIO no ha sido enviada.";
            return false; 
        }else{
            if($d->HORA_SERVICIO==""){
                $m = "La variable HORA_SERVICIO no puede ser null.";
                return false;
            }
            // else{
            //     if(!preg_match("/^([0-1][0-9]|[2][0-3])[\:]([0-5][0-9])[\:]([0-5][0-9])$/",$d->HORA_SERVICIO)){
            //         $m = "La variable HORA_SERVICIO no contiene una hora válida o no tiene el formato correcto.";
            //         return false;
            //     } else {
            //         $get_hora = getdate();
            //         $hora = $get_hora["hours"];
            //         $minuto = $get_hora["minutes"];
            //         $hora_cliente = explode(':', $d->HORA_SERVICIO );
            //         $hora_form_cliente = $hora_cliente[0];
            //         $minuto_form_cliente = $hora_cliente[1];
            //         $hoy = explode('-', $d->SERVICIO_FECHA_HORA);
            //         if($get_hora["mday"] == $hoy[2]){
            //             if($hora > $hora_form_cliente ) {
            //                 $m = "La variable1 HORA_SERVICIO debe ser mayor a la hora actual.";
            //                 return false;
            //             }else{
            //                 if($hora >= $hora_form_cliente ){
            //                     if ($minuto >= $minuto_form_cliente ){
            //                         $m = "La variable2 HORA_SERVICIO debe ser mayor a la hora actual.";
            //                         return false;
            //                     }
            //                 }
                            
            //             }
            //         }
            //     }
            // }
        }

    }
    return true; 
    }


?>