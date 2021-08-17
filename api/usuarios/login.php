<?php
    header('Access-Control-Allow-Origin: *'); //Change
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    include_once '../../clases/Usuario.php';
    include_once '../../config/database.php';


    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        return;
    }

    $database = new Database();
    $db = $database->getConnection();
    $usuario = new Usuario($db);

    $datos = json_decode(file_get_contents("php://input"));
    
    //Variables de respuesta
    $mensaje = '';
    $exito = false;
    $code_error = null;

    $usu_hash = "";
    $usu_id = "";   // ID de usuario según el tipo
    $usu_nombres = "";  // Nombre completo del usuario
    $usu_estado = -1;
    $usu_email = "";

    function esValido(&$m, &$d){
        //Antes la variable se llamaba USU_ALIAS 
        if(!isset($d->USU_IDENTIFICADOR)){
            $m = "*Campo obligatorio* La variable USU_IDENTIFICADOR no ha sido enviado.";
            return false;
        }else{
            if($d->USU_IDENTIFICADOR!=""){
                if(obtenerCantidadDeCaracteres($d->USU_IDENTIFICADOR)>60){ //Ahora acepta hasta 60 caracteres porque se puede iniciar sesión con email o alias
                    $m = "La variable USU_IDENTIFICADOR no debe exceder los 60 caracteres.";
                    return false;
                }
            }else{
                $m = "La variable USU_IDENTIFICADOR no debe estar vacio.";
                return false;
            }
        }
        
    
        if(!isset($d->USU_CONTRASENIA)){
            $m = "La variable USU_CONTRASENIA no ha sido enviado.";
            return false;
        }else{
            if($d->USU_CONTRASENIA!=""){
                if(obtenerCantidadDeCaracteres($d->USU_CONTRASENIA)<8 || obtenerCantidadDeCaracteres($d->USU_CONTRASENIA)>20){
                    $m = "La variable USU_CONTRASENIA no debe ser menor a 8 caracteres ni exceder los 20 caracteres.";
                    return false;
                }
            }else{
                $m = "La variable USU_CONTRASENIA no debe estar vacio.";
                return false;
            }
        }

        if(!isset($d->TIPO_CUENTA)){
            $m = "La variable TIPO_CUENTA no ha sido enviado.";
            return false;
        }else{
            if(!is_int($d->TIPO_CUENTA)){
                $m = "La variable TIPO_CUENTA debe ser numérico.";
                return false;
            }else{
                if($d->TIPO_CUENTA<0 || $d->TIPO_CUENTA>1){
                    $m = "La variable TIPO_CUENTA debe ser 0 empleado, 1 administrador.";
                    return false;
                }
            }
        }

        return true;
    }

    if(esValido($mensaje,$datos)){
        $identificador = $datos->USU_IDENTIFICADOR;
        $alias = $usuario->buscarAliasUsuario( $identificador , $exito , $code_error , $mensaje );
        if(!$exito){
            header('HTTP/1.0 400 Error');
            echo json_encode(array("error"=>$code_error, "mensaje"=>$mensaje, "exito"=>$exito));
        }else{
            $usuario->USU_USUARIO = $alias;
            $usuario->USU_CONTRASENIA = $datos->USU_CONTRASENIA;
            $usuario->USU_TIPO = $datos->TIPO_CUENTA;
            $IP = $usuario->obtenerIpUsuario();
            $usuario->login($IP, $usu_tipo, $usu_id, $usu_nombres, $usu_hash, $usu_estado, $usu_email, $mensaje, $exito);
            
            
        }
    }else{
        header('HTTP/1.0 400 Error');
        echo json_encode(array("estado"=>$usu_estado, "error"=>$code_error, "mensaje"=>$mensaje, "exito"=>false));
    }


?>