<?php

    include_once '../../util/mysqlnd.php';

    class Compra{
        private $conn; 

        public $COMPRA_ID;
        public $COMPRA_FECHA_EMISION_COMPROBANTE; 
        public $COMPRA_FECHA_REGISTRO;
        public $COMPRA_NRO_SERIE;
        public $COMPRA_NRO_COMPROBANTE;
        public $COMPRA_SUBTOTAL;
        public $COMPRA_TOTAL;
        public $COMPRA_DESCRIPCION;
        public $COMPRA_ESTADO;
        public $USU_ID;
        public $COMPROBANTE_ID;
        public $PROV_ID;
        public $GUIA_ID;

        public function __construct($db){
            $this->conn = $db;
        }
    
        function ingresarCompra(&$mensaje,&$code_error,$hay_guia,$GUIA_NRO_SERIE,$GUIA_NRO_COMPROBANTE,$P_GUIA_FECHA_EMISION,&$Compra_id){
            

            $queryValidarUsuId="SELECT * FROM USUARIOS WHERE USU_ID = ?";
            $queryValidarProvId="SELECT * FROM PROVEEDOR WHERE PROV_ID = ?";
            $queryValidarComprobanteId="SELECT * FROM COMPROBANTE WHERE COMPROBANTE_ID = ?";
            $queryValidarGuiaId="SELECT * FROM USUARIOS WHERE USU_ID = ?";
            $queryIngresarCompra ="
            CALL SP_INSERTAR_COMPRA(@P_COMPRA_ID,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"; 
            $queryCompraID = "SELECT @P_COMPRA_ID";
            try {

                $stmtExistenciaUsuId = $this->conn->prepare($queryValidarUsuId);
                $stmtExistenciaUsuId->bind_param("s",$this->USU_ID);
                $stmtExistenciaUsuId->execute();
                $resultUsuarioId = get_result($stmtExistenciaUsuId);
                //validamos si existe el id del usuario ingresado
                if(count($resultUsuarioId) > 0){
                    
                    $stmtExistenciaProvId = $this->conn->prepare($queryValidarProvId);
                    $stmtExistenciaProvId->bind_param("s",$this->PROV_ID);
                    $stmtExistenciaProvId->execute();
                    $resultProveedorId = get_result($stmtExistenciaProvId);
                    //validamos si existe el proveedor ingresado
                    if(count($resultProveedorId) > 0){
                        
                        $stmtExistenciaComprobanteId = $this->conn->prepare($queryValidarComprobanteId);
                        $stmtExistenciaComprobanteId->bind_param("s",$this->PROV_ID);
                        $stmtExistenciaComprobanteId->execute();
                        $resultComprobanteId = get_result($stmtExistenciaComprobanteId);
                        //validamos si existe el comprobante ingresado
                        if(count($resultComprobanteId) > 0){
                            

                            $stmtIngresarCompra = $this->conn->prepare($queryIngresarCompra);
                            $stmtIngresarCompra->bind_param("ssssssssssssss",$hay_guia,$GUIA_NRO_SERIE,$GUIA_NRO_COMPROBANTE,$P_GUIA_FECHA_EMISION,
                            $this->COMPRA_FECHA_EMISION_COMPROBANTE,$this->COMPRA_FECHA_REGISTRO,$this->COMPRA_NRO_SERIE,
                            $this->COMPRA_NRO_COMPROBANTE,$this->COMPRA_SUBTOTAL,$this->COMPRA_TOTAL,$this->COMPRA_DESCRIPCION,
                            $this->USU_ID,$this->COMPROBANTE_ID,$this->PROV_ID);
                            //verificamos que se haya realizado correctamente el ingreso de la compra
                            if(!$stmtIngresarCompra->execute()){

                                $code_error = "error_ejecucionQuery";
                                $mensaje = "Hubo un error al aumentar el stock de los productos.";
                                return false; 

                            }else{
                                
                                //Obtiene el id de la compra creada
                                $stmtCompraId = $this->conn->prepare($queryCompraID);
                                $stmtCompraId->execute();
                                $resultCompraId = get_result($stmtCompraId);  

                                if (count($resultCompraId) > 0) {
                                    //se guarda el id de la compra creada en una variable
                                    $Compra_id = array_shift($result3)["@P_COMPRA_ID"];
                                }else{
                                    $code_error = "error_ejecucionQuery";
                                    $mensaje = "No se logró obtener el id de la compra realizada.";
                                    return false; 
                                }
                                $mensaje = "La solicitud se realizó con éxito";
                                return true; 

                            }
                            
                        }else{
                            $code_error = "error_ExistenciaDeComprobante";
                            $mensaje = "El id del comprobante ingresado no existe.";
                            return false;
                        }
                        
                    }else{
                        $code_error = "error_ExistenciaProveedorId";
                        $mensaje = "El id del proveedor ingresado no existe.";
                        return false;
                    }

                }else{
                    $code_error = "error_ExistenciaDeUsuario";
                    $mensaje = "El id del usuario ingresado no existe.";
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