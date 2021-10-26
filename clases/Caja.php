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
        function cerrarCaja(&$mensaje, &$code_error,&$CAJA_ID){
            
        }
    }   
?>