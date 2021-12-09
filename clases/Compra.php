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
        
        function listar(&$mensaje,&$code_error,&$exito){

            $query = "
            SELECT COM.COMPRA_ID, COM.COMPRA_FECHA_EMISION_COMPROBANTE, COM.COMPRA_FECHA_REGISTRO, COM.COMPRA_NRO_SERIE, COM.COMPRA_NRO_COMPROBANTE, COM.COMPRA_SUBTOTAL, COM.COMPRA_TOTAL, COM.COMPRA_DESCRIPCION,
            COM.COMPRA_ESTADO,
            PROV.PROV_EMPRESA_PROVEEDORA,
            CONCAT(USU.USU_NOMBRES," ",USU.USU_APELLIDO_PATERNO," ",USU.USU_APELLIDO_MATERNO) AS USU_NOMBRE_COMPLETO,
            COMP.COMPROBANTE_TIPO, 
            GUIA.*
            FROM COMPRA COM 
            INNER JOIN PROVEEDOR PROV ON (COM.PROV_ID = PROV.PROV_ID)
            INNER JOIN USUARIOS USU ON (COM.USU_ID = USU.USU_ID)
            INNER JOIN COMPROBANTE COMP ON (COM.COMPROBANTE_ID = COMP.COMPROBANTE_ID) 
            LEFT OUTER JOIN GUIA_REMISION GUIA ON (COM.GUIA_ID = GUIA.GUIA_ID);
            ";

            $datos = [];
            try {

                $stmt = $this->conn->prepare($query);
                if(!$stmt->execute()){

                    $code_error = "error_ejecucionQuery";
                    $mensaje = "Hubo un error al listar el registro de compras.";
                    return false; 

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

        function ingresarCompra(&$mensaje,&$code_error,$hay_guia,$GUIA_NRO_SERIE,$GUIA_NRO_COMPROBANTE,$GUIA_FECHA_EMISION,$GUIA_FLETE,&$Compra_id){
            

            $queryValidarUsuId="SELECT * FROM USUARIOS WHERE USU_ID = ?";
            $queryValidarProvId="SELECT * FROM PROVEEDOR WHERE PROV_ID = ?";
            $queryValidarComprobanteId="SELECT * FROM COMPROBANTE WHERE COMPROBANTE_ID = ?";
            $queryValidarGuiaId="SELECT * FROM USUARIOS WHERE USU_ID = ?";
            $queryIngresarCompra ="
            CALL SP_INSERTAR_COMPRA(@P_COMPRA_ID,@VAL_GUIA_NRO_COMPROBANTE,@VAL_COMPRA_NRO_COMPROBANTE,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"; 
            $queryCompraID = "SELECT @P_COMPRA_ID";
            $queryGUIA_NRO_COMPROBANTE = "SELECT @VAL_GUIA_NRO_COMPROBANTE";
            $queryCOMPRA_NRO_COMPROBANTE = "SELECT @VAL_COMPRA_NRO_COMPROBANTE";
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
                        $stmtExistenciaComprobanteId->bind_param("s",$this->COMPROBANTE_ID);
                        $stmtExistenciaComprobanteId->execute();
                        $resultComprobanteId = get_result($stmtExistenciaComprobanteId);
                        //validamos si existe el comprobante ingresado
                        if(count($resultComprobanteId) > 0){
                            

                            $stmtIngresarCompra = $this->conn->prepare($queryIngresarCompra);
                            $stmtIngresarCompra->bind_param("sssssssssssssss",$hay_guia,$GUIA_NRO_SERIE,$GUIA_NRO_COMPROBANTE,$GUIA_FLETE,$GUIA_FECHA_EMISION,
                            $this->COMPRA_FECHA_EMISION_COMPROBANTE,$this->COMPRA_FECHA_REGISTRO,$this->COMPRA_NRO_SERIE,
                            $this->COMPRA_NRO_COMPROBANTE,$this->COMPRA_SUBTOTAL,$this->COMPRA_TOTAL,$this->COMPRA_DESCRIPCION,
                            $this->USU_ID,$this->COMPROBANTE_ID,$this->PROV_ID);
                            //verificamos que se haya realizado correctamente el ingreso de la compra
                            if(!$stmtIngresarCompra->execute()){

                                $code_error = "error_ejecucionQuery";
                                $mensaje = "Hubo un error crear la compra.";
                                return false; 

                            }else{
                                
                            

                                //se repite o no el nro de comprobante de la guía
                                $stmtGUIA_NRO_COMPROBANTE = $this->conn->prepare($queryGUIA_NRO_COMPROBANTE);
                                $stmtGUIA_NRO_COMPROBANTE->execute();
                                $resultGUIA_NRO_COMPROBANTE = get_result($stmtGUIA_NRO_COMPROBANTE); 


                                //se repite o no el nro de comprobante de la compra
                                $stmtCOMPRA_NRO_COMPROBANTE = $this->conn->prepare($queryCOMPRA_NRO_COMPROBANTE);
                                $stmtCOMPRA_NRO_COMPROBANTE->execute();
                                $resultCOMPRA_NRO_COMPROBANTE = get_result($stmtCOMPRA_NRO_COMPROBANTE); 

                                

                                if (count($resultGUIA_NRO_COMPROBANTE) > 0) {
                                    //obtenemos verdadero o falso dependiendo si es que se repite el nro de comprobante de la guía que se ingresará 
                                    $guia_nro_comprobante_valido = array_shift($resultGUIA_NRO_COMPROBANTE)["@VAL_GUIA_NRO_COMPROBANTE"];
                                }

                                if (count($resultCOMPRA_NRO_COMPROBANTE) > 0) {
                                    //obtenemos verdadero o falso dependiendo si es que se repite el nro de comprobante de la compra que se va a ingresar
                                    $compra_nro_comprobante_valido = array_shift($resultCOMPRA_NRO_COMPROBANTE)["@VAL_COMPRA_NRO_COMPROBANTE"];
                                }

                                //si es falso es por que se repite
                                
                                if(!$compra_nro_comprobante_valido){

                                    $code_error = "error_ExistenciaNroComprobanteCompra";
                                    $mensaje = "El número del comprobante de la compra ya existe.";
                                    return false;
                                    

                                }else{
                                
                                    if(!$guia_nro_comprobante_valido){

                                        $code_error = "error_ExistenciaNroComprobanteGuía";
                                        $mensaje = "El número del comprobante de la guía ya existe.";
                                        return false;

                                    }else{

                                        //Obtiene el id de la compra creada
                                        $stmtCompraId = $this->conn->prepare($queryCompraID);
                                        $stmtCompraId->execute();
                                        $resultCompraId = get_result($stmtCompraId);  

                                        if (count($resultCompraId) > 0) {
                                            //se guarda el id de la compra creada en una variable
                                            $Compra_id = array_shift($resultCompraId)["@P_COMPRA_ID"];
                                        }else{
                                            $code_error = "error_ejecucionQuery";
                                            $mensaje = "No se logró obtener el id de la compra realizada.";
                                            return false; 
                                        }
                                    }
                                    
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