<?php

    include_once '../../util/mysqlnd.php';

    class DetalleVenta{

        private $conn; 

        public $DET_VENTA_ID;
        public $DET_CANTIDAD;
        public $DET_IMPORTE;
        public $VENTA_ID;
        public $PRO_ID;

    
        public function __construct($db){
            $this->conn = $db;
        } 

        function agregarDetalleVenta(&$mensaje, &$code_error,$VentaId){
            
            
            $queryVerificarVentaId="SELECT * FROM VENTA WHERE VENTA_ID = ? ";
            $queryVerificarProductoId="SELECT * FROM PRODUCTO WHERE PRO_ID = ? ";
            $queryAgregarDetalle="
            INSERT INTO DETALLE_VENTA(DET_CANTIDAD,DET_IMPORTE,VENTA_ID,PRO_ID)
            VALUES(?,?,?,?);";
            $queryDisminuirStock ="UPDATE PRODUCTO SET PRO_STOCK = ? WHERE PRO_ID = ?";

            try {
                $stmtExistenciaVentaId = $this->conn->prepare($queryVerificarVentaId);
                $stmtExistenciaVentaId->bind_param("s",$VentaId);
                $stmtExistenciaVentaId->execute();
                $resultVentaId = get_result($stmtExistenciaVentaId);
                //validamos si existe el id de la venta ingresada
                if(count($resultVentaId) > 0){
                    

                    $stmtExistenciaProductoId = $this->conn->prepare($queryVerificarProductoId);
                    $stmtExistenciaProductoId->bind_param("s",$this->PRO_ID);
                    $stmtExistenciaProductoId->execute();
                    $resultProductoId = get_result($stmtExistenciaProductoId);
                    
                    //validamos si existe el id de la compra ingresada
                    if(count($resultProductoId) > 0){

                         //se obtiene el stock que tiene el producto para luego sumarle la cantidad ingresada en la compra 
                        $cantidadAnteriorProducto = array_shift($resultProductoId)["PRO_STOCK"];

                        if($cantidadAnteriorProducto < $this->DET_CANTIDAD){
                            $code_error = "error_cantidadInsuficiente";
                            $mensaje = "El stock del producto es menor a la cantidad solicitada.";
                            return false; 
                        }else{

                            //se resta la cantidad ingresada de la venta con el stock del producto obtenido lineas arriba
                            $cantidadDetalle = $this->DET_CANTIDAD ;
                            $this->DET_CANTIDAD = $cantidadAnteriorProducto - $this->DET_CANTIDAD ;
                            $stmt = $this->conn->prepare($queryAgregarDetalle);
                            $stmt->bind_param("ssss",$cantidadDetalle,$this->DET_IMPORTE,$VentaId,$this->PRO_ID);
                            if(!$stmt->execute()){

                                $code_error = "error_ejecucionQuery";
                                $mensaje = "Hubo un error al disminuir el stock de los productos.";
                                return false; 

                            }else{

                                $stmtDisminuirStock = $this->conn->prepare($queryDisminuirStock);
                                $stmtDisminuirStock->bind_param("ss",$this->DET_CANTIDAD,$this->PRO_ID);
                                if(!$stmtDisminuirStock->execute()){

                                    $code_error = "error_ejecucionQuery";
                                    $mensaje = "Hubo un error al disminuir el stock de los productos.";
                                    return false; 
                                }else{
                                    $mensaje = "La solicitud se ha realizado con éxito.";
                                    return true;
                                }

                            }

                        }

                    }else{

                        $code_error = "error_ExistenciaProductoId";
                        $mensaje = "El id de producto ingresado no existe.";
                        $exito = false;

                    }

                        

                }else{

                    $code_error = "error_ExistenciaVentaId";
                    $mensaje = "El id de venta ingresado no existe.";
                    $exito = false;

                }
                
            } catch (Throwable $th) {
                
                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                $exito = false;

            }
        }

        function listarDetallesPorVenta(&$mensaje, &$code_error,&$exito){

            $queryValidarIdVenta="SELECT * FROM VENTA WHERE VENTA_ID = ?";
            $query ="SELECT PRO.PRO_NOMBRE,PRO.PRO_TAMANIO_TALLA,CAT.CAT_NOMBRE,PRO.PRO_PRECIO_COMPRA,PRO.PRO_PRECIO_VENTA,DET.DET_CANTIDAD,DET.DET_IMPORTE FROM DETALLE_VENTA DET
            INNER JOIN PRODUCTO PRO ON (DET.PRO_ID = PRO.PRO_ID)
            INNER JOIN CATEGORIA CAT ON (PRO.CAT_ID = CAT.CAT_ID)
            INNER JOIN PROVEEDOR PROV ON (PRO.PROV_ID = PROV.PROV_ID)
            WHERE VENTA_ID = ?";
            $datos = [];
            try {
                $stmtValidarIdVenta = $this->conn->prepare($queryValidarIdVenta);
                $stmtValidarIdVenta->bind_param("s",$this->VENTA_ID);
                $stmtValidarIdVenta->execute();
                $resultValidarIdVenta = get_result($stmtValidarIdVenta);

                if(count($resultValidarIdVenta) > 0){
                    $stmt = $this->conn->prepare($query);
                    $stmt->bind_param("s",$this->VENTA_ID);
                    if(!$stmt->execute()){
                        $code_error = "error_ejecucionQuery";
                        $mensaje = "Hubo un error al listar los detalles de las ventas.";
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
                }else{
                    $code_error = "error_NoExistenciaVentaId";
                    $mensaje = "El id de la venta ingresada no existe.";
                    $exito = false; 
                }
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