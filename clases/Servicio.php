<?php
    include_once '../../util/mysqlnd.php';

    class Servicio{
        private $conn; 

        public $SERVICIO_ID; 
        public $MASCOTA_ID;
        public $SERVICIO_PRECIO; 
        public $SERVICIO_DESCRIPCION; 
        public $SERVICIO_FECHA_HORA; 
        public $SERVICIO_TIPO;
        public $SERVICIO_ESTADO; 
        public $TIPO_SERVICIO_ID; 
        public $SERVICIO_ADELANTO;
        public $MDP_ID;
        public $USU_ID;
        public $COMPROBANTE_ID;

        public function __construct($db){
            $this->conn = $db;
        }

        function registrarServicio(&$mensaje,&$code_error){

            $queryRegistrar = " 
                INSERT INTO SERVICIO(SERVICIO_PRECIO,SERVICIO_DESCRIPCION,SERVICIO_FECHA_HORA,SERVICIO_TIPO,SERVICIO_ESTADO,TIPO_SERVICIO_ID,MASCOTA_ID,SERVICIO_ADELANTO,MDP_ID,USU_ID,COMPROBANTE_ID)
                VALUES(?,?,?,?,0,?,?,?,?,?,?)
            ";

            $queryValidarMas =" SELECT * FROM MASCOTA WHERE MAS_ID = ?";
            $queryValidarTs =" SELECT * FROM TIPO_SERVICIO WHERE TIPO_SERVICIO_ID = ?";
            $queryValidarMDP =" SELECT * FROM METODO_PAGO WHERE MDP_ID = ?";
            $queryUsuId =" SELECT * FROM USUARIOS WHERE USU_ID = ?";
            $queryComprobanteId =" SELECT * FROM COMPROBANTE WHERE COMPROBANTE_ID = ?";
            $query = "SELECT (SELECT COUNT(*) FROM DATOS_JURIDICOS WHERE CLIENTE_ID = CLI.CLIENTE_ID) as TIPO_CLIENTE FROM MASCOTA MAS INNER JOIN CLIENTE CLI ON (MAS.CLIENTE_ID = CLI.CLIENTE_ID) WHERE MAS_ID = ? ;"; 
            $queryDisponibilidadHorarios = "
            SELECT * FROM SERVICIO WHERE (? 
            BETWEEN SERVICIO_FECHA_HORA AND ADDDATE(SERVICIO_FECHA_HORA, INTERVAL 1 hour) OR 
            ADDDATE(?, INTERVAL 1 hour) BETWEEN SERVICIO_FECHA_HORA AND ADDDATE(SERVICIO_FECHA_HORA, INTERVAL 1 hour))  
            AND SERVICIO_TIPO = 1  AND  SERVICIO_ESTADO = 0"; 

            try {
                $stmtUsuId = $this->conn->prepare($queryUsuId);
                $stmtUsuId->bind_param("s",$this->USU_ID);
                $stmtUsuId->execute();
                $resultUsuId = get_result($stmtUsuId); 

                if(count($resultUsuId) > 0){
                    $stmtTs = $this->conn->prepare($queryValidarTs);
                    $stmtTs->bind_param("s",$this->TIPO_SERVICIO_ID);
                    $stmtTs->execute();
                    $resultITs = get_result($stmtTs); 
                    
    
                    if (count($resultITs) > 0) {

                        $stmtComprobanteId = $this->conn->prepare($queryComprobanteId);
                        $stmtComprobanteId->bind_param("s",$this->COMPROBANTE_ID);
                        $stmtComprobanteId->execute();
                        $resultIComprobanteId = get_result($stmtComprobanteId); 
                        
                        if (count($resultIComprobanteId) > 0) {

                            $stmtMas = $this->conn->prepare($queryValidarMas);
                            $stmtMas->bind_param("s",$this->MASCOTA_ID);
                            $stmtMas->execute();
                            $stmtMas = get_result($stmtMas); 
                            
                            if (count($stmtMas) > 0) {
        
                                
                                $stmtTipoEmpleado = $this->conn->prepare($query);
                                $stmtTipoEmpleado->bind_param("s",$this->MASCOTA_ID);
                                $stmtTipoEmpleado->execute();
                                $resultTipoEmpleado = get_result($stmtTipoEmpleado); 

                                if (count($resultTipoEmpleado) > 0) {
                                    
                                    if($this->COMPROBANTE_ID == 1){
                                        
                                        if(array_shift($result)['TIPO_CLIENTE'] != 1){
                                            
                                            $code_error = "error_ComprobanteTipoCliente";
                                            $mensaje = "El tipo de cliente y el comprobante no concuerdan.";
                                            return false; 

                                        }

                                    }else{
                                        if($this->COMPROBANTE_ID == 2){
                                            
                                            if(array_shift($result)['TIPO_CLIENTE'] != 0){
                                            
                                                $code_error = "error_ComprobanteTipoCliente";
                                                $mensaje = "El tipo de cliente y el comprobante no concuerdan.";
                                                return false; 
    
                                            }
                                        }
                                    }
                                    
                                    // if(array_shift($result)['TIPO_CLIENTE'] == 1 && $this->COMPROBANTE_ID !=1 ){

                                    //     $code_error = "error_ComprobanteTipoCliente";
                                    //     $mensaje = "El tipo de cliente y el comprobante no concuerdan.";
                                    //     return false; 

                                    // }else{
                                    //     if(array_shift($result)['TIPO_CLIENTE'] == 0 && $this->COMPROBANTE_ID !=2 ){

                                    //         $code_error = "error_ComprobanteTipoCliente";
                                    //         $mensaje = "El tipo de cliente y el comprobante no concuerdan.";
                                    //         return false; 
                                    //     }

                                    // }

                                    $stmtMDP = $this->conn->prepare($queryValidarMDP);
                                    $stmtMDP->bind_param("s",$this->MDP_ID);
                                    $stmtMDP->execute();
                                    $resultMDP = get_result($stmtMDP); 
                                    
                                    if (count($resultMDP) > 0) {
            
                                        if($this->SERVICIO_TIPO == 1 ){
                                        
                                            $stmtHorarios = $this->conn->prepare($queryDisponibilidadHorarios);
                                            $stmtHorarios->bind_param("ss",$this->SERVICIO_FECHA_HORA,$this->SERVICIO_FECHA_HORA);
                                            $stmtHorarios->execute();
                                            $resultHorarios = get_result($stmtHorarios); 
                
                                            if(count($resultHorarios) < 3){
                
                                                $stmt = $this->conn->prepare($queryRegistrar);
                                                $stmt->bind_param("ssssssssss",$this->SERVICIO_PRECIO,$this->SERVICIO_DESCRIPCION,$this->SERVICIO_FECHA_HORA
                                                ,$this->SERVICIO_TIPO,$this->TIPO_SERVICIO_ID,$this->MASCOTA_ID,$this->SERVICIO_ADELANTO,$this->MDP_ID,$this->USU_ID,$this->COMPROBANTE_ID);
                                                if(!$stmt->execute()){
                
                                                    $code_error = "error_ejecucionQuery";
                                                    $mensaje = "Hubo un error registrar el servicio.";
                                                    return false; 
                
                                                }else{
                
                                                    $mensaje = "Solicitud ejecutada con exito";
                                                    return true;
                                                    
                                                }
                
                                            }else{
                                                
                                                $code_error = "error_conflictoHorarios";
                                                $mensaje = "No se pudo registrar la cita por conflicto de horarios.";
                                                return false; 
                
                                            }
                
                                        }else{
                                            $stmt = $this->conn->prepare($queryRegistrar);
                                            $stmt->bind_param("ssssssssss",$this->SERVICIO_PRECIO,$this->SERVICIO_DESCRIPCION,$this->SERVICIO_FECHA_HORA
                                            ,$this->SERVICIO_TIPO,$this->TIPO_SERVICIO_ID,$this->MASCOTA_ID,$this->SERVICIO_ADELANTO,$this->MDP_ID,$this->USU_ID,$this->COMPROBANTE_ID);
                                            if(!$stmt->execute()){
                
                                                $code_error = "error_ejecucionQuery";
                                                $mensaje = "Hubo un error registrar el servicio.";
                                                return false; 
                
                                            }else{
                
                                                $mensaje = "Solicitud ejecutada con exito";
                                                return true;
                                                
                                            }
                                        }
                                    }else{
            
                                        $code_error = "error_NoExistenciaDeMDP";
                                        $mensaje = "El id ingresado del método de pago no existe.";
                                        return false;
            
                                    }
            
                                }else{

                                    $code_error = "error_NoTipoCliente";
                                    $mensaje = "No se pudo obtener el tipo de cliente";
                                    return false;

                                }
                                
                            }else{
        
                                $code_error = "error_NoExistenciaDeMascota";
                                $mensaje = "El id ingresado de la mascota no existe.";
                                return false;
        
                            }
                        }else{

                            $code_error = "error_NoExistenciaComprobante";
                            $mensaje = "El id ingresado del comprobante no existe.";
                            return false;
    

                        }
    
                    }else{
                        $code_error = "error_NoExistenciaDeTipoServicio";
                        $mensaje = "El id ingresado del tipo de servicio no existe.";
                        return false;
                    }
                }else{
                    $code_error = "error_NoExistenciaDeUsuario";
                    $mensaje = "El id ingresado del usuario no existe.";
                    return false;
                }
                

            } catch (Throwable $th) {
                
                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                return false;

            }
        }
        
        function editarServicio(&$mensaje,&$code_error){

            $queryValidarIdServicio = "SELECT * FROM SERVICIO WHERE SERVICIO_ID = ?"; 
            $queryEditar ="
                UPDATE SERVICIO SET SERVICIO_PRECIO = ?, SERVICIO_DESCRIPCION = ?,SERVICIO_FECHA_HORA = ?, SERVICIO_TIPO = ?,
                TIPO_SERVICIO_ID = ?, MASCOTA_ID = ?, SERVICIO_ADELANTO = ?, MDP_ID = ? WHERE SERVICIO_ID = ?
            ";
            $queryValidarTs =" SELECT * FROM TIPO_SERVICIO WHERE TIPO_SERVICIO_ID = ?";
            $queryValidarMas =" SELECT * FROM MASCOTA WHERE MAS_ID = ?";
            $queryValidarMDP =" SELECT * FROM METODO_PAGO WHERE MDP_ID = ?";
            $queryDisponibilidadHorarios = "
            SELECT * FROM SERVICIO WHERE (? 
            BETWEEN SERVICIO_FECHA_HORA AND ADDDATE(SERVICIO_FECHA_HORA, INTERVAL 1 hour) OR 
            ADDDATE(?, INTERVAL 1 hour) BETWEEN SERVICIO_FECHA_HORA AND ADDDATE(SERVICIO_FECHA_HORA, INTERVAL 1 hour))  
            AND SERVICIO_TIPO = 1  AND  SERVICIO_ESTADO = 0 AND SERVICIO_ID <> ?"; 

            try {

                $stmtServicio = $this->conn->prepare($queryValidarIdServicio);
                $stmtServicio->bind_param("s",$this->SERVICIO_ID);
                $stmtServicio->execute();
                $resultServicio = get_result($stmtServicio); 
                
                if (count($resultServicio) > 0) {
                    $stmtTs = $this->conn->prepare($queryValidarTs);
                    $stmtTs->bind_param("s",$this->TIPO_SERVICIO_ID);
                    $stmtTs->execute();
                    $resultITs = get_result($stmtTs); 
                    
                    if (count($resultITs) > 0) {

                        $stmtMas = $this->conn->prepare($queryValidarMas);
                        $stmtMas->bind_param("s",$this->MASCOTA_ID);
                        $stmtMas->execute();
                        $stmtMas = get_result($stmtMas); 
                        
                        if (count($stmtMas) > 0) {

                            $stmtMDP = $this->conn->prepare($queryValidarMDP);
                            $stmtMDP->bind_param("s",$this->MDP_ID);
                            $stmtMDP->execute();
                            $resultMDP = get_result($stmtMDP); 
                            
                            if (count($resultMDP) > 0) {

                                if($this->SERVICIO_TIPO == 1 ){
                            
                                    $stmtHorarios = $this->conn->prepare($queryDisponibilidadHorarios);
                                    $stmtHorarios->bind_param("sss",$this->SERVICIO_FECHA_HORA,$this->SERVICIO_FECHA_HORA,$this->SERVICIO_ID);
                                    $stmtHorarios->execute();
                                    $resultHorarios = get_result($stmtHorarios); 
        
                                    if(count($resultHorarios) < 3){
    
                                        $stmt = $this->conn->prepare($queryEditar);
                                        $stmt->bind_param("sssssssss",$this->SERVICIO_PRECIO,$this->SERVICIO_DESCRIPCION,$this->SERVICIO_FECHA_HORA
                                        ,$this->SERVICIO_TIPO,$this->TIPO_SERVICIO_ID,$this->MASCOTA_ID,$this->SERVICIO_ADELANTO,$this->MDP_ID,$this->SERVICIO_ID);
                                        if(!$stmt->execute()){
    
                                            $code_error = "error_ejecucionQuery";
                                            $mensaje = "Hubo un error editar el servicio.";
                                            return false; 
    
                                        }else{
    
                                            $mensaje = "Solicitud ejecutada con exito";
                                            return true;
                                            
                                        }
    
                                    }else{
                                    
                                        $code_error = "error_conflictoHorarios";
                                        $mensaje = "No se pudo registrar la cita por conflicto de horarios.";
                                        return false; 
        
                                    }
                                }else{
                                
                                    $stmt = $this->conn->prepare($queryEditar);
                                    $stmt->bind_param("sssssssss",$this->SERVICIO_PRECIO,$this->SERVICIO_DESCRIPCION,$this->SERVICIO_FECHA_HORA
                                    ,$this->SERVICIO_TIPO,$this->TIPO_SERVICIO_ID,$this->MASCOTA_ID,$this->SERVICIO_ADELANTO,$this->MDP_ID,$this->SERVICIO_ID);
                                    if(!$stmt->execute()){
    
                                        $code_error = "error_ejecucionQuery";
                                        $mensaje = "Hubo un error editar el servicio.";
                                        return false; 
    
                                    }else{
    
                                        $mensaje = "Solicitud ejecutada con exito";
                                        return true;
                                        
                                    }
    
                                }

                            }else{

                                $code_error = "error_NoExistenciaDeMDP";
                                $mensaje = "El id ingresado del método de pago no existe.";
                                return false;

                            }

                            

                    }else{

                        $code_error = "error_NoExistenciaDeMascota";
                        $mensaje = "El id ingresado de la mascota no existe.";
                        return false;

                    }


                    }else{
                        $code_error = "error_NoExistenciaDeTipoServicio";
                        $mensaje = "El id ingresado del tipo de servicio no existe.";
                        return false;
                    }
                }else{
                    $code_error = "error_NoExistenciaServicio";
                    $mensaje = "El id del servicio ingresado no existe.";
                    return false;
                }

            } catch (Throwable $th) {
                
                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                return false;

            }
        }

        function editarServicioPorEmpleado(&$mensaje,&$code_error){

            $queryValidarIdServicio = "SELECT * FROM SERVICIO WHERE SERVICIO_ID = ?";
            $queryActualizarServicio = "UPDATE SERVICIO SET SERVICIO_FECHA_HORA = ? , SERVICIO_TIPO = 1 WHERE SERVICIO_ID = ? ";
           
            $queryDisponibilidadHorarios = "
            SELECT * FROM SERVICIO WHERE (? 
            BETWEEN SERVICIO_FECHA_HORA AND ADDDATE(SERVICIO_FECHA_HORA, INTERVAL 1 hour) OR 
            ADDDATE(?, INTERVAL 1 hour) BETWEEN SERVICIO_FECHA_HORA AND ADDDATE(SERVICIO_FECHA_HORA, INTERVAL 1 hour))  
            AND SERVICIO_TIPO = 1  AND  SERVICIO_ESTADO = 0 AND SERVICIO_ID <> ?"; 
            try {
                
                $stmtValidarIdServicio = $this->conn->prepare($queryValidarIdServicio);
                $stmtValidarIdServicio->bind_param("s",$this->SERVICIO_ID);
                $stmtValidarIdServicio->execute();
                $resultValidarIdServicio = get_result($stmtValidarIdServicio); 

                if(count($resultValidarIdServicio) > 0 )
                {   

                    $stmtHorarios = $this->conn->prepare($queryDisponibilidadHorarios);
                    $stmtHorarios->bind_param("sss",$this->SERVICIO_FECHA_HORA,$this->SERVICIO_FECHA_HORA,$this->SERVICIO_ID);
                    $stmtHorarios->execute();
                    $resultHorarios = get_result($stmtHorarios); 

                    if(count($resultHorarios) < 3){

                        $stmt = $this->conn->prepare($queryActualizarServicio);
                        $stmt->bind_param("ss",$this->SERVICIO_FECHA_HORA,$this->SERVICIO_ID);
                        if(!$stmt->execute()){

                            $code_error = "error_ejecucionQuery";
                            $mensaje = "Hubo un error al actualizar el servicio.";
                            return false; 

                        }else{

                            $mensaje ="Actualización realizada con éxito.";
                            return true; 
                        }

                    }else{
                        $code_error = "error_conflictoHorarios";
                        $mensaje = "No se pudo registrar la cita por conflicto de horarios.";
                        return false; 
                    }

                    
                }else{
                    $code_error = "error_NoExistenciaServicio";
                    $mensaje = "El id del servicio ingresado no existe.";
                    return false;
                }
            } catch (Throwable $th) {
               
                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                return false;

            }
        }

        function listarServiciosPendientes(&$mensaje,&$code_error,&$exito){

            $query = "SELECT SER.*,MAS.*,CLI.* ,TS.TIPO_SERVICIO_NOMBRE FROM SERVICIO SER 
            INNER JOIN TIPO_SERVICIO TS ON (SER.TIPO_SERVICIO_ID = TS.TIPO_SERVICIO_ID)
            INNER JOIN MASCOTA MAS ON (SER.MASCOTA_ID = MAS.MAS_ID) 
            INNER JOIN CLIENTE CLI ON(MAS.CLIENTE_ID = CLI.CLIENTE_ID) AND SER.SERVICIO_ESTADO = 0 ORDER BY SERVICIO_FECHA_HORA ASC"; 
            $datos = []; 
            try {
                
                $stmt = $this->conn->prepare($query);
                if(!$stmt->execute()){

                    $code_error = "error_ejecucionQuery";
                    $mensaje = "Hubo un error al listar los servicios.";
                    $exito = false; 

                }else{

                    $result = get_result($stmt); 
                
                    if (count($result) > 0) {                
                        while ($dato = array_shift($result)) {    
                            $datos[] = $dato;
                        }
                    }

                    $mensaje = "Solicitud ejecutada con exito";
                    $exito = true;

                }
                
                return $datos;

            } catch (Throwable $th) {
                
                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                $exito = false;
                return $datos; 
            }
        }

        function terminarServicio(&$mensaje,&$code_error){

            $query = "CALL SP_FINALIZAR_SERVICIO(@VALIDACIONES,?)" ; 
            $queryValidaciones = "SELECT @VALIDACIONES"; 

            try {


                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("s",$this->SERVICIO_ID);
                if(!$stmt->execute()){

                    $code_error = "error_ejecucionQuery";
                    $mensaje = "Hubo un error terminar el servicio.";
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
                            $code_error = "error_noExistenciaIdServicio";
                            $mensaje = "El id del servicio ingresado no existe.";
                            return false; 
                            break;
                        case 2:
                            $code_error = "error_servicioTerminado";
                            $mensaje = "El servicio ya está terminado.";
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

    }
?>