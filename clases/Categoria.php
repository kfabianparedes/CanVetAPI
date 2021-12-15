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
        
        function listarCategoriasActivas(&$mensaje,&$exito,&$code_error){
            $query="SELECT * FROM CATEGORIA WHERE CAT_ESTADO = 1 ORDER BY CAT_NOMBRE ASC ";
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
            
            } catch (Throwable $th) {
                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                return false;
            }
            
        }

        function editarCategoria(&$mensaje,&$code_error,$NUEVO_NOMBRE){
            $queryValidarExistenciID="SELECT * FROM CATEGORIA where CAT_ID = ?";
            $queryValidarExistenciaNombre="SELECT * FROM CATEGORIA where CAT_NOMBRE = ? AND CAT_ID <> ?";
            $query = "UPDATE CATEGORIA SET CAT_NOMBRE = ? WHERE CAT_ID = ?";

            try {

                //COMPROBAMOS DE QUE EXISTA EL ID DE LA CATEGORIA
                $stmtId = $this->conn->prepare($queryValidarExistenciID);
                $stmtId->bind_param("s",$this->CAT_ID);
                $stmtId->execute();
                $resultID = get_result($stmtId);

                if(count($resultID) > 0 ){

                    //COMPROBAMOS DE QUE EL NUEVO NOMBRE DE LA CATEGORIA EXISTA. 
                    $stmtNombre = $this->conn->prepare($queryValidarExistenciaNombre);
                    $stmtNombre->bind_param("ss",$NUEVO_NOMBRE,$this->CAT_ID);
                    $stmtNombre->execute();
                    $resultNombre = get_result($stmtNombre);

                    if(count($resultNombre) < 1){

                        $stmtUpdate = $this->conn->prepare($query);
                        $stmtUpdate->bind_param("ss",$NUEVO_NOMBRE,$this->CAT_ID);
                        $stmtUpdate->execute();
                        $mensaje = "Se editó la categoría con éxito";
                        return true;

                    }else{

                        //SI ES QUE YA EXISTE UNA CATEGORIA PUES MOSTRAMOS EL ERROR DE QUE YA EXISTE UNA CATEGORIA CON ESE NOMBRE
                        $code_error = "error_exitenciaNombre";
                        $mensaje = "El nombre ingresado para crear la nueva categoría ya existe.";
                        return false; 

                    }
                }else{

                    //SI ES QUE NO EXISTE UNA CATEGORIA CON EL ID INGRESADO SE MUESTRA EL ERROR DE NO EXISTENCIA ID
                    $code_error = "error_noExistenciaId";
                    $mensaje = "El Id de la categoria ingresado no existe.";
                    return false; 

                }

            } catch (Throwable $th) {
                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                return false;
            }

        }
        function habilitarInhabilitarCategoria(&$mensaje,&$code_error){

            $queryValidarExistenciID="SELECT * FROM CATEGORIA where CAT_ID = ?";
            $query = "UPDATE CATEGORIA SET CAT_ESTADO = ? WHERE CAT_ID = ?";

            try {

                //COMPROBAMOS DE QUE EXISTA EL ID DE LA CATEGORIA
                $stmtId = $this->conn->prepare($queryValidarExistenciID);
                $stmtId->bind_param("s",$this->CAT_ID);
                $stmtId->execute();
                $resultID = get_result($stmtId);

                if(count($resultID) > 0 ){
                    //EJECTUAMOS LA CONSULTA PARA ACTUALIZAR EL ESTADO DE LA CATEGORIA 
                    $stmt = $this->conn->prepare($query);
                    $stmt->bind_param("ss",$this->CAT_ESTADO,$this->CAT_ID);
                    $stmt->execute();

                    $mensaje = "Se ha actualizado la categoría con éxito";
                    return true;
                }else{
                    $code_error = "error_noExistenciaId";
                    $mensaje = "El Id de la categoria ingresado no existe.";
                    return false; 
                }   
               
            } catch (Throwable $th) {
                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                return false;
            }

        }
        


    }

?>