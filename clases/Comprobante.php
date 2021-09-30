<?php
    include_once '../../util/mysqlnd.php';

    class Comprobante{
        private $conn; 

        public $COMPROBANTE_ID;
        public $COMPROBANTE_TIPO;

        public function __construct($db){
            $this->conn = $db;
        }
 
        function listarComprobantes(&$mensaje,&$code_error){
           $listarComprobantes = "Select * from COMPROBANTE";
           $datos = [];
           try {
               
                $stmt = $this->conn->prepare($listarComprobantes);
                $stmt->execute();
                $result = get_result($stmt); 
                
                if (count($result) > 0) {                
                    while ($dato = array_shift($result)) {    
                        $datos[] = $dato;
                    }
                }

                $mensaje = "Solicitud ejecutada con exito";
                $exito = true;
                return $datos;

           } catch (Throwable $th) {

                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                $exito = false;

           }
        }
    
    }
?>