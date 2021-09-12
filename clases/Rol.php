<?php
    include_once '../../util/mysqlnd.php';
    class Rol{
        private $conn;

        public $ROL_ID;
        public $ROL_NOMBRE;


        public function __construct($db){
            $this->conn = $db;
        }

        function obtenerRoles(&$mensaje, &$exito, &$code_error){
            $query = "SELECT * FROM ROL";
            $datos = [];
            try{
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
        
            }catch(Throwable  $e){
                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                $exito = false;
                return $datos;
            } 
        }
    }
?>
