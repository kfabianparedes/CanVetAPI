<?php

    include_once '../../util/mysqlnd.php';

    class Venta{

        private $conn;

        public $VENTA_ID;
        public $VENTA_FECHA_EMISION_COMPROBANTE;
        public $VENTA_FECHA_REGISTRO;
        public $VENTA_NRO_SERIE;
        public $VENTA_NRO_COMPROBANTE;
        public $VENTA_SUBTOTAL;
        public $VENTA_TOTAL;
        public $VENTA_ESTADO;
        public $COMPROBANTE_ID;
        public $USU_ID;
        public $METODO_DE_PAGO_ID;
        public $CLIENTE_ID;

        public function __construct($db){
            $this->conn = $db;
        }

        function registrar(&$mensaje,&$code_error,&$ventaId){

            $query = "CALL SP_INSERTAR_VENTA(@P_VENTA_ID,@VAL_NRO_COMPROBANTE,@VAL_COMPROBANTE_ID,@VAL_USU_ID,@VAL_MDP_ID,@VAL_CLI_ID,?,?,?,?,?,?,?,?,?,?)"; 
            $queryComprobanteID = "SELECT @VAL_COMPROBANTE_ID"; 
            $queryUsuID = "SELECT @VAL_USU_ID"; 
            $queryVentaId = "SELECT @P_VENTA_ID";
            $queryMdpID = "SELECT @VAL_MDP_ID"; 
            $queryCliId = "SELECT @VAL_CLI_ID"; 
            $queryNroComprobante = "SELECT @VAL_NRO_COMPROBANTE"; 

            try {

                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("ssssssssss",$this->VENTA_FECHA_EMISION_COMPROBANTE,
                $this->VENTA_NRO_SERIE,$this->VENTA_NRO_COMPROBANTE,$this->VENTA_FECHA_REGISTRO,
                $this->VENTA_SUBTOTAL,$this->VENTA_TOTAL,$this->COMPROBANTE_ID,$this->USU_ID,$this->METODO_DE_PAGO_ID,$this->CLIENTE_ID);
                //verificamos que se haya realizado correctamente el ingreso de la compra
                if(!$stmt->execute()){

                    $code_error = "error_ejecucionQuery";
                    $mensaje = "Hubo un error al ingresar la venta.";
                    return false; 

                }else{

                    //verificar si existe el comprobante id 
                    $stmtComprobanteId = $this->conn->prepare($queryComprobanteID);
                    $stmtComprobanteId->execute();
                    $resultComprobanteId = get_result($stmtComprobanteId); 

                    //verificar si existe el usu id 
                    $stmtUsuID = $this->conn->prepare($queryUsuID);
                    $stmtUsuID->execute();
                    $resultUsuId = get_result($stmtUsuID); 

                    //verificar si existe el método de pago id 
                    $stmtMdpID = $this->conn->prepare($queryMdpID);
                    $stmtMdpID->execute();
                    $resultMdpID = get_result($stmtMdpID); 

                    //verificar si existe el cliente id 
                    $stmtCliId = $this->conn->prepare($queryCliId);
                    $stmtCliId->execute();
                    $resultCliId = get_result($stmtCliId); 
                    
                    //verificar si no se repite ningpun Nro de comprobante igual al ingresado 
                    $stmtNroComprobante = $this->conn->prepare($queryNroComprobante);
                    $stmtNroComprobante->execute();
                    $resultNroComprobante = get_result($stmtNroComprobante); 

                    if (count($resultComprobanteId) > 0) {
                        //obtenemos verdadero o falso dependiendo si es que se repite el nro de comprobante de la guía que se ingresará 
                        $valComprobanteId = array_shift($resultComprobanteId)["@VAL_COMPROBANTE_ID"];
                    }

                    if (count($resultUsuId) > 0) {
                        //obtenemos verdadero o falso dependiendo si es que se repite el nro de comprobante de la compra que se va a ingresar
                        $valUsuId = array_shift($resultUsuId)["@VAL_USU_ID"];
                    }

                    if (count($resultMdpID) > 0) {
                        //obtenemos verdadero o falso dependiendo si es que se repite el nro de comprobante de la guía que se ingresará 
                        $valMdpId = array_shift($resultMdpID)["@VAL_MDP_ID"];
                    }

                    if (count($resultCliId) > 0) {
                        //obtenemos verdadero o falso dependiendo si es que se repite el nro de comprobante de la compra que se va a ingresar
                        $valCliId = array_shift($resultCliId)["@VAL_CLI_ID"];
                    }
                    if (count($resultNroComprobante) > 0) {
                        //obtenemos verdadero o falso dependiendo si es que se repite el nro de comprobante de la guía que se ingresará 
                        $valNroComprobante = array_shift($resultNroComprobante)["@VAL_NRO_COMPROBANTE"];
                    }

                    if(!$valComprobanteId){

                        $code_error = "error_NoExistenciaComprobanteId";
                        $mensaje = "El id del comprobante ingresado no existe.";
                        return false;

                    }else{
                        if(!$valUsuId){

                            $code_error = "error_NoExistenciaUsuarioId";
                            $mensaje = "El id del usuario ingresado no existe.";
                            return false;

                        }else{
                            if(!$valMdpId){

                                $code_error = "error_NoExistenciaMétodoDePagoId";
                                $mensaje = "El id del método de pago ingresado no existe.";
                                return false;

                            }else{
                                if(!$valCliId){

                                    $code_error = "error_NoExistenciaClienteId";
                                    $mensaje = "El id del cliente ingresado no existe.";
                                    return false;

                                }
                                else{
                                    if(!$valNroComprobante){

                                        $code_error = "error_existenciaNroComprobante";
                                        $mensaje = "El número de comprobante del número de serie ingresado ya existe.";
                                        return false;

                                    }else{

                                        //verificar si existe el comprobante id 
                                        $stmtVentaId = $this->conn->prepare($queryVentaId);
                                        if(!$stmtVentaId->execute()){

                                            $code_error = "error_ejecucionQuery";
                                            $mensaje = "Hubo un error al obtener el id de venta generado.";
                                            return false; 

                                        }else{
                                            
                                            $resultVentaId = get_result($stmtVentaId);

                                            if (count($resultVentaId) > 0) {
                                                //se guarda el id de la compra creada en una variable
                                                $ventaId = array_shift($resultVentaId)["@P_VENTA_ID"];
                                            }else{
                                                $code_error = "error_ejecucionQuery";
                                                $mensaje = "Hubo un error al obtener el id de venta generado.";
                                                return false; 
                                            }
                                        }
                                    }
                                   
                                }
                            }
                            
                        }
                    }
                    $mensaje = "La solicitud se realizó con éxito";
                    return true; 

                }

                
            } catch (Throwable $th) {

                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                return false;

            }
        }

        function listar(&$mensaje,&$code_error,&$exito){

            $query = "SELECT * FROM VENTA";
            $datos = [];

            try {
                
                $stmt = $this->conn->prepare($query);
                if(!$stmt->execute()){

                    $code_error = "error_ejecucionQuery";
                    $mensaje = "Hubo un error al listar el registro de ventas.";
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
                return $datos ;

            }
        }
        
        function gananciasDiariasVenta(&$mensaje,&$code_error,&$exito){

            $queryEfectivo = "SELECT * FROM VENTA WHERE VENTA_FECHA_REGISTRO = ? AND METODO_DE_PAGO_ID = 1" ; 
            $queryTarjeta = "SELECT * FROM VENTA WHERE VENTA_FECHA_REGISTRO = ? AND METODO_DE_PAGO_ID = 2" ; 
            $queryYape = "SELECT * FROM VENTA WHERE VENTA_FECHA_REGISTRO = ? AND METODO_DE_PAGO_ID = 3" ; 
            $queryServicioEfectivo = 'SELECT * FROM SERVICIO WHERE DATE_FORMAT(SERVICIO_FECHA_HORA,"%Y-%m-%d") = ? 
             AND MDP_ID = 1 AND SERVICIO_ESTADO = 1 ' ; 
             $queryServicioTarjeta = 'SELECT * FROM SERVICIO WHERE DATE_FORMAT(SERVICIO_FECHA_HORA,"%Y-%m-%d") = ? 
             AND MDP_ID = 1 AND SERVICIO_ESTADO = 2 ' ; 
             $queryServicioYape = 'SELECT * FROM SERVICIO WHERE DATE_FORMAT(SERVICIO_FECHA_HORA,"%Y-%m-%d") = ? 
             AND MDP_ID = 1 AND SERVICIO_ESTADO = 3 ' ; 

            $datosVentasTarjeta = 0;
            $datosVentasEfectivo = 0;
            $datosVentasYape = 0;
            $datosServicioEfectivo = 0;
            $datosServicioTarjeta = 0;
            $datosServicioYape = 0;
            try {
                
                //ventas efectivo
                $stmt = $this->conn->prepare($queryEfectivo);
                $stmt->bind_param("s",$this->VENTA_FECHA_REGISTRO);
                if(!$stmt->execute()){

                    $code_error = "error_ejecucionQuery";
                    $mensaje = "Hubo un error al listar el monto ganado en ventas con efectivo.";
                    $exito = false; 

                }else{

                    $result = get_result($stmt); 
                
                    if (count($result) > 0) {                
                        while ($dato = array_shift($result)) {    
                            $datosVentasEfectivo += $dato["VENTA_TOTAL"];
                        }
                    }
                    //servicios tarjeta
                    $stmt = $this->conn->prepare($queryTarjeta);
                    $stmt->bind_param("s",$this->VENTA_FECHA_REGISTRO);
                    if(!$stmt->execute()){

                        $code_error = "error_ejecucionQuery";
                        $mensaje = "Hubo un error al listar el monto ganado en ventas con tarjeta.";
                        $exito = false; 

                    }else{

                        $result = get_result($stmt); 
                    
                        if (count($result) > 0) {                
                            while ($dato = array_shift($result)) {    
                                $datosVentasTarjeta += $dato["VENTA_TOTAL"];
                            }
                        }
                        //servicios yape 
                        $stmt = $this->conn->prepare($queryYape);
                        $stmt->bind_param("s",$this->VENTA_FECHA_REGISTRO);
                        if(!$stmt->execute()){

                            $code_error = "error_ejecucionQuery";
                            $mensaje = "Hubo un error al listar el monto ganado en ventas con yape.";
                            $exito = false; 

                        }else{

                            $result = get_result($stmt); 
                        
                            if (count($result) > 0) {                
                                while ($dato = array_shift($result)) {    
                                    $datosVentasYape += $dato["VENTA_TOTAL"];
                                }
                            }
                            //servicios
                            //efectivo
                            $stmt = $this->conn->prepare($queryServicioEfectivo);
                            $stmt->bind_param("s",$this->VENTA_FECHA_REGISTRO);
                            if(!$stmt->execute()){

                                $code_error = "error_ejecucionQuery";
                                $mensaje = "Hubo un error al listar el monto ganado en servicios con efectivo.";
                                $exito = false; 

                            }else{

                                $result = get_result($stmt); 
                            
                                if (count($result) > 0) {                
                                    while ($dato = array_shift($result)) {    
                                        $datosServicioEfectivo += $dato["SERVICIO_PRECIO"];
                                    }
                                }
                                //servicios tarjeta
                                $stmt = $this->conn->prepare($queryServicioTarjeta);
                                $stmt->bind_param("s",$this->VENTA_FECHA_REGISTRO);
                                if(!$stmt->execute()){

                                    $code_error = "error_ejecucionQuery";
                                    $mensaje = "Hubo un error al listar el monto ganado en servicios con tarjeta.";
                                    $exito = false; 

                                }else{

                                    $result = get_result($stmt); 
                                
                                    if (count($result) > 0) {                
                                        while ($dato = array_shift($result)) {    
                                            $datosServicioTarjeta += $dato["SERVICIO_PRECIO"];
                                        }
                                    }
                                    //servicios yape
                                    $stmt = $this->conn->prepare($queryServicioYape);
                                    $stmt->bind_param("s",$this->VENTA_FECHA_REGISTRO);
                                    if(!$stmt->execute()){

                                        $code_error = "error_ejecucionQuery";
                                        $mensaje = "Hubo un error al listar el monto ganado en servicios con yape.";
                                        $exito = false; 

                                    }else{

                                        $result = get_result($stmt); 
                                    
                                        if (count($result) > 0) {                
                                            while ($dato = array_shift($result)) {    
                                                $datosServicioYape += $dato["SERVICIO_PRECIO"];
                                            }
                                        }
                                    
                                    $mensaje = "Solicitud ejecutada con exito";
                                    $exito = true;
                            
                                    }
                                }
                            }
                        }
                    }
                }
                $datos = array("gananciasVentasEfectivo" => $datosVentasEfectivo, "gananciasVentasTarjeta" => $datosVentasTarjeta, "gananciasVentasYape" => $datosVentasYape,
                "gananciasServiciosEfectivo" => $datosServicioEfectivo, "gananciasServiciosTarjeta" => $datosServicioTarjeta, "gananciasServiciosYape" => $datosServicioYape);

                return $datos;

            } catch (Throwable $th) {

                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                $exito = false;
                $datos = array("gananciasVentasEfectivo" => $datosVentasEfectivo, "gananciasVentasTarjeta" => $datosVentasTarjeta, "gananciasVentasYape" => $datosVentasYape,
                "gananciasServiciosEfectivo" => $datosServicioEfectivo, "gananciasServiciosTarjeta" => $datosServicioTarjeta, "gananciasServiciosYape" => $datosServicioYape);
                return $datos ;
            }
        }

        function gananciasDiariasVentaPorUsuario(&$mensaje,&$code_error,&$exito){

            $query = "SELECT * FROM VENTA WHERE VENTA_FECHA_REGISTRO = ? AND USU_ID = ?"; 
            $queryValidarUsuId = "SELECT * FROM USUARIOS WHERE USU_ID = ?";
            $datos = 0;
            try {
                $stmtUsuId = $this->conn->prepare($queryValidarUsuId);
                $stmtUsuId->bind_param("s",$this->USU_ID);
                $stmtUsuId->execute();
                $resultUsuId = get_result($stmtUsuId);
                if(count($resultUsuId) > 0 ){

                    $stmt = $this->conn->prepare($query);
                    $stmt->bind_param("ss",$this->VENTA_FECHA_REGISTRO,$this->USU_ID);
                    if(!$stmt->execute()){
    
                        $code_error = "error_ejecucionQuery";
                        $mensaje = "Hubo un error al listar el registro de ventas.";
                        $exito = false; 
    
                    }else{
    
                        $result = get_result($stmt); 
                    
                        if (count($result) > 0) {                
                            while ($dato = array_shift($result)) {    
                                $datos += $dato["VENTA_TOTAL"];
                            }
                        }
    
                        $mensaje = "Solicitud ejecutada con exito";
                        $exito = true;
                        
                    }
                }else{

                    $code_error = "error_existenciaUsuario";
                    $mensaje = "El usuario ingresado no existe.";
                    $exito = false; 

                }

               

                return $datos;

            } catch (Throwable $th) {

                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                $exito = false;
                return $datos ;
            }
        }

        function gananciasSemanales(&$mensaje,&$code_error,&$exito){
            
            $query = "SELECT * FROM VENTA WHERE VENTA_FECHA_REGISTRO =?"; 
            $queryServicio = 'SELECT * FROM SERVICIO WHERE DATE_FORMAT(SERVICIO_FECHA_HORA,"%Y-%m-%d") = ? AND SERVICIO_ESTADO = 1';
            $datosVentas = 0;
            $datosServicios = 0; 
            try {
                

                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("s",$this->VENTA_FECHA_REGISTRO);
                if(!$stmt->execute()){

                    $code_error = "error_ejecucionQuery";
                    $mensaje = "Hubo un error al listar el registro de ventas.";
                    $exito = false; 

                }else{

                    $result = get_result($stmt); 
                
                    if (count($result) > 0) {                
                        while ($dato = array_shift($result)) {    
                            $datosVentas += $dato["VENTA_TOTAL"];
                        }
                    }

                    $mensaje = "Solicitud ejecutada con exito";

                    $stmt = $this->conn->prepare($queryServicio);
                    $stmt->bind_param("s",$this->VENTA_FECHA_REGISTRO);
                    if(!$stmt->execute()){

                        $code_error = "error_ejecucionQuery";
                        $mensaje = "Hubo un error al listar las ganancias.";
                        $exito = false; 

                    }else{

                        $result = get_result($stmt); 
                    
                        if (count($result) > 0) {                
                            while ($dato = array_shift($result)) {    
                                $datosServicios += $dato["SERVICIO_PRECIO"];
                            }
                        }

                        $mensaje = "Solicitud ejecutada con exito";
                        $exito = true; 
                        
                    }
                }

                return array("MONTO_VENTAS"=>$datosVentas,"MONTO_SERVICIOS"=>$datosServicios);

            } catch (Throwable $th) {

                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                $exito = false;
                return array("MONTO_VENTAS"=>$datosVentas,"MONTO_SERVICIOS"=>$datosServicios);
            }
        }

        function gananciasMensuales(&$mensaje,&$code_error,&$exito){

            $queryVenta = 'SELECT * FROM VENTA WHERE DATE_FORMAT(VENTA_FECHA_REGISTRO,"%Y-%m") = ?'; 
            $queryServicio = 'SELECT * FROM SERVICIO WHERE DATE_FORMAT(SERVICIO_FECHA_HORA,"%Y-%m") = ? AND SERVICIO_ESTADO = 1';
            $datos = 0;
            try {
                

                $stmt = $this->conn->prepare($queryVenta);
                $stmt->bind_param("s",$this->VENTA_FECHA_REGISTRO);
                if(!$stmt->execute()){

                    $code_error = "error_ejecucionQuery";
                    $mensaje = "Hubo un error al listar las ganancias.";
                    $exito = false; 

                }else{

                    $result = get_result($stmt); 
                
                    if (count($result) > 0) {                
                        while ($dato = array_shift($result)) {    
                            $datos += $dato["VENTA_TOTAL"];
                        }
                    }

                    $mensaje = "Solicitud ejecutada con exito";
                    
                    $stmt = $this->conn->prepare($queryServicio);
                    $stmt->bind_param("s",$this->VENTA_FECHA_REGISTRO);
                    if(!$stmt->execute()){

                        $code_error = "error_ejecucionQuery";
                        $mensaje = "Hubo un error al listar las ganancias.";
                        $exito = false; 

                    }else{

                        $result = get_result($stmt); 
                    
                        if (count($result) > 0) {                
                            while ($dato = array_shift($result)) {    
                                $datos += $dato["SERVICIO_PRECIO"];
                            }
                        }

                        $mensaje = "Solicitud ejecutada con exito";
                        $exito = true; 
                        
                    }
                }

                return $datos;

            } catch (Throwable $th) {

                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                $exito = false;
                return $datos ;
            }
        }

    }


?>