<?php
    include_once '../../util/mysqlnd.php';

    class Cliente{
        private $conn; 

        public function __construct($db){
            $this->conn = $db;
        }
        
        function registrar(&$mensaje,&$code_error,$esJuridico){

            $query = "" ; 

            try {

                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("s",$this->PROV_ID);
                if(!$stmt->execute()){

                    $code_error = "error_ejecucionQuery";
                    $mensaje = "Hubo un error al registrar un cliente.";
                    return false; 

                }else{

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

        function editar(&$mensaje,&$code_error,$esJuridico){

            $queryValidarClienteId ="SELECT * FROM CLIENTE WHERE CLIENTE_ID = ?";
            $queryEditar = "";

            try {
                
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("s",$this->PROV_ID);
                if(!$stmt->execute()){

                    $code_error = "error_ejecucionQuery";
                    $mensaje = "Hubo un error al editar un cliente.";
                    return false; 

                }else{

                    $mensaje = "Solicitud ejecutada con exito";
                    return true;
                    
                }
            } catch (Throwable $th) {
                
                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                return false;
            }

        }
    }
?>