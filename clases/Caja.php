<?php
    include_once '../../util/mysqlnd.php';
    class Caja{
        private $conn;
        
        public $CAJA_ID;
        public $CAJA_APERTURA;
        public $CAJA_CIERRE;
        public $CAJA_MONTO_INICIAL;
        public $CAJA_MONTO_FINAL;
        public $CAJA_DESCUENTO_GASTOS;
        public $CAJA_CODIGO;
        public $CAJA_MONTO_EFECTIVO_VENTAS;
        public $CAJA_MONTO_TARJETA_VENTAS;
        public $CAJA_MONTO_YAPE_VENTAS;
        public $CAJA_MONTO_EFECTIVO_SERVICIOS;
        public $CAJA_MONTO_YAPE_SERVICIOS;
        public $CAJA_MONTO_TARJETA_SERVICIOS;
        public $USU_ID;

        public function __construct($db){
            $this->conn = $db;
        }

        function abrirCaja(&$mensaje, &$code_error,&$CAJA_ID){
            $query = "CALL SP_ABRIR_CAJA(@P_CAJA_ABIERTA,@P_CAJA_ID,?,?,?,?)";
            $_query = "SELECT @P_CAJA_ABIERTA";
            $query_ = "SELECT @P_CAJA_ID";
            try {

                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("ssss",$this->CAJA_APERTURA,$this->CAJA_MONTO_INICIAL,$this->CAJA_CODIGO,$this->USU_ID);
                if(!$stmt->execute()){
                    $code_error = "error_ejecucionQuery";
                    $mensaje = "Hubo un error al abrir caja.";
                    return false; 
                }
                //Obtiene la variable P_USU_ID_VALIDO (boolean)
                $_stmt = $this->conn->prepare($_query);
                $_stmt->execute();
                $_result = get_result($_stmt);    

                //Obtiene el ID de la ultima sesion registrada
                $stmt_ = $this->conn->prepare($query_);
                $stmt_->execute();
                $result_ = get_result($stmt_);  

                if (count($_result) > 0) {
                    //obtenemos verdadero o falso dependiendo si es correcto el id del servicio
                    $CAJA_ABIERTA = array_shift($_result)["@P_CAJA_ABIERTA"];
                }
                if (count($result_) > 0) {
                    //obtenemos verdadero o falso dependiendo si es correcto el id del servicio
                    $CAJA_ID = array_shift($result_)["@P_CAJA_ID"];
                }
                
                if($CAJA_ABIERTA){
                    $code_error = 'error_cajaAbierta';
                    $mensaje = 'El usuario tiene la caja abierta en este dia.';
                    return false;
                }
            }catch (Throwable $th) {
                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                return false;
            }
            $mensaje = "Solicitud ejecutada con éxito.";
            return true;
        }

        function cerrarCaja(&$mensaje, &$code_error){
            
            $existeCaja = "SELECT * FROM CAJA WHERE CAJA_CODIGO = ?"; 
            $query = "UPDATE CAJA SET 
                CAJA_DESCUENTO_GASTOS = ? , CAJA_MONTO_EFECTIVO_VENTAS = ?, CAJA_MONTO_TARJETA_VENTAS = ?, CAJA_MONTO_YAPE_VENTAS = ?, CAJA_MONTO_EFECTIVO_SERVICIOS = ?,
                CAJA_MONTO_TARJETA_SERVICIOS = ?, CAJA_MONTO_YAPE_SERVICIOS = ?, CAJA_MONTO_FINAL = ? WHERE CAJA_CODIGO = ?
            ";
            $cajaCerrada = "SELECT * FROM CAJA WHERE CAJA_MONTO_FINAL IS NOT NULL " ;


            try {
                $stmtExisteCaja = $this->conn->prepare($existeCaja);
                $stmtExisteCaja->bind_param("s",$this->CAJA_CODIGO);
                $stmtExisteCaja->execute();
                $resultExisteCaja= get_result($stmtExisteCaja);
                //validamos si existe el id del usuario ingresado
                if(count($resultExisteCaja) > 0){

                    $stmtCajaCerrada = $this->conn->prepare($cajaCerrada);
                    $stmtCajaCerrada->execute();
                    $resultCajaCerrada= get_result($stmtCajaCerrada);
                    //validamos si existe el id del usuario ingresado
                    if(count($resultCajaCerrada) == 0){
                        
                        $stmt = $this->conn->prepare($query);
                        $stmt->bind_param("sssssssss",$this->CAJA_DESCUENTO_GASTOS,$this->CAJA_MONTO_EFECTIVO_VENTAS,$this->CAJA_MONTO_TARJETA_VENTAS,
                        $this->CAJA_MONTO_YAPE_VENTAS,$this->CAJA_MONTO_EFECTIVO_SERVICIOS,$this->CAJA_MONTO_TARJETA_SERVICIOS,$this->CAJA_MONTO_YAPE_SERVICIOS,$this->CAJA_MONTO_FINAL
                        ,$this->CAJA_CODIGO);
                        //verificamos que se haya realizado correctamente el ingreso de la compra
                        if(!$stmt->execute()){

                            $code_error = "error_ejecucionQuery";
                            $mensaje = "Hubo un error cerrar la caja.";
                            return false; 

                        }else{

                            $mensaje = "Caja cerrada con éxito.";
                            return true; 

                        }

                    }else{
                        $code_error = "error_cajaCerrada";
                        $mensaje = "La caja ya está cerrada.";
                        return false; 
                    }

                }else{
                    $code_error = "error_noExistenciaCaja";
                    $mensaje = "No existe una caja abierta con el código ingresado.";
                    return false; 
                }

            } catch (Throwable $th) {

                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                return false;

            }
        }

        function reportesCaja(&$mensaje, &$code_error,&$exito){

            $query = 'SELECT 
            CAJA_MONTO_EFECTIVO_VENTAS,CAJA_MONTO_TARJETA_VENTAS,CAJA_MONTO_YAPE_VENTAS,CAJA_MONTO_EFECTIVO_SERVICIOS,
            CAJA_MONTO_TARJETA_SERVICIOS,CAJA_MONTO_YAPE_SERVICIOS
            FROM CAJA WHERE DATE_FORMAT(CAJA_APERTURA,"%Y-%m") = ?'; 

            $anioMes = date('Y-m'); 
            echo $anioMes;
            $anioMesAnterior = strtotime('-1 month', strtotime($anioMes));
            $anioMesAnterior = date('Y-m', $anioMesAnterior); 

            $datos = [];
            $datosMesActual =[];
            $datosMesAterior =[];
            try {

                //reporte del mes 
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("s",$anioMes);
                if(!$stmt->execute()){

                    $code_error = "error_ejecucionQuery";
                    $mensaje = "Hubo un error reportar los cierres de caja del mes actual.";
                    $exito = false; 

                }else{
                    
                    $result = get_result($stmt); 
                
                    if (count($result) > 0) {                
                        while ($dato = array_shift($result)) {    
                            $datosMesActual[] = $dato;
                        }
                    }

                    //reporte del mes anterior 
                    $stmt = $this->conn->prepare($query);
                    $stmt->bind_param("s",$anioMesAnterior);
                    if(!$stmt->execute()){

                        $code_error = "error_ejecucionQuery";
                        $mensaje = "Hubo un error reportar los cierres de caja del mes anterior.";
                        $exito = false; 

                    }else{
                        
                        $resulta = get_result($stmt); 
                    
                        if (count($resulta) > 0) {                
                            while ($dato = array_shift($resulta)) {    
                                $datosMesAterior[] = $dato;
                            }
                        }

                        $mensaje = "El reporte se realizó con éxito";
                        $exito = true; 
                    }
                }

            $datos = array("mes_actual"=>$datosMesActual,"mes_anterior"=>$datosMesAterior);
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