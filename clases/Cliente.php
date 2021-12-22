<?php
    include_once '../../util/mysqlnd.php';

    class Cliente{
        private $conn; 

        public $CLIENTE_ID;
        public $CLIENTE_DNI;
        public $CLIENTE_NOMBRES;
        public $CLIENTE_APELLIDOS;
        public $CLIENTE_TELEFONO;
        public $CLIENTE_DIRECCION;
        public $CLIENTE_CORREO;

        public function __construct($db){
            $this->conn = $db;
        }
        
        function registrarCliente(&$mensaje,&$code_error,$esJuridico,$DJ_RAZON_SOCIAL,$DJ_RUC,$DJ_TIPO_EMPRESA_ID){

            $query = "CALL SP_CREAR_CLIENTE(@VALIDACIONES,?,?,?,?,?,?,?,?,?,?)" ; 
            $queryValidaciones = "SELECT @VALIDACIONES"; 

            try {

                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("ssssssssss",$esJuridico,$this->CLIENTE_DNI,$this->CLIENTE_NOMBRES,$this->CLIENTE_APELLIDOS
                ,$this->CLIENTE_TELEFONO,$this->CLIENTE_DIRECCION,$DJ_RAZON_SOCIAL,$DJ_RUC,$DJ_TIPO_EMPRESA_ID,$this->CLIENTE_CORREO);
                if(!$stmt->execute()){

                    $code_error = "error_ejecucionQuery";
                    $mensaje = "Hubo un error al registrar un cliente.";
                    return false; 

                }else{

                    $stmtValidaciones = $this->conn->prepare($queryValidaciones);
                    $stmtValidaciones->execute();
                    $resultValidaciones = get_result($stmtValidaciones); 

                    if (count($resultValidaciones) > 0) {
                        //obtenemos verdadero o falso dependiendo si es que se repite el nro de comprobante de la guía que se ingresará 
                        $validaciones = array_shift($resultValidaciones)["@VALIDACIONES"];
                    }

                    switch ($validaciones) {
                        case 1:
                            $code_error = "error_existenciaDNI";
                            $mensaje = "El DNI ingresado ya existe.";
                            return false; 
                            break;
                        case 2:
                            $code_error = "error_existenciaRUC";
                            $mensaje = "El RUC ingresado ya existe.";
                            return false; 
                            break;
                        case 3:
                            $code_error = "error_noExistenciaTipoEmpresa";
                            $mensaje = "El tipo de empresa ingresado no existe.";
                            return false; 
                            break;
                        case 4:
                            $code_error = "error_ExistenciaRazonSocial";
                            $mensaje = "La razón social ingresada ya existe.";
                            return false; 
                            break;
                        case 5:
                            $code_error = "error_ExistenciaCorreoCliente";
                            $mensaje = "El correo ingresado ya existe.";
                            return false; 
                            break;
                    }

                    $mensaje = "Solicitud ejecutada con exito";
                    return true;
                    
                }

            } catch (Throwable $th) {
                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                return false;
            }

        }

        function listar(&$mensaje,&$code_error,&$exito){

            $queryClientesNormales = "
            SELECT c.* FROM CLIENTE c left outer join DATOS_JURIDICOS d on (c.CLIENTE_ID = d.CLIENTE_ID) WHERE
            d.CLIENTE_ID IS NULL
            ";
            $queryClientesJuridicos = "
            SELECT * FROM CLIENTE c right outer join DATOS_JURIDICOS d on (c.CLIENTE_ID = d.CLIENTE_ID);
            ";
            $datos = [];
            $datosNormales = []; 
            $datosJuridicos = [];  
            try {

                $stmt = $this->conn->prepare($queryClientesNormales);
                if(!$stmt->execute()){

                    $code_error = "error_ejecucionQuery";
                    $mensaje = "Hubo un error al listar el registro cliente normales.";
                    $exito = false; 

                }else{

                    $result = get_result($stmt); 
                
                    if (count($result) > 0) {                
                        while ($dato = array_shift($result)) {    
                            $datosNormales[] = $dato;
                        }
                    }

                    $stmta = $this->conn->prepare($queryClientesJuridicos);
                    if(!$stmta->execute()){

                        $code_error = "error_ejecucionQuery";
                        $mensaje = "Hubo un error al listar el registro cliente normales.";
                        $exito = false;

                    }else{

                        $result = get_result($stmta); 
                    
                        if (count($result) > 0) {                
                            while ($dato = array_shift($result)) {    
                                $datosJuridicos[] = $dato;
                            }
                        }
                        
                        $mensaje = "Solicitud ejecutada con exito";
                        $exito = true;
                    }

                }
                $datos = array("CLIENTES_NORMALES"=>$datosNormales, "CLIENTES_JURIDICOS"=>$datosJuridicos);
                return $datos; 
            } catch (Throwable $th) {
                
                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                $exito = false;
                return $datos;
            }

        }

        function editarCliente(&$mensaje,&$code_error,$esJuridico,$DJ_RAZON_SOCIAL,$DJ_RUC,$DJ_TIPO_EMPRESA_ID){

            $query = "CALL SP_EDITAR_CLIENTE(@VALIDACIONES,?,?,?,?,?,?,?,?,?,?,?)" ; 
            $queryValidaciones = "SELECT @VALIDACIONES"; 

            try {
                
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("sssssssssss",$esJuridico,$this->CLIENTE_DNI,$this->CLIENTE_NOMBRES,$this->CLIENTE_APELLIDOS
                ,$this->CLIENTE_TELEFONO,$this->CLIENTE_DIRECCION,$DJ_RAZON_SOCIAL,$DJ_RUC,$DJ_TIPO_EMPRESA_ID,$this->CLIENTE_ID,$this->CLIENTE_CORREO);
                if(!$stmt->execute()){

                    $code_error = "error_ejecucionQuery";
                    $mensaje = "Hubo un error al editar un cliente.";
                    return false; 

                }else{

                    $stmtValidaciones = $this->conn->prepare($queryValidaciones);
                    $stmtValidaciones->execute();
                    $resultValidaciones = get_result($stmtValidaciones); 

                    if (count($resultValidaciones) > 0) {
                        //obtenemos verdadero o falso dependiendo si es que se repite el nro de comprobante de la guía que se ingresará 
                        $validaciones = array_shift($resultValidaciones)["@VALIDACIONES"];
                    }

                    switch ($validaciones) {
                        case 1:
                            $code_error = "error_noExistenciaCliente";
                            $mensaje = "El id del cliente ingresado no existe.";
                            return false; 
                            break;
                        case 2:
                            $code_error = "error_existenciaDNI";
                            $mensaje = "El DNI ingresado ya existe.";
                            return false; 
                            break;
                        case 3:
                            $code_error = "error_existenciaRUC";
                            $mensaje = "El RUC ingresado ya existe.";
                            return false; 
                            break;
                        case 4:
                            $code_error = "error_noExistenciaTipoEmpresa";
                            $mensaje = "El tipo de empresa ingresado no existe.";
                            return false; 
                            break;
                        case 5:
                            $code_error = "error_ExistenciaRazónSocial";
                            $mensaje = "La razón social ingresada ya existe.";
                            return false; 
                            break;
                        case 6:
                            $code_error = "error_ExistenciaCorreoCliente";
                            $mensaje = "El correo ingresado ya existe.";
                            return false; 
                            break;
                    }

                    $mensaje = "Solicitud ejecutada con exito";
                    return true;
                    
                }
            } catch (Throwable $th) {
                
                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                return false;
            }

        }

        function listarClientes(&$mensaje,&$code_error,&$exito){
            $query = "
            SELECT CLI.CLIENTE_ID, CLI.CLIENTE_NOMBRES, CLI.CLIENTE_APELLIDOS, CLI.CLIENTE_TELEFONO, CLI.CLIENTE_DIRECCION, CLI.CLIENTE_CORREO, 
            if((SELECT COUNT(*) FROM DATOS_JURIDICOS WHERE CLIENTE_ID = CLI.CLIENTE_ID) = 1, DJ.DJ_RUC, CLI.CLIENTE_DNI) as CLIENTE_DNI FROM CLIENTE CLI 
            LEFT OUTER JOIN DATOS_JURIDICOS DJ ON (DJ.CLIENTE_ID = CLI.CLIENTE_ID)
            ";
            $datos = array();
            try {
                $stmt = $this->conn->prepare($query);
                if(!$stmt->execute()){
                    $code_error = "error_ejecucionQuery";
                    $mensaje = "Hubo un error al listar el registro cliente normales.";
                    $exito = false; 
                    return $datos;
                }

                $result = get_result($stmt); 
                if (count($result) > 0) {                
                    while ($dato = array_shift($result)) {    
                        $datos[] = $dato;
                    }
                }
                $mensaje = "Solicitud ejecutada con exito";
                    $exito = true;
                return $datos; 

            } catch (Throwable $th) {
                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                $exito = false;
                return $datos;
            }
        }
    }
?>