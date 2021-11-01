<?php
    include_once '../../util/mysqlnd.php';
    class Mascota{
        private $conn; 

        public $MAS_ID;
        public $MAS_NOMBRE; 
        public $MAS_RAZA;
        public $MAS_COLOR;
        public $MAS_ESPECIE; 
        public $MAS_ATENCIONES;
        public $MAS_ESTADO;
        public $CLIENTE_ID;
        

        public function __construct($db){
            $this->conn = $db;
        }

        function registrarMascota(&$mensaje,&$code_error){

            $query  = "
            INSERT INTO MASCOTA(MAS_NOMBRE,MAS_RAZA,MAS_COLOR,MAS_ESPECIE,MAS_ATENCIONES,MAS_ESTADO,CLIENTE_ID)
            VALUES(?,?,?,?,?,?,?);
            "; 
            $queryValidarIdCliente = "SELECT * FROM CLIENTE WHERE CLIENTE_ID";
                
            try {
                
                $stmtIdCliente = $this->conn->prepare($queryValidarIdCliente);
                $stmtIdCliente->bind_param("s",$this->CLIENTE_ID);
                $stmtIdCliente->execute();
                $resultIdCliente = get_result($stmtIdCliente); 
                
                if (count($resultIdCliente) > 0) {
                    
                    $stmt = $this->conn->prepare($query);
                    $stmt->bind_param("sssssss",$this->MAS_NOMBRE,$this->MAS_RAZA,$this->MAS_COLOR
                    ,$this->MAS_ESPECIE,$this->MAS_ATENCIONES,$this->MAS_ESTADO,$this->CLIENTE_ID);
                    if(!$stmt->execute()){

                        $code_error = "error_ejecucionQuery";
                        $mensaje = "Hubo un error al registrar la mascota.";
                        return false; 

                    }else{

                        $mensaje = "Solicitud ejecutada con exito";
                        return true;

                    }

                }else{

                    $code_error = "error_noExistenciaIdCliente";
                    $mensaje = "El id del cliente no existe.";
                    return false;

                }

            } catch (Throwable $th) {

                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                return false;
            
            }
        }

        function editarMascota(&$mensaje,&$code_error){

            $query  = "
            UPDATE MASCOTA SET MAS_NOMBRE = ?,MAS_RAZA = ?,MAS_COLOR = ?,MAS_ESPECIE = ?,MAS_ATENCIONES = ?,CLIENTE_ID = ?, MAS_ESTADO = ?
            WHERE MAS_ID = ?";
            $queryValidarIdMascota = "SELECT * FROM MASCOTA WHERE MAS_ID = ?";
            $queryValidarIdCliente = "SELECT * FROM CLIENTE WHERE CLIENTE_ID = ?";

            try {
                $stmtIdMascota = $this->conn->prepare($queryValidarIdMascota);
                $stmtIdMascota->bind_param("s",$this->MAS_ID);
                $stmtIdMascota->execute();
                $resultIdMascota = get_result($stmtIdMascota); 
                
                if (count($resultIdMascota) > 0) {

                    $stmtIdCliente = $this->conn->prepare($queryValidarIdCliente);
                    $stmtIdCliente->bind_param("s",$this->CLIENTE_ID);
                    $stmtIdCliente->execute();
                    $resultIdCliente = get_result($stmtIdCliente); 
                    
                    if (count($resultIdCliente) > 0) {
                        
                        $stmt = $this->conn->prepare($query);
                        $stmt->bind_param("ssssssss",$this->MAS_NOMBRE,$this->MAS_RAZA,$this->MAS_COLOR
                        ,$this->MAS_ESPECIE,$this->MAS_ATENCIONES,$this->CLIENTE_ID,$this->MAS_ESTADO,$this->MAS_ID);
                        if(!$stmt->execute()){
    
                            $code_error = "error_ejecucionQuery";
                            $mensaje = "Hubo un error al registrar la mascota.";
                            return false; 
    
                        }else{
    
                            $mensaje = "Solicitud ejecutada con exito";
                            return true;
    
                        }
    
                    }else{
    
                        $code_error = "error_noExistenciaIdCliente";
                        $mensaje = "El id del cliente no existe.";
                        return false;
    
                    }
                }else{

                    $code_error = "error_noExistenciaIdMascota";
                    $mensaje = "El id de la mascota no existe.";
                    return false;
                
                }


            } catch (Throwable $th) {

                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                $exito = false;
                return $datos;
            
            }
        }

        function listar(&$mensaje,&$code_error,&$exito){
            $query = "SELECT * FROM MASCOTA MAS INNER JOIN CLIENTE CLI ON (MAS.CLIENTE_ID = CLI.CLIENTE_ID);";
            $datos = [];  
            try {

                $stmt = $this->conn->prepare($query);
                $stmt->execute();
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

        function listarActivos(&$mensaje,&$code_error,&$exito){
            $query = "SELECT * FROM MASCOTA MAS INNER JOIN CLIENTE CLI ON (MAS.CLIENTE_ID = CLI.CLIENTE_ID) WHERE MAS_ESTADO = 1";
            $datos = [];  
            try {

                $stmt = $this->conn->prepare($query);
                $stmt->execute();
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


        function cambiarEstadoMascota(&$mensaje,&$code_error){

            $query_="SELECT * FROM MASCOTA where MAS_ID = ?";
            $query = "UPDATE MASCOTA SET MAS_ESTADO = ? WHERE MAS_ID = ?";

            try {

                //COMPROBAMOS DE QUE EXISTA EL ID DE LA MASCOTA
                $stmt_ = $this->conn->prepare($query_);
                $stmt_->bind_param("s",$this->MAS_ID);
                $stmt_->execute();
                $result_ = get_result($stmt_);

                if(count($result_) > 0 ){
                    //EJECTUAMOS LA CONSULTA PARA ACTUALIZAR EL ESTADO DE LA MASCOTA 
                    $stmt = $this->conn->prepare($query);
                    $stmt->bind_param("ss",$this->MAS_ESTADO,$this->MAS_ID);
                    $stmt->execute();

                    $mensaje = "Se ha actualizado el estado de la MASCOTA con éxito";
                    return true;
                }else{
                    $code_error = "error_noExistenciaId";
                    $mensaje = "El ID de la MASCOTA ingresado no existe.";
                    return false; 
                }   
               
            } catch (Throwable $th) {
                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                return false;
            }

        }

    }
?>