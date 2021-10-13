<?php
    include_once '../../util/mysqlnd.php';

    class DetalleCompra{
        private $conn; 

        public $DET_COMPRA_ID;
        public $DET_CANTIDAD;
        public $DET_IMPORTE;
        public $COMPRA_ID;
        public $PRO_ID;


        public function __construct($db){
            $this->conn = $db;
        }

        function listarDetallesPorIdCompra(&$mensaje, &$code_error,&$exito){

            $queryValidarIdCompra = " SELECT * FROM COMPRA WHERE COMPRA_ID = ?";
            $queryListarDetalles = "
                SELECT DET.*,PRO.*,CAT.CAT_NOMBRE, PROV.PROV_EMPRESA_PROVEEDORA FROM DETALLE_COMPRA DET
                INNER JOIN PRODUCTO PRO ON (DET.PRO_ID = PRO.PRO_ID)
                INNER JOIN CATEGORIA CAT ON (PRO.CAT_ID = CAT.CAT_ID)
                INNER JOIN PROVEEDOR PROV ON (PRO.PROV_ID = PROV.PROV_ID)
                WHERE COMPRA_ID = ?
            ";

            $datos = [];
            try {

                $stmtValidarIdCompra = $this->conn->prepare($queryValidarIdCompra);
                $stmtValidarIdCompra->bind_param("s",$this->COMPRA_ID);
                $stmtValidarIdCompra->execute();
                $resultIdCompra = get_result($stmtValidarIdCompra);

                if(count($resultIdCompra) > 0){

                    $stmt = $this->conn->prepare($queryListarDetalles);
                    $stmt->bind_param("s",$this->COMPRA_ID);
                    if(!$stmt->execute()){

                        $code_error = "error_ejecucionQuery";
                        $mensaje = "Hubo un error al listar los detalles de la compra.";
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
                    

                }else{
                    
                    $code_error = "error_NoExistenciaCompraId";
                    $mensaje = "El id de la compra ingresada no existe.";
                    return false; 

                }
                return $datos;
            } catch (Throwable $th) {
               
                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                $exito = false;
                return $datos;

            }
        }

        function agregarDetalleCompra(&$mensaje, &$code_error,$Compra_id){
            
            $queryVerificarCompraId="SELECT * FROM COMPRA WHERE COMPRA_ID = ? ";
            $queryVerificarProductoId="SELECT * FROM PRODUCTO WHERE PRO_ID = ? ";
            $queryAgregarDetalle="
            INSERT INTO DETALLE_COMPRA(DET_CANTIDAD,DET_IMPORTE,COMPRA_ID,PRO_ID)
            VALUES(?,?,?,?);";
            $queryAumentaStock ="UPDATE PRODUCTO SET PRO_STOCK = ? WHERE PRO_ID = ?";
            
            
            try {
                $stmtExistenciaCompraId = $this->conn->prepare($queryVerificarCompraId);
                $stmtExistenciaCompraId->bind_param("s",$Compra_id);
                $stmtExistenciaCompraId->execute();
                $resultCompraId = get_result($stmtExistenciaCompraId);
                //validamos si existe el id de la compra ingresada
                if(count($resultCompraId) > 0){
                    

                    $stmtExistenciaProductoId = $this->conn->prepare($queryVerificarProductoId);
                    $stmtExistenciaProductoId->bind_param("s",$this->PRO_ID);
                    $stmtExistenciaProductoId->execute();
                    $resultProductoId = get_result($stmtExistenciaProductoId);
                    
                    //validamos si existe el id de la compra ingresada
                    if(count($resultProductoId) > 0){

                         //se obtiene el stock que tiene el producto para luego sumarle la cantidad ingresada en la compra 
                        $cantidadAnteriorProducto = array_shift($resultProductoId)["PRO_STOCK"];

                        //se suma la cantidad ingresada de la compra con el stock del producto obtenido lineas arriba
                        $this->DET_CANTIDAD = $this->DET_CANTIDAD + $cantidadAnteriorProducto;
                        $stmt = $this->conn->prepare($queryAgregarDetalle);
                        $stmt->bind_param("ssss",$this->DET_CANTIDAD,$this->DET_IMPORTE,$Compra_id,$this->PRO_ID);
                        if(!$stmt->execute()){

                            $code_error = "error_ejecucionQuery";
                            $mensaje = "Hubo un error al aumentar el stock de los productos.";
                            return false; 

                        }else{

                            $stmtAumentarStock = $this->conn->prepare($queryAumentaStock);
                            $stmtAumentarStock->bind_param("ss",$this->DET_CANTIDAD,$this->PRO_ID);
                            if(!$stmtAumentarStock->execute()){

                                $code_error = "error_ejecucionQuery";
                                $mensaje = "Hubo un error al aumentar el stock de los productos.";
                                return false; 
                            }else{
                                $mensaje = "La solicitud se ha realizado con éxito.";
                                return true;
                            }

                        }

                    }else{

                        $code_error = "error_ExistenciaProductoId";
                        $mensaje = "El id de producto ingresado no existe.";
                        $exito = false;

                    }

                }else{

                    $code_error = "error_ExistenciaCompraId";
                    $mensaje = "El id de compra ingresado no existe.";
                    $exito = false;

                }
                
            } catch (Throwable $th) {
                
                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                $exito = false;

            }
        }
    }

?>