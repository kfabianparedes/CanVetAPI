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
        public $CAJA_DESCRIPCION ; 
        public $USU_ID;

        public function __construct($db){
            $this->conn = $db;
        }

        function abrirCaja(&$mensaje, &$code_error,&$CAJA_ID){
            $query = "CALL SP_ABRIR_CAJA(@P_CAJA_ABIERTA,@P_CAJA_ID,?,?,?,?)";
            $_query = "SELECT @P_CAJA_ABIERTA";
            $query_ = "SELECT @P_CAJA_ID";
            $horaDeRegistro = date("H:i:s");
            $this->CAJA_APERTURA = $this->CAJA_APERTURA.' '.$horaDeRegistro ;
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
            $mensaje = "Solicitud ejecutada con ??xito.";
            return true;
        }

        function cerrarCaja(&$mensaje, &$code_error){
            
            $existeCaja = "SELECT * FROM CAJA WHERE CAJA_CODIGO = ?"; 
            $query = "UPDATE CAJA SET 
                CAJA_DESCUENTO_GASTOS = ? , CAJA_MONTO_EFECTIVO_VENTAS = ?, CAJA_MONTO_TARJETA_VENTAS = ?, CAJA_MONTO_YAPE_VENTAS = ?, CAJA_MONTO_EFECTIVO_SERVICIOS = ?,
                CAJA_MONTO_TARJETA_SERVICIOS = ?, CAJA_MONTO_YAPE_SERVICIOS = ?, CAJA_MONTO_FINAL = ?, CAJA_CIERRE = ?, CAJA_DESCRIPCION = ? WHERE CAJA_CODIGO = ?
            ";
            $cajaCerrada = "SELECT * FROM CAJA WHERE CAJA_MONTO_FINAL IS NOT NULL AND CAJA_CODIGO = ?" ;
            setlocale(LC_ALL, 'es_PE');
            $this->CAJA_CIERRE = date("Y-m-d H:i:s");

            try {
                $stmtExisteCaja = $this->conn->prepare($existeCaja);
                $stmtExisteCaja->bind_param("s",$this->CAJA_CODIGO);
                $stmtExisteCaja->execute();
                $resultExisteCaja= get_result($stmtExisteCaja);
                //validamos si existe el id del usuario ingresado
                if(count($resultExisteCaja) > 0){

                    $stmtCajaCerrada = $this->conn->prepare($cajaCerrada);
                    $stmtCajaCerrada->bind_param("s",$this->CAJA_CODIGO);
                    $stmtCajaCerrada->execute();
                    $resultCajaCerrada= get_result($stmtCajaCerrada);
                    //validamos si existe el id del usuario ingresado
                    if(count($resultCajaCerrada) == 0){
                        
                        $stmt = $this->conn->prepare($query);
                        $stmt->bind_param("sssssssssss",$this->CAJA_DESCUENTO_GASTOS,$this->CAJA_MONTO_EFECTIVO_VENTAS,$this->CAJA_MONTO_TARJETA_VENTAS,
                        $this->CAJA_MONTO_YAPE_VENTAS,$this->CAJA_MONTO_EFECTIVO_SERVICIOS,$this->CAJA_MONTO_TARJETA_SERVICIOS,$this->CAJA_MONTO_YAPE_SERVICIOS,$this->CAJA_MONTO_FINAL
                        ,$this->CAJA_CIERRE,$this->CAJA_DESCRIPCION,$this->CAJA_CODIGO);
                        //verificamos que se haya realizado correctamente el ingreso de la compra
                        if(!$stmt->execute()){

                            $code_error = "error_ejecucionQuery";
                            $mensaje = "Hubo un error cerrar la caja.";
                            return false; 

                        }else{

                            $mensaje = "Caja cerrada con ??xito.";
                            return true; 

                        }

                    }else{
                        $code_error = "error_cajaCerrada";
                        $mensaje = "La caja ya est?? cerrada.";
                        return false; 
                    }

                }else{
                    $code_error = "error_noExistenciaCaja";
                    $mensaje = "No existe una caja abierta con el c??digo ingresado.";
                    return false; 
                }

            } catch (Throwable $th) {

                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                return false;

            }
        }

        function reportesCaja(&$mensaje, &$code_error,&$exito){

            $query = '
            SELECT SUM(CAJA_MONTO_EFECTIVO_VENTAS) AS CAJA_MONTO_EFECTIVO_VENTAS,SUM(CAJA_MONTO_TARJETA_VENTAS) AS CAJA_MONTO_TARJETA_VENTAS,SUM(CAJA_MONTO_YAPE_VENTAS) AS CAJA_MONTO_YAPE_VENTAS
            ,SUM(CAJA_MONTO_EFECTIVO_SERVICIOS) AS CAJA_MONTO_EFECTIVO_SERVICIOS,SUM(CAJA_MONTO_TARJETA_SERVICIOS) AS CAJA_MONTO_TARJETA_SERVICIOS,SUM(CAJA_MONTO_YAPE_SERVICIOS) AS CAJA_MONTO_YAPE_SERVICIOS
            ,CAJA_CODIGO,SUM(CAJA_DESCUENTO_GASTOS) AS CAJA_DESCUENTO_GASTOS, SUM(CAJA_MONTO_INICIAL) AS CAJA_MONTO_INICIAL,date_format(CAJA_APERTURA,"%Y-%m-%d") AS CAJA_FECHA
            FROM CAJA WHERE DATE_FORMAT(CAJA_APERTURA,"%Y-%m") = ? AND CAJA_CIERRE IS NOT NULL
            GROUP BY DATE_FORMAT(CAJA_APERTURA,"%Y-%m-%d") ORDER BY DATE_FORMAT(CAJA_APERTURA,"%Y-%m-%d") DESC'; 

            $anioMes = date('Y-m'); 
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

                        $mensaje = "El reporte se realiz?? con ??xito";
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

        function recuperarCajaEmpleado(&$mensaje, &$code_error,&$exito){

            $query  = 'SELECT CAJA_CODIGO, CAJA_ID FROM CAJA WHERE USU_ID = ? 
            AND DATE_FORMAT(CAJA_APERTURA,"%Y-%m-%d") = ? AND CAJA_CIERRE IS NULL';

            $queryValidarUsuario = "SELECT * FROM USUARIOS WHERE USU_ID = ?";

            $diaActual = date("Y-m-d");
            $datos;
            try {
                $stmtValidarUsuario = $this->conn->prepare($queryValidarUsuario);
                $stmtValidarUsuario->bind_param("s",$this->USU_ID);
                $stmtValidarUsuario->execute();
                $resultUsuario= get_result($stmtValidarUsuario);
                //validamos si existe el id del usuario ingresado
                if(count($resultUsuario) > 0){
                    $stmt = $this->conn->prepare($query);
                    $stmt->bind_param("ss",$this->USU_ID,$diaActual);
                    if(!$stmt->execute()){
                        $code_error = "error_ejecucionQuery";
                        $mensaje = "Hubo un error reportar los cierres de caja del mes actual.";
                        $exito = false; 
                    }else{
                        $result = get_result($stmt); 
                        if (count($result) > 0) {                
                            $datos = array_shift($result);
                        }
                        $mensaje = "Solicitud realizada con ??xito.";
                        $exito = true;
                    }  
                }else{
                    $code_error = "error_NoUsuarioId";
                    $mensaje = "El id del usuario ingresado no existe.";
                    $exito =  false;
                }
                return $datos;
            } catch (Throwable $th) {
                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                $exito = false;
                return $datos;
            }
        }

        function reportarCajasPorFecha(&$mensaje, &$code_error, &$exito){

            $query = 
            'SELECT CAJ.*, 
            CONCAT(USU.USU_NOMBRES," ",USU.USU_APELLIDO_PATERNO," ",USU.USU_APELLIDO_MATERNO) AS USU_NOMBRE,
            (CAJ.CAJA_MONTO_EFECTIVO_VENTAS+CAJ.CAJA_MONTO_TARJETA_VENTAS +CAJ.CAJA_MONTO_YAPE_VENTAS +CAJ.CAJA_MONTO_EFECTIVO_SERVICIOS+CAJ.CAJA_MONTO_TARJETA_SERVICIOS+CAJ.CAJA_MONTO_YAPE_SERVICIOS) AS MONTO_TOTAL FROM CAJA CAJ 
            INNER JOIN USUARIOS USU ON (CAJ.USU_ID = USU.USU_ID)
            WHERE DATE_FORMAT(CAJA_APERTURA,"%Y-%m-%d") = ? AND CAJ.CAJA_CIERRE IS NOT NULL';

            $datos = [];
            
            try {
                
                //reporte del mes anterior 
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("s",$this->CAJA_APERTURA);
                if(!$stmt->execute()){

                    $code_error = "error_ejecucionQuery";
                    $mensaje = "Hubo un error reportar las cajas del d??a seleccionado.";
                    $exito = false; 

                }else{
                    
                    $result = get_result($stmt); 
                
                    if (count($result) > 0) {                
                        while ($dato = array_shift($result)) {    
                            $datos[] = $dato;
                        }
                    }
                    
                    $mensaje = "El reporte se realiz?? con ??xito";
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
    }   
?>