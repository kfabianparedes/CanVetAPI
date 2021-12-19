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
        if(esValido($mensaje, $datos)){
            $usuario->USU_USUARIO = $datos->USU_USUARIO;
            $usuario->USU_CONTRASENIA = $datos->USU_CONTRASENIA;
            $usuario->USU_NOMBRES = $datos->USU_NOMBRES;
            $usuario->USU_APELLIDO_PATERNO = $datos->USU_APELLIDO_PATERNO;
            $usuario->USU_APELLIDO_MATERNO = $datos->USU_APELLIDO_MATERNO;
            $usuario->USU_SEXO = $datos->USU_SEXO; 
            $usuario->USU_DNI = $datos->USU_DNI;
            $usuario->USU_CELULAR = $datos->USU_CELULAR;
            $usuario->USU_FECHA_NACIMIENTO = $datos->USU_FECHA_NACIMIENTO;
            $usuario->USU_DIRECCION = $datos->USU_DIRECCION;
            $usuario->USU_EMAIL = $datos->USU_EMAIL;
            $usuario->USU_ESTADO = $datos->USU_ESTADO; //Habilitado(1) / Deshabilitado(0) / Cambio de contraseña (2) 
            $usuario->ROL_ID = $datos->ROL_ID;
            
            $exito = $usuario->crearUsuario($code_error,$mensaje);
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

    function esValido(&$m ,$d){
        if(!isset($d)){
            $m = "Los datos ingresados deben respetar el formato json";
            return false;
        }else{
            if(!isset($d->USU_USUARIO)){
                $m = "la variable USU_USUARIO no ha sido enviada.";
                return false;
            }else{  
                if($d->USU_USUARIO == ""){
                    $m = "la variable USU_USUARIO no puede estar vacía o ser null.";
                    return false; 
                }
            }
    
            if(!isset($d->USU_CONTRASENIA)){
                $m = "La variable USU_CONTRASENIA no ha sido enviada.";
                return false;
            }else{  
                if($d->USU_CONTRASENIA == ""){
                    $m = "La variable USU_CONTRASENIA no puede estar vacía o ser null.";
                    return false; 
                }else{
                    if(obtenerCantidadDeCaracteres($d->USU_CONTRASENIA)<8 || obtenerCantidadDeCaracteres($d->USU_CONTRASENIA)>20){
                        $m = "La variable USU_CONTRASENIA no puede ser menor a 8 ni mayor a 20 caracteres.";
                        return false; 
                    }
                }
            }
    
            if(!isset($d->USU_NOMBRES)){
                $m = "La variable USU_NOMBRES no ha sido enviada.";
                return false;
            }else{  
                if($d->USU_NOMBRES == ""){
                    $m = "La variable USU_NOMBRES no puede estar vacía o ser null.";
                    return false; 
                }else{
                    if(!esTextoAlfabetico(trim($d->USU_NOMBRES))){
                        $m = "La variable USU_NOMBRES debe ser alfabético.";
                        return false;
                    }
                    else if(obtenerCantidadDeCaracteres($d->USU_NOMBRES)>50){
                        $m = "La variable USU_NOMBRES no puede ser mayor a 50 caracteres.";
                        return false; 
                    }
                }
            }

            if(!isset($d->USU_APELLIDO_PATERNO)){
                $m = "El campo USU_APELLIDO_PATERNO no ha sido enviado.";
                return false;
            }else{
                if($d->USU_APELLIDO_PATERNO == ""){
                    $m = "La variable USU_APELLIDO_PATERNO no debe estar vacía.";
                    return false;
                }
                else {
                    if(!esTextoAlfabetico($d->USU_APELLIDO_PATERNO)){
                        $m = "La variable USU_APELLIDO_PATERNO debe ser alfabetica.";
                        return false;
                    }
                    else if(obtenerCantidadDeCaracteres($d->USU_APELLIDO_PATERNO)>30){
                        $m = "La variable USU_APELLIDO_PATERNO no debe exceder los 30 caracteres.";
                        return false;
                    }
                }
            }

            if(!isset($d->USU_APELLIDO_MATERNO)){
                $m = "El campo USU_APELLIDO_MATERNO no ha sido enviado.";
                return false;
            }else{
                if($d->USU_APELLIDO_MATERNO == ""){
                    $m = "La variable USU_APELLIDO_MATERNO no debe estar vacía.";
                    return false;
                }
                else {
                    if(!esTextoAlfabetico($d->USU_APELLIDO_MATERNO)){
                        $m = "La variable USU_APELLIDO_MATERNO debe ser alfabetica.";
                        return false;
                    }
                    else if(obtenerCantidadDeCaracteres($d->USU_APELLIDO_MATERNO)>30){
                        $m = "La variable USU_APELLIDO_MATERNO no debe exceder los 30 caracteres.";
                        return false;
                    }
                }
            }

            if(!isset($d->USU_SEXO)){
                $m = "El campo USU_SEXO no ha sido enviado.";
                return false;
            }else{
                if($d->USU_SEXO==""){ 
                    $m = "El campo USU_SEXO no puede estar vacio.";
                    return false;   
                }else{
                    if(obtenerCantidadDeCaracteres($d->USU_SEXO)!=1){
                        $m = "La variable USU_SEXO debe contener un caracter.";
                        return false;
                    }else if(strtoupper($d->USU_SEXO)!='M' && strtoupper($d->USU_SEXO)!='F'){
                        $m = "La variable USU_SEXO debe ser masculino \"M\" o femenino \"F\".";
                        return false;
                    }
                }
            }

            if(!isset($d->USU_DNI)){
                $m = "El campo USU_DNI no ha sido enviado.";
                return false;
            }else{
                if($d->USU_DNI==""){
                    $m = "La variable USU_DNI no debe estar vacía.";
                    return false;
                }
                else {
                    if(!ctype_digit($d->USU_DNI)){
                        $m = "La variable USU_DNI debe tener caracteres numéricos.";
                        return false;
                    }
                    if(obtenerCantidadDeCaracteres($d->USU_DNI)!=8){
                        $m = "La variable USU_DNI debe tener una longitud de 8 caracteres numéricos.";
                        return false;
                    }
                }
            }

            if(!isset($d->USU_CELULAR)){
                $m = "El campo USU_CELULAR no ha sido enviado.";
                return false;
            }else{
                if($d->USU_CELULAR==""){
                    $m = "El campo USU_CELULAR no puede estar vacío.";
                    return false;
                }else{
                    if(obtenerCantidadDeCaracteres($d->USU_CELULAR)!=9){
                        $m = "La variable USU_CELULAR no debe exceder de 9 caracteres.";
                        return false;
                    }else{
                        if(!verificarCelular($d->USU_CELULAR)){
                            $m = "La varaible USU_CELULAR no tiene el formato permitido.";
                            return false;
                        }
                    }
                }
            }

            if(!isset($d->USU_FECHA_NACIMIENTO)){
                $m = "La variable USU_FECHA_NACIMIENTO no ha sido enviada.";
                return false; 
            }else{
                if($d->USU_FECHA_NACIMIENTO==""){
                    $m = "La variable USU_FECHA_NACIMIENTO no puede ser null.";
                    return false;
                }else{
                    if(!verificarFecha($d->USU_FECHA_NACIMIENTO)){
                        $m = "La variable USU_FECHA_NACIMIENTO no contiene una fecha válida o no tiene el formato permitido.";
                        return false;
                    }else{
                        if(calcularedad($d->USU_FECHA_NACIMIENTO)<18){
                            $m = "La variable USU_FECHA_NACIMIENTO debe ser mayor a 18 años.";
                            return false;
                        }
                    }
                }
            }

            if(!isset($d->USU_DIRECCION)){
                $m = "El campo USU_DIRECCION no ha sido enviado";
                return false;
            }else{
                if($d->USU_DIRECCION==""){  
                    $m = "La variable USU_DIRECCION no puede ser null o vacía";
                    return false;  
                }else{
                    if(obtenerCantidadDeCaracteres($d->USU_DIRECCION)>100){
                        $m = "La variable USU_DIRECCION supera los 100 caracteres permitidos.";
                        return false;
                    }
                }
            }

            if(!isset($d->USU_EMAIL)){
                $m = "El campo USU_EMAIL no ha sido enviado";
                return false;
            }else{
                if($d->USU_EMAIL==""){
                    $m = "La variable USU_EMAIL no debe estar vacía.";
                    return false;
                }else{
                    if(obtenerCantidadDeCaracteres($d->USU_EMAIL)>60){
                        $m = "La variable USU_EMAIL no debe exceder los 60 caracteres.";
                        return false;
                    }else{
                        if(!filter_var($d->USU_EMAIL, FILTER_VALIDATE_EMAIL)){
                            $m = "La variable USU_EMAIL no tiene un formato valido.";
                            return false;
                        }
                    }
                }
            }

            if(!isset($d->USU_ESTADO)){
                $m = "El campo USU_ESTADO no ha sido enviado";
                return false;
            }else{
                if($d->USU_ESTADO == ""){
                    $m = "La variable USU_ESTADO no puede ser null o vacío.";
                    return false;
                    
                }else{
                    if(!is_numeric($d->USU_ESTADO)){
                        $m = "El campo USU_ESTADO debe ser numérico";
                        return false;
                    }else{
                        if(obtenerCantidadDeCaracteres($d->USU_ESTADO)!=1){
                            $m = "El valor de USU_ESTADO debe tener solo un digito positivo.";
                            return false;
                        }else{
                            if($d->USU_ESTADO>1 || $d->USU_ESTADO <0){
                                $m = "El valor de USU_ESTADO debe no debe ser difernete de 0 o 1.";
                                return false;
                            }
                        }
                    }
                }
            }

            if(!isset($d->ROL_ID)){
                $m = "El campo ROL_ID no ha sido enviado";
                return false;
            }else{
                if($d->ROL_ID == ""){
                    $m = "La variable ROL_ID no puede ser null o vacío.";
                    return false;
                }else{
                    if(!is_numeric($d->ROL_ID)){
                        $m = "El campo ROL_ID debe ser numérico";
                        return false;
                    }else{
                        if(obtenerCantidadDeCaracteres($d->ROL_ID)!=1){
                            $m = "El valor de ROL_ID debe tener solo un digito positivo.";
                            return false;
                        }else{
                            if($d->ROL_ID>2 || $d->ROL_ID <=0){
                                $m = "El valor de ROL_ID debe no debe ser difernete de 1 o 2.";
                                return false;
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

?>