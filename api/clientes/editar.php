<?php
    header('Access-Control-Allow-Origin: *');
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: PUT");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, User");

    include_once '../../clases/Usuario.php';
    include_once '../../clases/Autorizacion.php';
    include_once '../../config/database.php';
    include_once '../../util/validaciones.php';
    include_once '../../clases/Cliente.php';

    if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
        return;
    }
    $VentaId = '';
    $headers = apache_request_headers();
    $auth = new Autorizacion();
    $code_error = null;
    $mensaje = '';
    $exito = false;
    $esJuridico = 0;
    $DJ_RAZON_SOCIAL = '';
    $DJ_RUC = '';
    $DJ_TIPO_EMPRESA_ID = 0;
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
        
        if(esValido($mensaje,$datos,$esJuridico)){

            $clienteC = new Cliente($db);
            $CLIENTE = $datos->CLIENTE; 

            $clienteC->CLIENTE_ID =$CLIENTE->CLIENTE_ID;
            $clienteC->CLIENTE_DNI =$CLIENTE->CLIENTE_DNI;
            $clienteC->CLIENTE_NOMBRES =$CLIENTE->CLIENTE_NOMBRES;
            $clienteC->CLIENTE_APELLIDOS =$CLIENTE->CLIENTE_APELLIDOS;
            $clienteC->CLIENTE_TELEFONO =$CLIENTE->CLIENTE_TELEFONO;
            $clienteC->CLIENTE_DIRECCION =$CLIENTE->CLIENTE_DIRECCION;
            $clienteC->CLIENTE_CORREO =$CLIENTE->CLIENTE_CORREO;
            if($esJuridico == 1){
                $DATOS_JURIDICOS = $datos->DATOS_JURIDICOS; 
                $DJ_RAZON_SOCIAL = $DATOS_JURIDICOS->DJ_RAZON_SOCIAL; 
                $DJ_RUC = $DATOS_JURIDICOS->DJ_RUC; 
                $DJ_TIPO_EMPRESA_ID = $DATOS_JURIDICOS->TIPO_EMPRESA_ID; 
            }else{
                $DJ_RAZON_SOCIAL = ''; 
                $DJ_RUC = ''; 
                $DJ_TIPO_EMPRESA_ID = ''; 
            }

            $exito = $clienteC->editarCliente($mensaje,$code_error,$esJuridico,$DJ_RAZON_SOCIAL,$DJ_RUC,$DJ_TIPO_EMPRESA_ID);
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

    function esValido(&$m, &$d,&$esJuridico){


        if(!isset($d)){
            $m = "Se debe respetar el formato json.";
            return false;
        }else{

            //validaciones de datos juridicos
            if(!isset( $d->DATOS_JURIDICOS)){
                
                $m = "La variable DATOS_JURIDICOS no ha sido enviada.";
                return false;

            }else{
                $DATOS_JURIDICOS = $d->DATOS_JURIDICOS; 
                if(!isset($DATOS_JURIDICOS->DJ_RAZON_SOCIAL) && !isset($DATOS_JURIDICOS->DJ_RUC) && !isset($DATOS_JURIDICOS->TIPO_EMPRESA_ID)){
                    $esJuridico = 0; 
                }else{
                    $esJuridico = 1; 

                    //validamos TIPO_EMPRESA_ID
                    if(!isset($DATOS_JURIDICOS->TIPO_EMPRESA_ID)){
                        $m = "La variable TIPO_EMPRESA_ID no ha sido enviada.";
                        return false;
                    }
                    // else{  
                    //     if($DATOS_JURIDICOS->TIPO_EMPRESA_ID == ""){
                    //         $m = "La variable TIPO_EMPRESA_ID no puede estar vacía o ser null.";
                    //         return false; 
                    //     }else{
                    //         if(!is_numeric($DATOS_JURIDICOS->TIPO_EMPRESA_ID)){
                    //         $m = "La variable TIPO_EMPRESA_ID solo acepta caracteres numéricos.";
                    //         return false;  
                    //         }else{
                    //             if($DATOS_JURIDICOS->TIPO_EMPRESA_ID < 1 ){
                    //                 $m = "La variable TIPO_EMPRESA_ID no puede ser menor o igual a 0.";
                    //                 return false; 
                    //             }
                    //         }
                    //     }
                    // }

                    //validaciones de DJ_RUC
                    if(!isset($DATOS_JURIDICOS->DJ_RUC)){
                        $m = "La variable DJ_RUC no ha sido enviada.";
                        return false;
                    }else{  
                        if($DATOS_JURIDICOS->DJ_RUC == ""){
                            $m = "La variable DJ_RUC no puede estar vacía o ser null.";
                            return false; 
                        }else{
                            if(!ctype_digit($DATOS_JURIDICOS->DJ_RUC)){
                                $m = "La variable DJ_RUC debe estar conformada por caracteres numéricos.";
                                return false;
                            }else{
                                if(obtenerCantidadDeCaracteres($DATOS_JURIDICOS->DJ_RUC)!=11){
                                    $m = "La variable DJ_RUC debe tener una longitud de 5 caracteres numericos.";
                                    return false;
                                }
                            }
                        }
                    }

                    //validación DJ_RAZON_SOCIAL
                    if(!isset($DATOS_JURIDICOS->DJ_RAZON_SOCIAL)){
                        $m = "La variable DJ_RAZON_SOCIAL no ha sido enviada.";
                        return false;
                    }
                    // else{
                    //     if($DATOS_JURIDICOS->DJ_RAZON_SOCIAL!=""){
                    //         if(obtenerCantidadDeCaracteres($DATOS_JURIDICOS->DJ_RAZON_SOCIAL)>100){
                    //             $m = "La variable DJ_RAZON_SOCIAL no debe exceder los 100 caracteres.";
                    //             return false;
                    //         }else{
                    //             foreach ( str_split($DATOS_JURIDICOS->DJ_RAZON_SOCIAL) as $caracter) {
                    //                 if(is_numeric($caracter)){
                    //                     $m = "La variable DJ_RAZON_SOCIAL no debe tener números.";
                    //                     return false;
                    //                 }
                    //             }
                    //         }
                    //     }else{
                    //         $m = "La variable DJ_RUC no puede estar vacía o ser null.";
                    //             return false;
                    //     }
                    // }

                }
            }

            //validaciones de CLIENTE
            if(!isset($d->CLIENTE)){
                
                $m = "La variable CLIENTE no ha sido enviada.";
                return false;

            }else{
                $CLIENTE = $d->CLIENTE; 
                if($esJuridico == 1 ){

                    //validación CLIENTE_NOMBRES
                    if(!isset($CLIENTE->CLIENTE_NOMBRES)){
                        $m = "La variable CLIENTE_NOMBRES no ha sido enviada.";
                        return false;
                    }
                    // else{
                    //     if($CLIENTE->CLIENTE_NOMBRES!=""){
                    //         if(obtenerCantidadDeCaracteres($CLIENTE->CLIENTE_NOMBRES)>45){
                    //             $m = "La variable DJ_RAZON_SOCIAL no debe exceder los 45 caracteres.";
                    //             return false;
                    //         }else{
                    //             foreach ( str_split($CLIENTE->CLIENTE_NOMBRES) as $caracter) {
                    //                 if(is_numeric($caracter)){
                    //                     $m = "La variable CLIENTE_NOMBRES no debe tener números.";
                    //                     return false;
                    //                 }
                    //             }
                    //         }
                    //     }else{
                    //         $m = "La variable CLIENTE_NOMBRES no puede estar vacía o ser null.";
                    //             return false;
                    //     }
                    // }

                    //validación CLIENTE_APELLIDOS
                    if(!isset($CLIENTE->CLIENTE_APELLIDOS)){
                        $m = "La variable CLIENTE_APELLIDOS no ha sido enviada.";
                        return false;
                    }
                    // else{
                    //     if($CLIENTE->CLIENTE_APELLIDOS!=""){
                    //         if(obtenerCantidadDeCaracteres($CLIENTE->CLIENTE_APELLIDOS)>45){
                    //             $m = "La variable CLIENTE_APELLIDOS no debe exceder los 45 caracteres.";
                    //             return false;
                    //         }else{
                    //             foreach ( str_split($CLIENTE->CLIENTE_APELLIDOS) as $caracter) {
                    //                 if(is_numeric($caracter)){
                    //                     $m = "La variable CLIENTE_APELLIDOS no debe tener números.";
                    //                     return false;
                    //                 }
                    //             }
                    //         }
                    //     }
                    // }

                    //validación CLIENTE_APELLIDOS
                    if(!isset($CLIENTE->CLIENTE_DIRECCION)){
                        $m = "La variable CLIENTE_DIRECCION no ha sido enviada.";
                        return false;
                    }// else{
                    //     if($CLIENTE->CLIENTE_DIRECCION!=""){
                    //         if(obtenerCantidadDeCaracteres($CLIENTE->CLIENTE_DIRECCION)>100){
                    //             $m = "La variable CLIENTE_DIRECCION no debe exceder los 100 caracteres.";
                    //             return false;
                    //         }
                    //     }
                    // }

                    //validaciones de CLIENTE_DNI
                    if(!isset($CLIENTE->CLIENTE_DNI)){
                        $m = "La variable CLIENTE_DNI no ha sido enviada.";
                        return false;
                    }else{  
                        if($CLIENTE->CLIENTE_DNI != ""){
                            if(!ctype_digit($CLIENTE->CLIENTE_DNI)){
                                $m = "La variable CLIENTE_DNI debe estar conformada por caracteres numéricos.";
                                return false;
                            }else{
                                if(obtenerCantidadDeCaracteres($CLIENTE->CLIENTE_DNI)!=8){
                                    $m = "La variable DJ_RUC debe tener una longitud de 8 caracteres numericos.";
                                    return false;
                                }
                            }
                        }
                    }

                    //VALIDAMOS CLIENTE_TELEFONO
                    if(!isset($CLIENTE->CLIENTE_TELEFONO)){
                        $m = "La variable CLIENTE_TELEFONO no ha sido enviada.";
                        return false;
                    }else{
                        if($CLIENTE->CLIENTE_TELEFONO!=""){
                            if(obtenerCantidadDeCaracteres($CLIENTE->CLIENTE_TELEFONO)==9){
                                if(!verificarCelular($CLIENTE->CLIENTE_TELEFONO)){
                                    $m = "La variable CLIENTE_TELEFONO no tiene el formato permitido.";
                                    return false;
                                }
                            }else{
                                $m = "La variable CLIENTE_TELEFONO no debe exceder los 9 caracteres.";
                                return false;
                            }
                        }else{
                            $m = "La variable CLIENTE_TELEFONO no debe estar vacía o ser null.";
                            return false;
                        }
                    }

                    //validaciones de la variable CLIENTE_ID
                    if(!isset($CLIENTE->CLIENTE_ID)){
                        $m = "La variable CLIENTE_ID no ha sido enviada.";
                        return false;
                    }else{  
                        if($CLIENTE->CLIENTE_ID == ""){
                            $m = "La variable CLIENTE_ID no puede estar vacía o ser null.";
                            return false; 
                        }else{
                            if(!is_numeric($CLIENTE->CLIENTE_ID)){
                            $m = "La variable CLIENTE_ID solo acepta caracteres numéricos.";
                            return false;  
                            }else{
                                if($CLIENTE->CLIENTE_ID < 1 ){
                                    $m = "La variable CLIENTE_ID no puede ser menor o igual a 0.";
                                    return false; 
                                }
                            }
                        }
                    }

                }else{

                    //validación DJ_RAZON_SOCIAL
                    if(!isset($CLIENTE->CLIENTE_NOMBRES)){
                        $m = "La variable CLIENTE_NOMBRES no ha sido enviada.";
                        return false;
                    }// else{
                    //     if($CLIENTE->CLIENTE_NOMBRES!=""){
                    //         if(obtenerCantidadDeCaracteres($CLIENTE->CLIENTE_NOMBRES)>45){
                    //             $m = "La variable DJ_RAZON_SOCIAL no debe exceder los 45 caracteres.";
                    //             return false;
                    //         }else{
                    //             foreach ( str_split($CLIENTE->CLIENTE_NOMBRES) as $caracter) {
                    //                 if(is_numeric($caracter)){
                    //                     $m = "La variable CLIENTE_NOMBRES no debe tener números.";
                    //                     return false;
                    //                 }
                    //             }
                    //         }
                    //     }else{
                    //         $m = "La variable CLIENTE_NOMBRES no puede estar vacía o ser null.";
                    //             return false;
                    //     }
                    // }

                     //VALIDAMOS CLIENTE_TELEFONO
                    if(!isset($CLIENTE->CLIENTE_TELEFONO)){
                        $m = "La variable CLIENTE_TELEFONO no ha sido enviada.";
                        return false;
                    }else{
                        if($CLIENTE->CLIENTE_TELEFONO!=""){
                            if(obtenerCantidadDeCaracteres($CLIENTE->CLIENTE_TELEFONO)==9){
                                if(!verificarCelular($CLIENTE->CLIENTE_TELEFONO)){
                                    $m = "La variable CLIENTE_TELEFONO no tiene el formato permitido.";
                                    return false;
                                }
                            }else{
                                $m = "La variable CLIENTE_TELEFONO no debe exceder los 9 caracteres.";
                                return false;
                            }
                        }else{
                            $m = "La variable CLIENTE_TELEFONO no debe estar vacía o ser null.";
                            return false;
                        }
                    }

                    //validaciones de CLIENTE_DNI
                    if(!isset($CLIENTE->CLIENTE_DNI)){
                        $m = "La variable CLIENTE_DNI no ha sido enviada.";
                        return false;
                    }else{  
                        if($CLIENTE->CLIENTE_DNI != ""){
                            if(!ctype_digit($CLIENTE->CLIENTE_DNI)){
                                $m = "La variable CLIENTE_DNI debe estar conformada por caracteres numéricos.";
                                return false;
                            }else{
                                if(obtenerCantidadDeCaracteres($CLIENTE->CLIENTE_DNI)!=8){
                                    $m = "La variable DJ_RUC debe tener una longitud de 8 caracteres numericos.";
                                    return false;
                                }
                            }
                        }else{
                            $m = "La variable CLIENTE_DNI no puede estar vacía o ser null.";
                                return false;
                        }
                    }

                    //validación CLIENTE_APELLIDOS
                    if(!isset($CLIENTE->CLIENTE_DIRECCION)){
                        $m = "La variable CLIENTE_DIRECCION no ha sido enviada.";
                        return false;
                    }// else{
                    //     if($CLIENTE->CLIENTE_DIRECCION!=""){
                    //         if(obtenerCantidadDeCaracteres($CLIENTE->CLIENTE_DIRECCION)>100){
                    //             $m = "La variable CLIENTE_DIRECCION no debe exceder los 100 caracteres.";
                    //             return false;
                    //         }
                    //     }
                    // }

                    //validación CLIENTE_APELLIDOS
                    if(!isset($CLIENTE->CLIENTE_APELLIDOS)){
                        $m = "La variable CLIENTE_APELLIDOS no ha sido enviada.";
                        return false;
                    }// else{
                    //     if($CLIENTE->CLIENTE_APELLIDOS!=""){
                    //         if(obtenerCantidadDeCaracteres($CLIENTE->CLIENTE_APELLIDOS)>45){
                    //             $m = "La variable CLIENTE_APELLIDOS no debe exceder los 45 caracteres.";
                    //             return false;
                    //         }else{
                    //             foreach ( str_split($CLIENTE->CLIENTE_APELLIDOS) as $caracter) {
                    //                 if(is_numeric($caracter)){
                    //                     $m = "La variable CLIENTE_APELLIDOS no debe tener números.";
                    //                     return false;
                    //                 }
                    //             }
                    //         }
                    //     }
                    // }
                    
                    //validaciones de la variable CLIENTE_ID
                    if(!isset($CLIENTE->CLIENTE_ID)){
                        $m = "La variable CLIENTE_ID no ha sido enviada.";
                        return false;
                    }else{  
                        if($CLIENTE->CLIENTE_ID == ""){
                            $m = "La variable CLIENTE_ID no puede estar vacía o ser null.";
                            return false; 
                        }else{
                            if(!is_numeric($CLIENTE->CLIENTE_ID)){
                            $m = "La variable CLIENTE_ID solo acepta caracteres numéricos.";
                            return false;  
                            }else{
                                if($CLIENTE->CLIENTE_ID < 1 ){
                                    $m = "La variable CLIENTE_ID no puede ser menor o igual a 0.";
                                    return false; 
                                }
                            }
                        }
                    }

                }

                if(!isset($CLIENTE->CLIENTE_CORREO)){
                    $m = "La variable CLIENTE_CORREO no ha sido enviada.";
                    return false;
                }// else{
                //     if($CLIENTE->CLIENTE_CORREO != ""){
                //         if(obtenerCantidadDeCaracteres($CLIENTE->CLIENTE_CORREO)>60){
                //             $m = "La variable CLIENTE_CORREO no debe exceder los 60 caracteres.";
                //             return false;
                //         }else{
                //             if(!filter_var($CLIENTE->CLIENTE_CORREO, FILTER_VALIDATE_EMAIL)){
                //                 $m = "La variable CLIENTE_CORREO no tiene un formato valido.";
                //                 return false;
                //             }
                //         }
                //     }
                // }

            }
        }

        return true;
    }
?>