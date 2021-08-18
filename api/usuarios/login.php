<?php
    header('Access-Control-Allow-Origin: *'); //Change
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: POST");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    include_once '../../clases/Usuario.php';
    include_once '../../config/database.php';
    include_once '../../util/validaciones.php';

    if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
        exit;
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

    function esValido(&$m, $d){
        //Antes la variable se llamaba USU_ALIAS 
        if(!isset($d->USU_IDENTIFICADOR)){
            $m = "La variable USU_IDENTIFICADOR no ha sido enviada.";
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
            $m = "La variable USU_CONTRASENIA no ha sido enviada.";
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

        return true;
    }

    if(esValido($mensaje,$datos)){
        $TIPO_CUENTA = -1;
        $identificador = $datos->USU_IDENTIFICADOR;
        $alias = $usuario->buscarAliasUsuario($TIPO_CUENTA, $identificador , $exito , $code_error , $mensaje );
        if(!$exito || ($TIPO_CUENTA!==1 && $TIPO_CUENTA!==0) || $alias === ''){
            header('HTTP/1.0 400 Bad Request');
            echo json_encode(array("error"=>$code_error, "mensaje"=>$mensaje, "exito"=>$exito));
        }else{
            $usuario->USU_USUARIO = $alias;
            $usuario->USU_CONTRASENIA = $datos->USU_CONTRASENIA;
            $usuario->ROL_ID = $TIPO_CUENTA;
            $IP = $usuario->obtenerIpUsuario();
            $exitoLogin = $usuario->login($IP, $usu_id, $usu_nombres, $usu_hash, $usu_estado, $usu_email, $mensaje, $code_error);
            if($exitoLogin){
                header('HTTP/1.1 200 OK');
                echo json_encode(array("error"=>$code_error, "autenticar"=>$usu_hash, "id"=>$usu_id, "fullName"=>$usu_nombres, "estado"=>$usu_estado, "email"=>$usu_email, "mensaje"=>$mensaje, "exito"=>$exitoLogin));
            }else {
                header('HTTP/1.0 400 Bad Request');
                echo json_encode(array("error"=>$code_error, "mensaje"=>$mensaje, "exito"=>$exitoLogin));
            }
        }
    }else{
        $code_error = "error_deCampo";
        header('HTTP/1.0 400 Bad Request');
        echo json_encode(array("estado"=>$usu_estado, "error"=>$code_error, "mensaje"=>$mensaje, "exito"=>false));
    }


?>