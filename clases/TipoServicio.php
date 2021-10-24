<?php
    include_once '../../util/mysqlnd.php';
    
    class TipoServicio{
        private $conn; 

        public $TIPO_SERVICIO_ID; 
        public $TIPO_SERVICIO_NOMBRE;   

        public function __construct($db){
            $this->conn = $db;
        }

        function listarTipoServicio(&$mensaje,&$code_error,&$exito){
            $query = "SELECT * FROM TIPO_SERVICIO"; 
            $datos = []; 
            try {
                
                $stmt = $this->conn->prepare($query);
                if(!$stmt->execute()){

                    $code_error = "error_ejecucionQuery";
                    $mensaje = "Hubo un error al listar los tipos de servicios.";
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