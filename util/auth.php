<?php
    require_once '../clases/Autorizacion.php';
    include_once '../clases/Usuario.php';
    include_once '../config/database.php';
    
    function authAdm(&$mensaje,&$code_error){
        $headers = apache_request_headers();
        $auth = new Autorizacion();

        foreach ($headers as $header => $value) {
            if(strtolower($header) == $auth->FIRST_HEADER){//se compara si existe la cabecera authorization
                $auth->USE_SUB = $value;//se obtiene el valor
                $auth->FIRST_HEADER = "";//se limpia la variable para que dentro del for no se vuelva a comparar
                $auth->HEADER_COUNT += 1;//se suma uno cuando se encuentra la cabecera
            }
    
            if(strtolower($header)==$SECOND_HEADER){//se compara si existe la cabecera user
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
            return false;
        }else{
            if(!isset($auth->TYPE_USER)){
                if($auth->TYPE_USER == ''){
                    $code_error = "error_autorizacion";
                    $mensaje = 'Hubo un error de autorización';
                    return false;
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
                    $exito = $usuario->tokenVerify($TOKEN,$auth->ROL,$code_error,$mensaje);
                    if($exito && $auth->ROL == 2){
                        return true;
                    }else{
                        $code_error = "error_autorizacion";
                        $mensaje = 'Hubo un error de autorización, el usuario no es administrador.';
                        return false;
                    }
                }else{
                    $code_error = "error_autorizacion";
                    $mensaje = 'Hubo un error de autorización, no se envió código de autorización de administrador.';
                    return false;
                }
            }
            else{
                $code_error = "error_autorizacion";
                $mensaje = 'Hubo un error de autorización, error verificando el token.';
                return false;
            }
    
        }else{
            $code_error = "error_autorizacion";
            $mensaje = 'Hubo un error de autorización, token de verificación vacío.';
            return false;
        }

    }
    function authEmp(){

    }
    function authBoth(){

    }
    // $headers = apache_request_headers();
    // $USE_SUB = '';
    // $TYPE_USER = '';
    // $FIRST_HEADER = 'authorization';
    // $SECOND_HEADER = 'user';
    // $HEADER_COUNT = 0;
    // $AUTH_ADM = 'dmMLAeOtrn';
    // $AUTH_EMP = 'me2Ia1NMer';

    // foreach ($headers as $header => $value) {
    //     if(strtolower($header)==$FIRST_HEADER){//se compara si existe la cabecera authorization
    //         $USE_SUB = $value;//se obtiene el valor
    //         $FIRST_HEADER = "";//se limpia la variable para que dentro del for no se vuelva a comparar
    //         $HEADER_COUNT += 1;//se suma uno cuando se encuentra la cabecera
    //     }

    //     if(strtolower($header)==$SECOND_HEADER){//se compara si existe la cabecera user
    //         $TYPE_USER = $value;// se obtiene el valor
    //         $SECOND_HEADER = "";//se limpia la variable para que dentro del for no se vuelva a comparar
    //         $HEADER_COUNT += 1;//se suma uno cuando se encuentra la cabecera
    //     }

    //     if($HEADER_COUNT == 2)//si es 2 es porque se encontraron las 2 cabeceras
    //         break;
    // }
    
    // if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    //     return;
    // }

    // if($HEADER_COUNT != 2){//si no se encontraron las 2 cabeceras
    //     $code_error = "error_autorizacion";
    //     $mensaje = 'Hubo un error de autorización';
    //     echo json_encode(array("error"=>$code_error,"mensaje"=>$mensaje, "exito"=>false));
    //     header('HTTP/1.0 401 Unauthorized');
    //     exit;//se termina la ejecución
    // }else{
    //     if(!isset($TYPE_USER)){
    //         if($TYPE_USER == ''){
    //             $code_error = "error_autorizacion";
    //             $mensaje = 'Hubo un error de autorización';
    //             echo json_encode(array("error"=>$code_error,"mensaje"=>$mensaje, "exito"=>false));
    //             header('HTTP/1.0 401 Unauthorized');
    //             exit;//se termina la ejecución
    //         }
    //     }
    // }

    // if($USE_SUB!=""){
    //     # Basic 29$$101109112108101$2y$10$YYKcURLLTlYGMuKVTkklVeUVfXtpzUAwbRL35P03P1vNQjo91NaYW97100105116111
    //     $VALUE_HEADER_USE_SUB = explode(' ',$USE_SUB); // Lo trasforma en un arreglo todo lo que este separado el parametro ingresado => ''
    //     # $VALUE_HEADER_USE_SUB[0] = Basic
    //     # $VALUE_HEADER_USE_SUB[1] = 29$$101109112108101$2y$10$YYKcURLLTlYGMuKVTkklVeUVfXtpzUAwbRL35P03P1vNQjo91NaYW97100105116111
        
    //     $TOKEN_USE_SUB = explode('$$',$VALUE_HEADER_USE_SUB[1]);//el segundo es todo el texto no legible
    //     # TOKEN_USE_SUB[0] = 29
    //     # TOKEN_USE_SUB[1] = 101109112108101$2y$10$YYKcURLLTlYGMuKVTkklVeUVfXtpzUAwbRL35P03P1vNQjo91NaYW97100105116111
    //     $VALID_TOKEN = (is_numeric($TOKEN_USE_SUB[0]))? true:false;//verifico si los caracteres antes del separador '$$' sean numericos

    //     if($VALID_TOKEN){
    //         # TOKEN_USE_SUB[0] = 29
    //         $FIRST_HALF_TOKEN_0 = round(intval($TOKEN_USE_SUB[0])/2);//dividir el numero inicial del valor de la cabecera a la mitad (redondeado)
    //         # TOKEN_USE_SUB[0])/2 => 29 / 2 => 14.5 => round(14.5) => 15
    //         # FIRST_HALF_TOKEN_0 = 15
    //         echo json_encode($FIRST_HALF_TOKEN_0);

    //         $SECOND_HALF_TOKEN_0 = intval($TOKEN_USE_SUB[0]) - $FIRST_HALF_TOKEN_0;
    //         # SECOND_HALF_TOKEN = 29 - 15 = 14;
            
    //         # TOKEN_USE_SUB[1] = 101109112108101$2y$10$YYKcURLLTlYGMuKVTkklVeUVfXtpzUAwbRL35P03P1vNQjo91NaYW97100105116111
    //         $TOKEN = substr($TOKEN_USE_SUB[1],$FIRST_HALF_TOKEN_0,-$SECOND_HALF_TOKEN_0);//Saca un substring desde el indice FIRST_HALF_TOKEN_0 hasta el SECOND_HALF_TOKEN_0 (De derecha a izquierda por ser negativo)
    //         # FIRST_HALF_TOKEN_0 = 15 -> 101109112108101 ****** $2y$10$YYKcURLLTlYGMuKVTkklVeUVfXtpzUAwbRL35P03P1vNQjo91NaYW97100105116111
    //         # SECOND_HALF_TOKEN_0 = -14 -> $2y$10$YYKcURLLTlYGMuKVTkklVeUVfXtpzUAwbRL35P03P1vNQjo91NaYW ****** 97100105116111
    //         # TOKEN = $2y$10$YYKcURLLTlYGMuKVTkklVeUVfXtpzUAwbRL35P03P1vNQjo91NaYW (La contraseña hasheada del usuario)
            

    //         if($TYPE_USER == $AUTH_ADM){
    //             $database = new Database();
    //             $db = $database->getConnection();
    //             $usuario = new Usuario($db);
    //             $TIPY_TOKEN = $usuario->tokenVerify($TOKEN);
    //             if($TIPY_TOKEN == 2){
    //                 return true;
    //             }else{
    //                 $code_error = "error_autorizacion";
    //                 $mensaje = 'Hubo un error de autorización, el usuario no es administrador.';
    //                 echo json_encode(array("error"=>$code_error,"mensaje"=>$mensaje, "exito"=>false));
    //                 header('HTTP/1.0 401 Unauthorized');
    //                 exit;//se termina la ejecución
    //             }
    //         }
    //     }
    //     else{
    //         $code_error = "error_autorizacion";
    //     $mensaje = 'Hubo un error de autorización, error verificando el token.';
    //     echo json_encode(array("error"=>$code_error,"mensaje"=>$mensaje, "exito"=>false));
    //     header('HTTP/1.0 401 Unauthorized');
    //     exit;//se termina la ejecución
    //     }

    // }else{
    //     $code_error = "error_autorizacion";
    //     $mensaje = 'Hubo un error de autorización';
    //     echo json_encode(array("error"=>$code_error,"mensaje"=>$mensaje, "exito"=>false));
    //     header('HTTP/1.0 401 Unauthorized');
    //     exit;//se termina la ejecución
    // }
?>