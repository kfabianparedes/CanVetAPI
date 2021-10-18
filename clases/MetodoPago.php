<?php
    include_once '../../util/mysqlnd.php';
    class MetodoPago{
        private $conn;
        
        public $MDP_ID;
        public $MDP_NOMBRE;

        public function __construct($db){
            $this->conn = $db;
        }

        function listarMetodosDePago(&$mensaje,&$code_error,&$exito){
            $query = "SELECT * FROM METODO_PAGO";
            $datos = array();
            try {
                $stmt = $this->conn->prepare($query);
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