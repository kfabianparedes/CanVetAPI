<?php
    header('Access-Control-Allow-Origin: *');
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: *"); //To allow for sending custom headers
    
    
    //Include database and classes files
    include_once '../../config/database.php';
    include_once '../../util/validaciones.php';
    include_once '../../clases/Mascota.php';
    include_once '../../clases/Usuario.php';
    include_once '../../clases/Autorizacion.php';
    //COMPROBAMOS QUE EL METODO USADO SEA GET
    if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
        return;
    }
    
        $mensaje = '';
        $exito = false;
        $code_error = null;
        $datos = [];
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
            $mascotaC = new Mascota($db);

            if(esValido($mensaje,$datos)){
                
                $mascotaC->MAS_ID = $datos->MAS_ID;
                $mascotaC->MAS_NOMBRE = $datos->MAS_NOMBRE; 
                $mascotaC->MAS_RAZA = $datos->MAS_RAZA;
                $mascotaC->MAS_COLOR = $datos->MAS_COLOR;
                $mascotaC->MAS_ESPECIE = $datos->MAS_ESPECIE; 
                $mascotaC->MAS_ATENCIONES = $datos->MAS_ATENCIONES;
                $mascotaC->CLIENTE_ID = $datos->CLIENTE_ID;

                $exito = $mascotaC->editarMascota($mensaje,$code_error);
            
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

        function esValido(&$m,$d){

            if(is_null($d)){
                $m = "Los datos ingresados deben respetar el formato json";
                return false;
            }else{
                 //validamos el id de la mascota
                 if(!isset($d->MAS_ID)){
                    $m = "El campo MAS_ID no ha sido enviado";
                    return false;
                }else{
                    if(!is_numeric($d->MAS_ID) || ctype_digit($d->MAS_ID)){
                        $m = "El campo MAS_ID debe ser numérico";
                        return false;
                    }else{
                        if($d->MAS_ID <=0){
                            $m = "El valor de MAS_ID debe no debe ser negativo o igual a 0.";
                            return false;
                        }
                    }
                    
                }

                //validamos el id del cliente
                if(!isset($d->CLIENTE_ID)){
                    $m = "El campo CLIENTE_ID no ha sido enviado";
                    return false;
                }else{
                    if(!is_numeric($d->CLIENTE_ID) || ctype_digit($d->CLIENTE_ID)){
                        $m = "El campo CLIENTE_ID debe ser numérico";
                        return false;
                    }else{
                        if($d->CLIENTE_ID <=0){
                            $m = "El valor de CLIENTE_ID debe no debe ser negativo o igual a 0.";
                            return false;
                        }
                    }
                    
                }
    
                //validamos las atenciones de la mascota
                if(!isset($d->MAS_ATENCIONES)){
                    $m = "El campo MAS_ATENCIONES no ha sido enviado";
                    return false;
                }else{
                    if(!is_numeric($d->MAS_ATENCIONES) || ctype_digit($d->MAS_ATENCIONES)){
                        $m = "El campo MAS_ATENCIONES debe ser numérico";
                        return false;
                    }else{
                        if($d->MAS_ATENCIONES <0){
                            $m = "El valor de MAS_ATENCIONES debe no debe ser negativo.";
                            return false;
                        }
                    }
                    
                }
    
                //validamos el nombre de la mascota
                if(!isset($d->MAS_NOMBRE)){
                    $m = "La variable MAS_NOMBRE no ha sido enviada.";
                    return false;
                }else{  
                    if($d->MAS_NOMBRE == ""){
                        $m = "La variable MAS_NOMBRE no puede estar vacía o ser null.";
                        return false; 
                    }else{
                        if(!esTextoAlfabetico(trim($d->MAS_NOMBRE))){
                            $m = "La variable USU_NOMBRES debe ser alfabético.";
                            return false;
                        }
                        else if(obtenerCantidadDeCaracteres($d->MAS_NOMBRE)>45){
                            $m = "La variable MAS_NOMBRE no puede ser mayor a 45 caracteres.";
                            return false; 
                        }
                    }
                }
                
                //validamos la raza de la mascota
                if(!isset($d->MAS_RAZA)){
                    $m = "La variable MAS_RAZA no ha sido enviada.";
                    return false;
                }else{  
                    if($d->MAS_RAZA == ""){
                        $m = "La variable MAS_RAZA no puede estar vacía o ser null.";
                        return false; 
                    }else{
                        if(!esTextoAlfabetico(trim($d->MAS_RAZA))){
                            $m = "La variable MAS_RAZA debe ser alfabético.";
                            return false;
                        }
                        else if(obtenerCantidadDeCaracteres($d->MAS_RAZA)>45){
                            $m = "La variable MAS_RAZA no puede ser mayor a 45 caracteres.";
                            return false; 
                        }
                    }
                }
    
                 //validamos la especie de la mascota
                 if(!isset($d->MAS_ESPECIE)){
                    $m = "La variable MAS_ESPECIE no ha sido enviada.";
                    return false;
                }else{  
                    if($d->MAS_ESPECIE == ""){
                        $m = "La variable MAS_ESPECIE no puede estar vacía o ser null.";
                        return false; 
                    }else{
                        if(!esTextoAlfabetico(trim($d->MAS_ESPECIE))){
                            $m = "La variable MAS_ESPECIE debe ser alfabético.";
                            return false;
                        }
                        else if(obtenerCantidadDeCaracteres($d->MAS_ESPECIE)>45){
                            $m = "La variable MAS_ESPECIE no puede ser mayor a 45 caracteres.";
                            return false; 
                        }
                    }
                }

                //validamos el color de la mascota
                if(!isset($d->MAS_COLOR)){
                    $m = "La variable MAS_COLOR no ha sido enviada.";
                    return false;
                }else{  
                    if($d->MAS_COLOR == ""){
                        $m = "La variable MAS_COLOR no puede estar vacía o ser null.";
                        return false; 
                    }else{
                        if(!esTextoAlfabetico(trim($d->MAS_COLOR))){
                            $m = "La variable MAS_COLOR debe ser alfabético.";
                            return false;
                        }
                        else if(obtenerCantidadDeCaracteres($d->MAS_COLOR)>45){
                            $m = "La variable MAS_COLOR no puede ser mayor a 45 caracteres.";
                            return false; 
                        }
                    }
                }
            }
    
            return true; 
        }

?>