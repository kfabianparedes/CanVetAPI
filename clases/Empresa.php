<?php
    include_once '../../util/mysqlnd.php';
    class Empresa{
        private $conn;

        public $TIPO_EMPRESA_ID;
        public $TIPO_EMPRESA_CLAVE; 
        public $TIPO_EMPRESA_DESCRIPCION;

        public function __construct($db){
            $this->conn = $db;
        }

        function listarTipoEmpresas(&$mensaje, &$code_error, &$exito){
            $query = "SELECT * FROM TIPO_EMPRESA";
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
