<?php
    include_once '../../util/mysqlnd.php';

    class Categoria{
        private $conn; 

        public $CAT_ID;
        public $CAT_NOMBRE;
        public $CAT_ESTADO;

        public function __construct($db){
            $this->conn = $db;
        }
        
        
        function listarCategorias(&$mensaje,&$exito,&$code_error){
            $query="SELECT * FROM CATEGORIA";
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

        function registrarCategoria(&$mensaje,&$code_error){

            $queryValidarExistenciaNombre="SELECT * FROM CATEGORIA where CAT_NOMBRE = ?";
            $query="insert into CATEGORIA(CAT_NOMBRE,CAT_ESTADO) VALUES (?,1)" ;
            try {
                //COMPROBAMOS LA EXISTENCIA DEL NOMBRE DE LA CATEGORIA QUE SE VA A CREAR 
                $stmtNombre = $this->conn->prepare($queryValidarExistenciaNombre);
                $stmtNombre->bind_param("s",$this->CAT_NOMBRE);
                $stmtNombre->execute();
                $resultNombre = get_result($stmtNombre);
                
                if(count($resultNombre) < 1){
                    //COMO NO EXISTE NINGUNA CATEGORIA CON EL MISMO NOMBRE SE CREA LA NUEVA CATEGORIA
                    $stmt = $this->conn->prepare($query);
                    $stmt->bind_param("s",$this->CAT_NOMBRE);
                    $stmt->execute();

                    $mensaje = "Se ha creado la categoría con éxito";
                    return true;

                }else{
                    //SI ES QUE YA EXISTE UNA CATEGORIA PUES MOSTRAMOS EL ERROR DE QUE YA EXISTE UNA CATEGORIA CON ESE NOMBRE
                    $code_error = "error_exitenciaNombre";
                    $mensaje = "El nombre ingresado para crear la nueva categoría ya existe.";
                    return false; 
                }
            
            } catch (\Throwable $th) {
                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                $exito = false;
            }
            
        }

        function editarCategoria(){
            $query = "";

            try {
                //code...
            } catch (\Throwable $th) {
                //throw $th;
            }

        }
        function habilitarInhabilitarCategoria(&$mensaje,&$code_error){
            
            $query = "UPDATE CATEGORIA SET CAT_ESTADO = ? WHERE CAT_ID = ?";

            try {
                //EJECTUAMOS LA CONSULTA PARA ACTUALIZAR EL ESTADO DE LA CATEGORIA 
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("ss",$this->CAT_ESTADO,$this->CAT_ID);
                $stmt->execute();

                $mensaje = "Se ha actualizado la categoría con éxito";
                return true;
            } catch (\Throwable $th) {
                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                $exito = false;
            }

        }
        


    }

?>