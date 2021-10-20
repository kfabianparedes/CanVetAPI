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

            $query = "CALL SP_INSERTAR_VENTA(@P_VENTA_ID,@VAL_COMPROBANTE_ID,@VAL_USU_ID,@VAL_MDP_ID,@VAL_CLI_ID,?,?,?,?,?,?,?,?,?,?)"; 
            $queryComprobanteID = "SELECT @VAL_COMPROBANTE_ID"; 
            $queryUsuID = "SELECT @VAL_USU_ID"; 
            $queryVentaId = "SELECT @P_VENTA_ID";
            $queryMdpID = "SELECT @VAL_MDP_ID"; 
            $queryCliId = "SELECT @VAL_CLI_ID"; 

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
        
        function gananciasDiarias(&$mensaje,&$code_error,&$exito){

            $query = ""; 

            try {
                //code...
            } catch (Throwable $th) {
                //throw $th;
            }
        }

        function gananciasSemanales(&$mensaje,&$code_error,&$exito){

            $query = ""; 

            try {
                //code...
            } catch (Throwable $th) {
                //throw $th;
            }
        }

        function ganancias mensuales(&$mensaje,&$code_error,&$exito){

            $query = ""; 

            try {
                //code...
            } catch (Throwable $th) {
                //throw $th;
            }
        }

    }


?>