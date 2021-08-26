<?php
include_once '../../util/mysqlnd.php';

class Producto{
    private $conn; 


    public $PRO_ID; 
    public $PRO_NOMBRE; 
    public $PRO_CODIGO; 
    public $PRO_PRECIO_VENTA; 
    public $PRO_PRECIO_COMPRA; 
    public $PRO_STOCK; 
    public $PRO_TAMANIO_TALLA; 
    public $CAT_ID; 
    
    public function __construct($db){
        $this->conn = $db;
    }

    function crearProducto(&$mensaje,&$code_error){
    
        $verificarExistenciaNombre ="select * from PRODUCTO where PRO_NOMBRE = ?";
        $verificarIdCategoria = "select * from CATEGORIA where CAT_ID =?"; 
        $query  = "INSERT INTO PRODUCTO(PRO_NOMBRE,PRO_CODIGO,PRO_PRECIO_VENTA,PRO_PRECIO_COMPRA,PRO_STOCK,PRO_TAMANIO_TALLA,CAT_ID) VALUES(?,?,?,?,?,?,?)";
        
        try {
            $stmtNombre = $this->conn->prepare($verificarExistenciaNombre);
            $stmtNombre->bind_param("s",$this->PRO_NOMBRE);
            $stmtNombre->execute();
            $resultNombre = get_result($stmtNombre);
            //VERIFICAMOS QUE EL NOMBRE DEL NUEVO PRODUCTO NO ESTÉ REGISTRADO
            if(count($resultNombre) < 1){
                $stmtExistenciaCatID = $this->conn->prepare($verificarIdCategoria);
                $stmtExistenciaCatID->bind_param("s",$this->CAT_ID);
                $stmtExistenciaCatID->execute();
                $resultCategoriaId = get_result($stmtExistenciaCatID);
                //VERIFICAMOS QUE EXISTA EL ID DE LA CATEGORIA INGRESADA
                if(count($resultCategoriaId) > 0){
                    
                    $stmt = $this->conn->prepare($query);
                    $stmt->bind_param("sssssss",$this->PRO_NOMBRE,$this->PRO_CODIGO,$this->PRO_PRECIO_VENTA,$this->PRO_PRECIO_COMPRA,$this->PRO_STOCK,$this->PRO_TAMANIO_TALLA,$this->CAT_ID);
                    $stmt->execute();
    
                    $mensaje = "Se ha creado el producto con éxito";
                    return true;

                }else{

                    //SI YA EXISTE UN PRODUCTO CON EL NOMBRE MOSTRAMOS EL ERROR
                    $code_error = "error_exitenciaCategoriaId";
                    $mensaje = "El id de la categoria no existe.";
                    return false; 

                }
            }else{
                //SI YA EXISTE UN PRODUCTO CON EL NOMBRE MOSTRAMOS EL ERROR
                $code_error = "error_exitenciaNombre";
                $mensaje = "El nombre ingresado para crear el nuevo producto ya existe.";
                return false; 
            }
            
        } catch (Throwable $th) {
            $code_error = "error_deBD";
            $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
            $exito = false;
        }
    }
    function actualizarProducto(&$mensaje,&$code_error){

    }

    function listarProductos(&$mensaje,&$exito,&$code_error){
        $query = "SELECT P.*, C.CAT_NOMBRE FROM PRODUCTO P INNER JOIN CATEGORIA C ON (P.CAT_ID = C.CAT_ID)";
        $datos= [];
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

    

   

    function habilitarInhabilitarProducto(&$mensaje,&$code_error){

    }
}
?>