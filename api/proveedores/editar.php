<?php
    header('Access-Control-Allow-Origin: *');
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: PUT");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: *"); //To allow for sending custom headers


    //Include database and classes files
    include_once '../../config/database.php';
    include_once '../../clases/Proveedor.php';
    include_once '../../util/validaciones.php';
    //COMPROBAMOS QUE EL METODO USADO SEA GET
    if($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
        exit;
    }


    $data = json_decode(file_get_contents("php://input"));
    $mensaje = '';
    $exito = "";
    $code_error = null;

    //Instantiate database
    $database = new Database();
    $db = $database->getConnection();
    
    $proveedorC = new Proveedor($db);


    function esValido($d,&$m){
        if(!isset($d->PROV_ID)){
            $mensaje = "la variable PROV_ID no ha sido enviada.";
            return false;
            echo "hola";
        }else{  
            if($d->PROV_ID == ""){
                $mensaje = "la variable PROV_ID no puede estar vacía o ser null.";
                return false; 
            }else{
                if(!is_numeric($d->PROV_ID)){
                   $mensaje = "la variable PROV_ID solo acepta caracteres numéricos.";
                   return false;  
                }else{
                    if($d->PROV_ID < 1 ){
                        $mensaje = "la variable PROV_ID no puede ser menor o igual a 0.";
                        return false; 
                    }
                }
            }
        }
        if(!isset($d->PROV_RUC)){
            $m = "El campo PROV_RUC no ha sido enviado.";
            return false;
        }else{
            if($d->PROV_RUC!=""){
                if(!ctype_digit($d->PROV_RUC)){
                    $m = "El ruc del proveedor debe estar conformado por caracteres numéricos.";
                    return false;
                }
                if(obtenerCantidadDeCaracteres($d->PROV_RUC)!=11){
                    $m = "El ruc del proveedor debe tener una longitud de 11 caracteres numericos.";
                    return false;
                }
            }else{
                $m = "El campo ruc es un campo obligatorio.";
                    return false;
            }
        }

        if(!isset($d->PROV_NUMERO_CONTACTO)){
            $m = "El campo USU_CELULAR no ha sido enviado.";
            return false;
        }else{
            if($d->PROV_NUMERO_CONTACTO!=""){
                if(obtenerCantidadDeCaracteres($d->PROV_NUMERO_CONTACTO)<=20){
                    if(!verificarCelular($d->PROV_NUMERO_CONTACTO)){
                        $m = "*Campo obligatorio* El numero de celular no tiene el formato permitido.";
                        return false;
                    }
                }else{
                    $m = "*Campo obligatorio* El numero de celular no debe exceder de 20 caracteres.";
                    return false;
                }
            }else{
                $m = "*Campo obligatorio* El numero de celular del cliente no puede estar vacio.";
                return false;
            }
        }

        if(!isset($d->PROV_EMPRESA_PROVEEDORA)){
            $m = "El campo PROV_EMPRESA_PROVEEDORA no ha sido enviado.";
            return false;
        }else{
            if($d->PROV_EMPRESA_PROVEEDORA!=""){
                if(obtenerCantidadDeCaracteres($d->PROV_EMPRESA_PROVEEDORA)>100){
                    $m = "*Campo obligatorio*El nombre de la empresa proveedora no debe exceder los 100 caracteres.";
                    return false;
                }else{
                    foreach ( str_split($d->PROV_EMPRESA_PROVEEDORA) as $caracter) {
                        if(is_numeric($caracter)){
                            $m = "*Campo obligatorio* El nombre de la empresa proveedora no debe tener números.";
                            return false;
                        }
                    }
                }
            }else{
                $m = "El nombre de la empresa proveedora es un campo obligatorio";
                    return false;
            } 
        }

        return true;
    }

    if(esValido($data,$mensaje)){
        
        $proveedorC->PROV_ID = $data->PROV_ID;
        $proveedorC->PROV_RUC = $data->PROV_RUC;
        $proveedorC->PROV_EMPRESA_PROVEEDORA = $data->PROV_EMPRESA_PROVEEDORA;
        $proveedorC->PROV_NUMERO_CONTACTO = $data->PROV_NUMERO_CONTACTO;
        $exito =  $proveedorC->actualizarProveedor($mensaje,$code_error);

        if($exito == true)
            header('HTTP/1.1 200 OK');
        else{
            header('HTTP/1.1 400 Bad Request');
        }
            
        echo json_encode( array("error"=>$code_error,"mensaje"=>$mensaje,"exito"=>$exito));
    }else{
        $code_error = "error_deCampo";
        echo json_encode(array("error"=>$code_error,"mensaje"=>$mensaje, "exito"=>$exito));
        header('HTTP/1.1 400 Bad Request');
    }


?>