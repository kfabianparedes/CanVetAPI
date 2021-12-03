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

        function registrar(&$mensaje,&$code_error,&$ventaId,$CAJA_CODIGO){

            $query = "CALL SP_INSERTAR_VENTA(@P_VENTA_ID,@VAL_COMPROBANTE_ID,@VAL_USU_ID,@VAL_MDP_ID,@VAL_CLI_ID,?,?,?,?,?,?,?,?)"; 
            $queryComprobanteID = "SELECT @VAL_COMPROBANTE_ID"; 
            $queryUsuID = "SELECT @VAL_USU_ID"; 
            $queryVentaId = "SELECT @P_VENTA_ID";
            $queryMdpID = "SELECT @VAL_MDP_ID"; 
            $queryCliId = "SELECT @VAL_CLI_ID";
            $cajaCerrada = "SELECT * FROM CAJA WHERE CAJA_CIERRE IS NULL AND CAJA_CODIGO = ?" ; 
            $horaDeRegistro = date("H:i:s");
            $this->VENTA_FECHA_REGISTRO = $this->VENTA_FECHA_REGISTRO.' '.$horaDeRegistro;
            try {

                $stmtExisteCaja = $this->conn->prepare($cajaCerrada);
                $stmtExisteCaja->bind_param("s",$CAJA_CODIGO);
                $stmtExisteCaja->execute();
                $resultExisteCaja= get_result($stmtExisteCaja);
                //validamos si existe el id del usuario ingresado
                if(count($resultExisteCaja) > 0){

                    $stmt = $this->conn->prepare($query);
                    $stmt->bind_param("ssssssss",$this->VENTA_FECHA_EMISION_COMPROBANTE,$this->VENTA_FECHA_REGISTRO,
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
                        // $stmtNroComprobante = $this->conn->prepare($queryNroComprobante);
                        // $stmtNroComprobante->execute();
                        // $resultNroComprobante = get_result($stmtNroComprobante); 

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
                        // if (count($resultNroComprobante) > 0) {
                        //     //obtenemos verdadero o falso dependiendo si es que se repite el nro de comprobante de la guía que se ingresará 
                        //     $valNroComprobante = array_shift($resultNroComprobante)["@VAL_NRO_COMPROBANTE"];
                        // }

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
                                        // if(!$valNroComprobante){

                                        //     $code_error = "error_existenciaNroComprobante";
                                        //     $mensaje = "El número de comprobante del número de serie ingresado ya existe.";
                                        //     return false;

                                        // }else{

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
                                        // }
                                    
                                    }
                                }
                                
                            }
                        }
                        $mensaje = "La solicitud se realizó con éxito";
                        return true; 

                    }

                }else{
                    
                    $code_error = "error_NoCajaAbierta";
                    $mensaje = "Para realizar una venta tiene que tener una caja abierta.";
                    return false; 

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

            $queryEfectivo = "SELECT * FROM VENTA WHERE (VENTA_FECHA_REGISTRO  BETWEEN ? AND ? ) AND METODO_DE_PAGO_ID = 1 AND USU_ID = ? AND VENTA_ESTADO = 1" ; 
            $queryTarjeta = "SELECT * FROM VENTA WHERE (VENTA_FECHA_REGISTRO  BETWEEN ? AND ? ) AND METODO_DE_PAGO_ID = 2 AND USU_ID = ? AND VENTA_ESTADO = 1" ; 
            $queryYape = "SELECT * FROM VENTA WHERE (VENTA_FECHA_REGISTRO  BETWEEN ? AND ? ) AND METODO_DE_PAGO_ID = 3 AND USU_ID = ? AND VENTA_ESTADO = 1"  ; 
            $queryServicioEfectivo = '
            SELECT SERVICIO_ID,if(SERVICIO_FECHA_REGISTRO BETWEEN ? AND ? AND SERVICIO_ESTADO = 0,SERVICIO_ADELANTO,
            if(DATE_FORMAT(SERVICIO_FECHA_HORA,"%Y-%m-%d") = DATE_FORMAT(SERVICIO_FECHA_REGISTRO,"%Y-%m-%d"),SERVICIO_PRECIO ,SERVICIO_PRECIO - SERVICIO_ADELANTO)) as SERVICIO_PAGO_DEUDA
            FROM SERVICIO WHERE ((SERVICIO_FECHA_REGISTRO BETWEEN ? AND ? AND SERVICIO_ESTADO = 0) 
                                    OR (SERVICIO_FECHA_HORA BETWEEN ? AND ? AND SERVICIO_ESTADO = 1))
            AND MDP_ID = 1 AND USU_ID = ?; ' ; 
             $queryServicioTarjeta = '
             SELECT SERVICIO_ID,if(SERVICIO_FECHA_REGISTRO BETWEEN ? AND ? AND SERVICIO_ESTADO = 0,SERVICIO_ADELANTO,
             if(DATE_FORMAT(SERVICIO_FECHA_HORA,"%Y-%m-%d") = DATE_FORMAT(SERVICIO_FECHA_REGISTRO,"%Y-%m-%d"),SERVICIO_PRECIO ,SERVICIO_PRECIO - SERVICIO_ADELANTO)) as SERVICIO_PAGO_DEUDA
             FROM SERVICIO WHERE ((SERVICIO_FECHA_REGISTRO BETWEEN ? AND ? AND SERVICIO_ESTADO = 0) 
                                     OR (SERVICIO_FECHA_HORA BETWEEN ? AND ? AND SERVICIO_ESTADO = 1))
             AND MDP_ID = 2 AND USU_ID = ?;' ; 
             $queryServicioYape = '
             SELECT SERVICIO_ID,if(SERVICIO_FECHA_REGISTRO BETWEEN ? AND ? AND SERVICIO_ESTADO = 0,SERVICIO_ADELANTO,
             if(DATE_FORMAT(SERVICIO_FECHA_HORA,"%Y-%m-%d") = DATE_FORMAT(SERVICIO_FECHA_REGISTRO,"%Y-%m-%d"),SERVICIO_PRECIO ,SERVICIO_PRECIO - SERVICIO_ADELANTO)) as SERVICIO_PAGO_DEUDA
             FROM SERVICIO WHERE ((SERVICIO_FECHA_REGISTRO BETWEEN ? AND ? AND SERVICIO_ESTADO = 0) 
                                     OR (SERVICIO_FECHA_HORA BETWEEN ? AND ? AND SERVICIO_ESTADO = 1))
             AND MDP_ID = 3 AND USU_ID = ?;' ; 
            $queryBuscarHoraRegistroCaja = "SELECT CAJA_APERTURA, CAJA_MONTO_INICIAL FROM CAJA WHERE USU_ID = ? AND CAJA_CIERRE IS NULL";
            $queryMontoInicial = "SELECT  CAJA_MONTO_INICIAL FROM CAJA WHERE USU_ID = ? AND CAJA_CIERRE IS NULL";

            $datosVentasTarjeta = 0;
            $datosVentasEfectivo = 0;
            $datosVentasYape = 0;
            $datosServicioEfectivo = 0;
            $datosServicioTarjeta = 0;
            $datosServicioYape = 0;
            $monto_inicial = 0; 
            $hora_finalización = date("Y-m-d H:i:s");
            try {
                //ventas efectivo
                $stmt = $this->conn->prepare($queryBuscarHoraRegistroCaja);
                $stmt->bind_param("s",$this->USU_ID);
                $stmt->execute();
                $resultHoraApertura = get_result($stmt);
                if(count($resultHoraApertura) <= 0){

                    $code_error = "error_ErrorDeCajaNoAbierta";
                    $mensaje = "El usuario no tiene ninguna caja abierta.";
                    $exito = false; 

                }else{

                    $this->VENTA_FECHA_REGISTRO = array_shift($resultHoraApertura)['CAJA_APERTURA'] ; 
                    $stmtMontoInicial = $this->conn->prepare($queryMontoInicial);
                    $stmtMontoInicial->bind_param("s",$this->USU_ID);
                    $stmtMontoInicial->execute();
                    $resulMontoInicial = get_result($stmtMontoInicial);
                    $monto_inicial = array_shift($resulMontoInicial)['CAJA_MONTO_INICIAL'] ;
                     //ventas efectivo
                    $stmt = $this->conn->prepare($queryEfectivo);
                    $stmt->bind_param("sss",$this->VENTA_FECHA_REGISTRO,$hora_finalización,$this->USU_ID);
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
                        $stmt->bind_param("sss",$this->VENTA_FECHA_REGISTRO,$hora_finalización,$this->USU_ID);
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
                            $stmt->bind_param("sss",$this->VENTA_FECHA_REGISTRO,$hora_finalización,$this->USU_ID);
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
                                $stmt->bind_param("sssssss",$this->VENTA_FECHA_REGISTRO,$hora_finalización,$this->VENTA_FECHA_REGISTRO,$hora_finalización,$this->VENTA_FECHA_REGISTRO,$hora_finalización,$this->USU_ID);
                                if(!$stmt->execute()){

                                    $code_error = "error_ejecucionQuery";
                                    $mensaje = "Hubo un error al listar el monto ganado en servicios con efectivo.";
                                    $exito = false; 

                                }else{

                                    $result = get_result($stmt); 
                                
                                    if (count($result) > 0) {                
                                        while ($dato = array_shift($result)) {    
                                            $datosServicioEfectivo += $dato["SERVICIO_PAGO_DEUDA"];
                                        }
                                    }
                                    //servicios tarjeta
                                    $stmt = $this->conn->prepare($queryServicioTarjeta);
                                    $stmt->bind_param("sssssss",$this->VENTA_FECHA_REGISTRO,$hora_finalización,$this->VENTA_FECHA_REGISTRO,$hora_finalización,$this->VENTA_FECHA_REGISTRO,$hora_finalización,$this->USU_ID);
                                    if(!$stmt->execute()){

                                        $code_error = "error_ejecucionQuery";
                                        $mensaje = "Hubo un error al listar el monto ganado en servicios con tarjeta.";
                                        $exito = false; 

                                    }else{

                                        $result = get_result($stmt); 
                                    
                                        if (count($result) > 0) {                
                                            while ($dato = array_shift($result)) {    
                                                $datosServicioTarjeta += $dato["SERVICIO_PAGO_DEUDA"];
                                            }
                                        }
                                        //servicios yape
                                        $stmt = $this->conn->prepare($queryServicioYape);
                                        $stmt->bind_param("sssssss",$this->VENTA_FECHA_REGISTRO,$hora_finalización,$this->VENTA_FECHA_REGISTRO,$hora_finalización,$this->VENTA_FECHA_REGISTRO,$hora_finalización,$this->USU_ID);
                                        if(!$stmt->execute()){

                                            $code_error = "error_ejecucionQuery";
                                            $mensaje = "Hubo un error al listar el monto ganado en servicios con yape.";
                                            $exito = false; 

                                        }else{

                                            $result = get_result($stmt); 
                                        
                                            if (count($result) > 0) {                
                                                while ($dato = array_shift($result)) {    
                                                    $datosServicioYape += $dato["SERVICIO_PAGO_DEUDA"];
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
                }

               
                $datos = array("gananciasVentasEfectivo" => $datosVentasEfectivo, "gananciasVentasTarjeta" => $datosVentasTarjeta, "gananciasVentasYape" => $datosVentasYape,
                "gananciasServiciosEfectivo" => $datosServicioEfectivo, "gananciasServiciosTarjeta" => $datosServicioTarjeta, "gananciasServiciosYape" => $datosServicioYape,"montoInicial"=>$monto_inicial);

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

            $query = "SELECT * FROM VENTA WHERE VENTA_FECHA_REGISTRO = ? AND USU_ID = ? AND VENTA_ESTADO = 1"; 
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
            
            $query = 'SELECT * FROM VENTA WHERE DATE_FORMAT(VENTA_FECHA_REGISTRO,"%Y-%m-%d") =? AND VENTA_ESTADO = 1'; 
            // $queryServicio = 'SELECT * FROM SERVICIO WHERE DATE_FORMAT(SERVICIO_FECHA_HORA,"%Y-%m-%d") = ? AND SERVICIO_ESTADO = 1';
            
            $queryServicio='SELECT SERVICIO_ID,if(DATE_FORMAT(SERVICIO_FECHA_REGISTRO,"%Y-%m-%d") = ?,SERVICIO_ADELANTO,
            if(DATE_FORMAT(SERVICIO_FECHA_HORA,"%Y-%m-%d") = DATE_FORMAT(SERVICIO_FECHA_REGISTRO,"%Y-%m-%d"),SERVICIO_PRECIO ,SERVICIO_PRECIO - SERVICIO_ADELANTO)) as SERVICIO_PAGO_DEUDA
            FROM SERVICIO WHERE ((DATE_FORMAT(SERVICIO_FECHA_REGISTRO,"%Y-%m-%d") = ?) 
							OR (DATE_FORMAT(SERVICIO_FECHA_HORA,"%Y-%m-%d") = ? AND SERVICIO_ESTADO = 1))';
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
                    $stmt->bind_param("sss",$this->VENTA_FECHA_REGISTRO,$this->VENTA_FECHA_REGISTRO,$this->VENTA_FECHA_REGISTRO);
                    if(!$stmt->execute()){

                        $code_error = "error_ejecucionQuery";
                        $mensaje = "Hubo un error al listar las ganancias.";
                        $exito = false; 

                    }else{

                        $result = get_result($stmt); 
                    
                        if (count($result) > 0) {                
                            while ($dato = array_shift($result)) {    
                                $datosServicios += $dato["SERVICIO_PAGO_DEUDA"];
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

            $queryVenta = 'SELECT * FROM VENTA WHERE DATE_FORMAT(VENTA_FECHA_REGISTRO,"%Y-%m") = ? AND VENTA_ESTADO = 1'; 
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
        function listarVentasMesAnterioryActual(&$mensaje,&$code_error,&$exito){

            #querys para obtener los reportes. 
            
            $queryVentas = '
            SELECT V.VENTA_ID, V.VENTA_FECHA_REGISTRO, V.VENTA_TOTAL
            ,CONCAT(USU.USU_NOMBRES," ",USU.USU_APELLIDO_PATERNO," ",USU.USU_APELLIDO_MATERNO)AS USU_NOMBRE, 
            COM.COMPROBANTE_TIPO,MDP.MDP_NOMBRE,
            CONCAT(CLI.CLIENTE_NOMBRES," ",CLI.CLIENTE_APELLIDOS)AS CLIENTE_NOMBRE, CLI.CLIENTE_TELEFONO, CLI.CLIENTE_DNI, CLI.CLIENTE_CORREO,
            DJ.DJ_RAZON_SOCIAL, DJ.DJ_RUC FROM VENTA V  
            INNER JOIN METODO_PAGO MDP ON (V.METODO_DE_PAGO_ID = MDP.MDP_ID)
            INNER JOIN COMPROBANTE COM ON (V.COMPROBANTE_ID = COM.COMPROBANTE_ID)
            INNER JOIN USUARIOS USU ON (V.USU_ID = USU.USU_ID)
            INNER JOIN CLIENTE CLI ON (V.CLIENTE_ID = CLI.CLIENTE_ID) 
			left OUTER JOIN DATOS_JURIDICOS DJ ON (CLI.CLIENTE_ID = DJ.CLIENTE_ID) 
            WHERE DATE_FORMAT(V.VENTA_FECHA_REGISTRO,"%Y-%m") = ? AND V.VENTA_ESTADO = 1
            ORDER BY V.VENTA_FECHA_REGISTRO DESC;
            ';

            $queryServicios = '
            SELECT SERVICIOS.* FROM (
                SELECT SERVICIO_ID,if(DATE_FORMAT(SERVICIO_FECHA_REGISTRO,"%Y-%m") = ? and DATE_FORMAT(SERVICIO_FECHA_HORA,"%Y-%m-%d") <> DATE_FORMAT(SERVICIO_FECHA_REGISTRO,"%Y-%m-%d"),SERVICIO_ADELANTO,SERVICIO_PRECIO)
                            AS SERVICIO_PRECIO , SERVICIO_FECHA_REGISTRO AS SERVICIO_FECHA_HORA 
                            ,CONCAT(USU.USU_NOMBRES," ",USU.USU_APELLIDO_PATERNO," ",USU.USU_APELLIDO_MATERNO)AS USU_NOMBRE, 
                            TP.TIPO_SERVICIO_NOMBRE,
                            COM.COMPROBANTE_TIPO,MDP.MDP_NOMBRE,
                            CONCAT(CLI.CLIENTE_NOMBRES," ",CLI.CLIENTE_APELLIDOS)AS CLIENTE_NOMBRE, CLI.CLIENTE_TELEFONO, CLI.CLIENTE_DNI, CLI.CLIENTE_CORREO,
                            DJ.DJ_RAZON_SOCIAL, DJ.DJ_RUC
                            FROM SERVICIO SER
                            INNER JOIN METODO_PAGO MDP ON (SER.MDP_ID = MDP.MDP_ID)
                            INNER JOIN TIPO_SERVICIO TP ON (SER.TIPO_SERVICIO_ID = TP.TIPO_SERVICIO_ID)
                            INNER JOIN COMPROBANTE COM ON (SER.COMPROBANTE_ID = COM.COMPROBANTE_ID)
                            INNER JOIN USUARIOS USU ON (SER.USU_ID = USU.USU_ID)
                            INNER JOIN MASCOTA MAS ON (SER.MASCOTA_ID = MAS.MAS_ID)
                            INNER JOIN CLIENTE CLI ON (MAS.CLIENTE_ID = CLI.CLIENTE_ID) 
                            left OUTER JOIN DATOS_JURIDICOS DJ ON (CLI.CLIENTE_ID = DJ.CLIENTE_ID)
                            WHERE (DATE_FORMAT(SERVICIO_FECHA_REGISTRO,"%Y-%m") = ?)
                            UNION 
                SELECT SERVICIO_ID, SERVICIO_PRECIO - SERVICIO_ADELANTO AS SERVICIO_PRECIO, SERVICIO_FECHA_HORA 
                            ,CONCAT(USU.USU_NOMBRES," ",USU.USU_APELLIDO_PATERNO," ",USU.USU_APELLIDO_MATERNO)AS USU_NOMBRE, 
                            TP.TIPO_SERVICIO_NOMBRE,
                            COM.COMPROBANTE_TIPO,MDP.MDP_NOMBRE,
                            CONCAT(CLI.CLIENTE_NOMBRES," ",CLI.CLIENTE_APELLIDOS)AS CLIENTE_NOMBRE, CLI.CLIENTE_TELEFONO, CLI.CLIENTE_DNI, CLI.CLIENTE_CORREO,
                            DJ.DJ_RAZON_SOCIAL, DJ.DJ_RUC
                            FROM 
                            SERVICIO SER
                            INNER JOIN METODO_PAGO MDP ON (SER.MDP_ID = MDP.MDP_ID)
                            INNER JOIN TIPO_SERVICIO TP ON (SER.TIPO_SERVICIO_ID = TP.TIPO_SERVICIO_ID)
                            INNER JOIN COMPROBANTE COM ON (SER.COMPROBANTE_ID = COM.COMPROBANTE_ID)
                            INNER JOIN USUARIOS USU ON (SER.USU_ID = USU.USU_ID)
                            INNER JOIN MASCOTA MAS ON (SER.MASCOTA_ID = MAS.MAS_ID)
                            INNER JOIN CLIENTE CLI ON (MAS.CLIENTE_ID = CLI.CLIENTE_ID) 
                            left OUTER JOIN DATOS_JURIDICOS DJ ON (CLI.CLIENTE_ID = DJ.CLIENTE_ID)
                            WHERE (DATE_FORMAT(SERVICIO_FECHA_HORA,"%Y-%m") = ?) AND DATE_FORMAT(SERVICIO_FECHA_HORA,"%Y-%m-%d") <> DATE_FORMAT(SERVICIO_FECHA_REGISTRO,"%Y-%m-%d")
                ) as SERVICIOS
                ORDER BY SERVICIOS.SERVICIO_ID, SERVICIOS.SERVICIO_FECHA_HORA DESC
            ';

            // $queryServicios = '
            // SELECT SER.SERVICIO_ID,SER.SERVICIO_PRECIO, if(DATE_FORMAT(SER.SERVICIO_FECHA_REGISTRO,"%Y-%m") = ? AND 
            // SER.SERVICIO_PRECIO = SER.SERVICIO_ADELANTO,SER.SERVICIO_FECHA_REGISTRO,SER.SERVICIO_FECHA_HORA) AS SERVICIO_FECHA_HORA,SER.SERVICIO_TIPO
            // ,CONCAT(USU.USU_NOMBRES," ",USU.USU_APELLIDO_PATERNO," ",USU.USU_APELLIDO_MATERNO)AS USU_NOMBRE, 
            // TP.TIPO_SERVICIO_NOMBRE,
            // COM.COMPROBANTE_TIPO,MDP.MDP_NOMBRE,
            // CONCAT(CLI.CLIENTE_NOMBRES," ",CLI.CLIENTE_APELLIDOS)AS CLIENTE_NOMBRE, CLI.CLIENTE_TELEFONO, CLI.CLIENTE_DNI, CLI.CLIENTE_CORREO,
            // DJ.DJ_RAZON_SOCIAL, DJ.DJ_RUC FROM SERVICIO SER
            // INNER JOIN METODO_PAGO MDP ON (SER.MDP_ID = MDP.MDP_ID)
            // INNER JOIN TIPO_SERVICIO TP ON (SER.TIPO_SERVICIO_ID = TP.TIPO_SERVICIO_ID)
            // INNER JOIN COMPROBANTE COM ON (SER.COMPROBANTE_ID = COM.COMPROBANTE_ID)
            // INNER JOIN USUARIOS USU ON (SER.USU_ID = USU.USU_ID)
            // INNER JOIN MASCOTA MAS ON (SER.MASCOTA_ID = MAS.MAS_ID)
            // INNER JOIN CLIENTE CLI ON (MAS.CLIENTE_ID = CLI.CLIENTE_ID) 
			// left OUTER JOIN DATOS_JURIDICOS DJ ON (CLI.CLIENTE_ID = DJ.CLIENTE_ID)
            // WHERE	
            // ((DATE_FORMAT(SER.SERVICIO_FECHA_REGISTRO,"%Y-%m") = ? AND SER.SERVICIO_PRECIO = SER.SERVICIO_ADELANTO) 
            // OR (DATE_FORMAT(SER.SERVICIO_FECHA_HORA,"%Y-%m") = ? AND SER.SERVICIO_ESTADO = 1 AND SER.SERVICIO_PRECIO <> SER.SERVICIO_ADELANTO))
            // ORDER BY SER.SERVICIO_FECHA_HORA DESC;
            // ';

            #hallamos el mes actual y el mes anterior en formato de Año-Mes
            $anioMes = date('Y-m');
            $anioMesAnterior = strtotime('-1 month', strtotime($anioMes));
            $anioMesAnterior = date('Y-m', $anioMesAnterior); 

            #variable que almacenará los datos de los reportes y será enviada al frontend
            $datos = [];
            $datosMesAnterior = [];
            $datosMesActual = [];

            try {
                #****************** MES ACTUAL***********************#
                #****************** VENTAS MES ACTUAL************************#

                #obteniendo ventas DEL MES ACUTUAL 
                $stmt = $this->conn->prepare($queryVentas);
                $stmt->bind_param("s",$anioMes);
                if(!$stmt->execute()){

                    $code_error = "error_ejecucionQuery";
                    $mensaje = "Hubo un error al listar las ventas del mes actual.";
                    $exito = false; 

                }else{

                    $result = get_result($stmt); 
                
                    if (count($result) > 0) {                
                        while ($dato = array_shift($result)) {    
                            $datos[] = $dato;
                        }
                    }
                    #SE PONEN LAS VENTAS OBTENIDAS EN EL ARRAY DE MES ACTUAL
                    array_push($datosMesActual,array( "VENTAS" => $datos));
                    
                    #LIMPIAMOS LA VARIABLE DATOS PARA REUTILIZARLA PARA LOS OTROS REPORTES 
                    $datos = [];

                        #****************** SERIVICIOS MES ACTUAL ************************#

                        #obteniendo ventas con tipo de comprobante BOLETA 
                        $stmt = $this->conn->prepare($queryServicios);
                        $stmt->bind_param("sss",$anioMes,$anioMes,$anioMes);
                        if(!$stmt->execute()){

                            $code_error = "error_ejecucionQuery";
                            $mensaje = "Hubo un error al listar los servicios del mes actual.";
                            $exito = false; 

                        }else{

                            $result = get_result($stmt); 
                        
                            if (count($result) > 0) {                
                                while ($dato = array_shift($result)) {    
                                    $datos[] = $dato;
                                }
                            }
                            #SE PONEN LOS SERVICIOS OBTENIDOS EN EL ARRAY DE MES ACTUAL
                            array_push($datosMesActual,array( "SERVICIOS" => $datos));
                            
                            #LIMPIAMOS LA VARIABLE DATOS PARA REUTILIZARLA PARA LOS OTROS REPORTES 
                            $datos = [];

                                #*********************** MES ANTERIOR ***************************#
                                #********************** SERVICIOS *****************************#

                                #obteniendo servicios con tipo de comprobante FACTURA 
                                $stmt = $this->conn->prepare($queryVentas);
                                $stmt->bind_param("s",$anioMesAnterior);
                                if(!$stmt->execute()){

                                    $code_error = "error_ejecucionQuery";
                                    $mensaje = "Hubo un error al listar las ventas del mes anterior.";
                                    $exito = false; 

                                }else{

                                    $result = get_result($stmt); 
                                
                                    if (count($result) > 0) {                
                                        while ($dato = array_shift($result)) {    
                                            $datos[] = $dato;
                                        }
                                    }
                                    #SE PONEN LAS VENTAS OBTENIDAS DEL MES ANTERIOR 
                                    array_push($datosMesAnterior,array("VENTAS" => $datos));
                                    
                                    #LIMPIAMOS LA VARIABLE DATOS PARA REUTILIZARLA PARA LOS OTROS REPORTES 
                                    $datos = [];

                                        #****************** SERVICIOS DEL MES ANTERIOR ************************#

                                        #obteniendo servicios con tipo de comprobante BOLETAS 
                                        $stmt = $this->conn->prepare($queryServicios);
                                        $stmt->bind_param("sss",$anioMesAnterior,$anioMesAnterior,$anioMesAnterior);
                                        if(!$stmt->execute()){

                                            $code_error = "error_ejecucionQuery";
                                            $mensaje = "Hubo un error al listar los servicios del mes anterior.";
                                            $exito = false; 

                                        }else{

                                            $result = get_result($stmt); 
                                        
                                            if (count($result) > 0) {                
                                                while ($dato = array_shift($result)) {    
                                                    $datos[] = $dato;
                                                }
                                            }
                                            #SE PONEN LOS SERVICIOS OBTENIDOS EN EL ARRAY DE MES ANTERIOR
                                            array_push($datosMesAnterior    ,array("SERVICIOS" => $datos));
                                            
                                            #LIMPIAMOS LA VARIABLE DATOS PARA REUTILIZARLA PARA LOS OTROS REPORTES 
                                            $datos = [];

                                            $exito = true;
                                            $mensaje = "La solicitud se realizó con éxito.";
                                        }
                                }
                        

                        }
                    
                }

                $datos = array("MES_ACTUAL" => $datosMesActual, "MES_ANTERIOR" => $datosMesAnterior);
                return $datos ; 
                
            } catch (Throwable $th) {

                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                $exito = false;
                $datos = array("MES_ACTUAL" => $datosMesActual, "MES_ANTERIOR" => $datosMesAnterior);
                return $datos ;

            }
        }
        function listarVentasServiciosHoy(&$mensaje,&$code_error,&$exito){

            $queryVentas = '
            SELECT V.VENTA_ID, V.VENTA_FECHA_REGISTRO, V.VENTA_TOTAL
            ,CONCAT(USU.USU_NOMBRES," ",USU.USU_APELLIDO_PATERNO," ",USU.USU_APELLIDO_MATERNO)AS USU_NOMBRE, 
            COM.COMPROBANTE_TIPO,MDP.MDP_NOMBRE,
            CONCAT(CLI.CLIENTE_NOMBRES," ",CLI.CLIENTE_APELLIDOS)AS CLIENTE_NOMBRE, CLI.CLIENTE_TELEFONO, CLI.CLIENTE_DNI, CLI.CLIENTE_CORREO,
            DJ.DJ_RAZON_SOCIAL, DJ.DJ_RUC FROM VENTA V  
            INNER JOIN METODO_PAGO MDP ON (V.METODO_DE_PAGO_ID = MDP.MDP_ID)
            INNER JOIN COMPROBANTE COM ON (V.COMPROBANTE_ID = COM.COMPROBANTE_ID)
            INNER JOIN USUARIOS USU ON (V.USU_ID = USU.USU_ID)
            INNER JOIN CLIENTE CLI ON (V.CLIENTE_ID = CLI.CLIENTE_ID) 
			left OUTER JOIN DATOS_JURIDICOS DJ ON (CLI.CLIENTE_ID = DJ.CLIENTE_ID) 
            WHERE DATE_FORMAT(V.VENTA_FECHA_REGISTRO,"%Y-%m-%d") = ? AND V.VENTA_ESTADO = 1
            ORDER BY V.VENTA_FECHA_REGISTRO DESC;
            ';

            $queryServicios = '
            SELECT SERVICIOS.* FROM (
                SELECT SERVICIO_ID,if(DATE_FORMAT(SERVICIO_FECHA_REGISTRO,"%Y-%m-%d") = ? and DATE_FORMAT(SERVICIO_FECHA_HORA,"%Y-%m-%d") <> DATE_FORMAT(SERVICIO_FECHA_REGISTRO,"%Y-%m-%d"),SERVICIO_ADELANTO,SERVICIO_PRECIO)
                            AS SERVICIO_PRECIO , SERVICIO_FECHA_REGISTRO AS SERVICIO_FECHA_HORA 
                            ,CONCAT(USU.USU_NOMBRES," ",USU.USU_APELLIDO_PATERNO," ",USU.USU_APELLIDO_MATERNO)AS USU_NOMBRE, 
                            TP.TIPO_SERVICIO_NOMBRE,
                            COM.COMPROBANTE_TIPO,MDP.MDP_NOMBRE,
                            CONCAT(CLI.CLIENTE_NOMBRES," ",CLI.CLIENTE_APELLIDOS)AS CLIENTE_NOMBRE, CLI.CLIENTE_TELEFONO, CLI.CLIENTE_DNI, CLI.CLIENTE_CORREO,
                            DJ.DJ_RAZON_SOCIAL, DJ.DJ_RUC
                            FROM SERVICIO SER
                            INNER JOIN METODO_PAGO MDP ON (SER.MDP_ID = MDP.MDP_ID)
                            INNER JOIN TIPO_SERVICIO TP ON (SER.TIPO_SERVICIO_ID = TP.TIPO_SERVICIO_ID)
                            INNER JOIN COMPROBANTE COM ON (SER.COMPROBANTE_ID = COM.COMPROBANTE_ID)
                            INNER JOIN USUARIOS USU ON (SER.USU_ID = USU.USU_ID)
                            INNER JOIN MASCOTA MAS ON (SER.MASCOTA_ID = MAS.MAS_ID)
                            INNER JOIN CLIENTE CLI ON (MAS.CLIENTE_ID = CLI.CLIENTE_ID) 
                            left OUTER JOIN DATOS_JURIDICOS DJ ON (CLI.CLIENTE_ID = DJ.CLIENTE_ID)
                            WHERE (DATE_FORMAT(SERVICIO_FECHA_REGISTRO,"%Y-%m-%d") = ?)
                            UNION 
                SELECT SERVICIO_ID, SERVICIO_PRECIO - SERVICIO_ADELANTO AS SERVICIO_PRECIO, SERVICIO_FECHA_HORA 
                            ,CONCAT(USU.USU_NOMBRES," ",USU.USU_APELLIDO_PATERNO," ",USU.USU_APELLIDO_MATERNO)AS USU_NOMBRE, 
                            TP.TIPO_SERVICIO_NOMBRE,
                            COM.COMPROBANTE_TIPO,MDP.MDP_NOMBRE,
                            CONCAT(CLI.CLIENTE_NOMBRES," ",CLI.CLIENTE_APELLIDOS)AS CLIENTE_NOMBRE, CLI.CLIENTE_TELEFONO, CLI.CLIENTE_DNI, CLI.CLIENTE_CORREO,
                            DJ.DJ_RAZON_SOCIAL, DJ.DJ_RUC
                            FROM 
                            SERVICIO SER
                            INNER JOIN METODO_PAGO MDP ON (SER.MDP_ID = MDP.MDP_ID)
                            INNER JOIN TIPO_SERVICIO TP ON (SER.TIPO_SERVICIO_ID = TP.TIPO_SERVICIO_ID)
                            INNER JOIN COMPROBANTE COM ON (SER.COMPROBANTE_ID = COM.COMPROBANTE_ID)
                            INNER JOIN USUARIOS USU ON (SER.USU_ID = USU.USU_ID)
                            INNER JOIN MASCOTA MAS ON (SER.MASCOTA_ID = MAS.MAS_ID)
                            INNER JOIN CLIENTE CLI ON (MAS.CLIENTE_ID = CLI.CLIENTE_ID) 
                            left OUTER JOIN DATOS_JURIDICOS DJ ON (CLI.CLIENTE_ID = DJ.CLIENTE_ID)
                            WHERE (DATE_FORMAT(SERVICIO_FECHA_HORA,"%Y-%m-%d") = ?) AND DATE_FORMAT(SERVICIO_FECHA_HORA,"%Y-%m-%d") <> DATE_FORMAT(SERVICIO_FECHA_REGISTRO,"%Y-%m-%d")
                ) as SERVICIOS
                ORDER BY SERVICIOS.SERVICIO_ID, SERVICIOS.SERVICIO_FECHA_HORA DESC
            ';

            // $queryServicios = '
            // SELECT SER.SERVICIO_ID,SER.SERVICIO_PRECIO, if(DATE_FORMAT(SER.SERVICIO_FECHA_REGISTRO,"%Y-%m-%d") = ? AND 
            // SER.SERVICIO_PRECIO = SER.SERVICIO_ADELANTO,SER.SERVICIO_FECHA_REGISTRO,SER.SERVICIO_FECHA_HORA) AS SERVICIO_FECHA_HORA,SER.SERVICIO_TIPO
            // ,CONCAT(USU.USU_NOMBRES," ",USU.USU_APELLIDO_PATERNO," ",USU.USU_APELLIDO_MATERNO)AS USU_NOMBRE, 
            // TP.TIPO_SERVICIO_NOMBRE,
            // COM.COMPROBANTE_TIPO,MDP.MDP_NOMBRE,
            // CONCAT(CLI.CLIENTE_NOMBRES," ",CLI.CLIENTE_APELLIDOS)AS CLIENTE_NOMBRE, CLI.CLIENTE_TELEFONO, CLI.CLIENTE_DNI, CLI.CLIENTE_CORREO,
            // DJ.DJ_RAZON_SOCIAL, DJ.DJ_RUC FROM SERVICIO SER
            // INNER JOIN METODO_PAGO MDP ON (SER.MDP_ID = MDP.MDP_ID)
            // INNER JOIN TIPO_SERVICIO TP ON (SER.TIPO_SERVICIO_ID = TP.TIPO_SERVICIO_ID)
            // INNER JOIN COMPROBANTE COM ON (SER.COMPROBANTE_ID = COM.COMPROBANTE_ID)
            // INNER JOIN USUARIOS USU ON (SER.USU_ID = USU.USU_ID)
            // INNER JOIN MASCOTA MAS ON (SER.MASCOTA_ID = MAS.MAS_ID)
            // INNER JOIN CLIENTE CLI ON (MAS.CLIENTE_ID = CLI.CLIENTE_ID) 
			// left OUTER JOIN DATOS_JURIDICOS DJ ON (CLI.CLIENTE_ID = DJ.CLIENTE_ID)
            // WHERE	
            // ((DATE_FORMAT(SER.SERVICIO_FECHA_REGISTRO,"%Y-%m-%d") = ? AND SER.SERVICIO_PRECIO = SER.SERVICIO_ADELANTO) 
            // OR (DATE_FORMAT(SER.SERVICIO_FECHA_HORA,"%Y-%m-%d") = ? AND SER.SERVICIO_ESTADO = 1 AND SER.SERVICIO_PRECIO <> SER.SERVICIO_ADELANTO))
            // ORDER BY SER.SERVICIO_FECHA_HORA DESC;
            // ';

            $diaActual = date('Y-m-d');
            
            $datos = [] ; 
            $receptorDatos = [] ;
            try {
                
                #obteniendo ventas del día actual
                $stmt = $this->conn->prepare($queryVentas);
                $stmt->bind_param("s",$diaActual);
                if(!$stmt->execute()){

                    $code_error = "error_ejecucionQuery";
                    $mensaje = "Hubo un error al listar las ventas del día de hoy.";
                    $exito = false; 

                }else{

                    $result = get_result($stmt); 
                
                    if (count($result) > 0) {                
                        while ($dato = array_shift($result)) {    
                            $receptorDatos[] = $dato;
                        }
                    }
                    #SE PONEN LAS VENTAS OBTENIDAS EN EL ARRAY DE MES ACTUAL
                    array_push($datos,array( "VENTAS" => $receptorDatos));
                    
                    #LIMPIAMOS EL RECEPTOR DE DATOS PARA USARLO DESPUÉS
                    $receptorDatos = [];

                     #obteniendo servicios del día actual 
                    $stmt = $this->conn->prepare($queryServicios);
                    $stmt->bind_param("sss",$diaActual,$diaActual,$diaActual);
                    if(!$stmt->execute()){

                        $code_error = "error_ejecucionQuery";
                        $mensaje = "Hubo un error al listar los servicios del día de hoy.";
                        $exito = false; 

                    }else{

                        $result = get_result($stmt); 
                    
                        if (count($result) > 0) {                
                            while ($dato = array_shift($result)) {    
                                $receptorDatos[] = $dato;
                            }
                        }
                        #SE PONEN LAS VENTAS OBTENIDAS EN EL ARRAY DE MES ACTUAL
                        array_push($datos,array( "SERVICIOS" => $receptorDatos));
                        
                        $exito = true ; 
                        $mensaje = "La solicitud fue realizada con éxito.";
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
        function deshabilitarVenta(&$mensaje,&$code_error){

            $query = "UPDATE VENTA SET VENTA_ESTADO = 0 WHERE VENTA_ID = ?";
            $verificarExistenciaIdVenta = "select * from VENTA where VENTA_ID = ?"; 
                try {
                    $stmtId = $this->conn->prepare($verificarExistenciaIdVenta);
                    $stmtId->bind_param("s",$this->VENTA_ID);
                    $stmtId->execute();
                    $resultId = get_result($stmtId);
                    
                    if(count($resultId) > 0){
                        //EJECTUAMOS LA CONSULTA PARA ACTUALIZAR EL ESTADO DE LA CATEGORIA 
                        $stmt = $this->conn->prepare($query);
                        $stmt->bind_param("s",$this->VENTA_ID);
                        $stmt->execute();
    
                        $mensaje = "Se ha actualizado la venta con éxito";
                        return true;
                    }else{
                        $code_error = "error_existenciaId";
                        $mensaje = "La venta no existe.";
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