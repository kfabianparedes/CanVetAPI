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
    public $PROV_ID;
    public $PRO_ESTADO; 
    
    public function __construct($db){
        $this->conn = $db;
    }

    function crearProducto(&$mensaje,&$code_error){
    
        $verificarExistenciaNombre ="select * from PRODUCTO where PRO_NOMBRE = ?";
        $verificarIdCategoria = "select * from CATEGORIA where CAT_ID =?"; 
        $verificarIdProveedor = "select * from PROVEEDOR where PROV_ID =?"; 
        $query  = "INSERT INTO PRODUCTO(PRO_NOMBRE,PRO_PRECIO_VENTA,PRO_PRECIO_COMPRA,PRO_STOCK,PRO_TAMANIO_TALLA,CAT_ID,PROV_ID,PRO_ESTADO) VALUES(?,?,?,?,?,?,?,1)";
        
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

                    $stmtExistenciaProvID = $this->conn->prepare($verificarIdProveedor);
                    $stmtExistenciaProvID->bind_param("s",$this->PROV_ID);
                    $stmtExistenciaProvID->execute();
                    $resultProveedorId = get_result($stmtExistenciaProvID);
                    if(count($resultProveedorId) > 0){

                        $stmt = $this->conn->prepare($query);
                        $stmt->bind_param("sssssss",$this->PRO_NOMBRE,$this->PRO_PRECIO_VENTA,$this->PRO_PRECIO_COMPRA,$this->PRO_STOCK,$this->PRO_TAMANIO_TALLA,$this->CAT_ID,$this->PROV_ID);
                        $stmt->execute();
                        $mensaje = "Se ha creado el producto con éxito";
                        return true;

                    }else{

                        //SI YA EXISTE UN PRODUCTO CON EL NOMBRE MOSTRAMOS EL ERROR
                        $code_error = "error_exitenciaProveedorId";
                        $mensaje = "El id del proveedor no existe.";
                        return false; 

                    }


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
    function actualizarProducto(&$mensaje,&$code_error,$fecha){

        $verificarExistenciaIdProducto = "select * from PRODUCTO where PRO_ID = ?"; 
        $verificarExistenciaNombre ="select * from PRODUCTO where PRO_NOMBRE = ? and PRO_ID <> ? ";
        $verificarIdCategoria = "select * from CATEGORIA where CAT_ID =?"; 
        $verificarIdProveedor = "select * from PROVEEDOR where PROV_ID =?"; 
        $queryEditarSinPrecioAnterior = "UPDATE PRODUCTO SET PRO_NOMBRE = ?, PRO_PRECIO_VENTA = ?,PRO_PRECIO_COMPRA = ?,PRO_TAMANIO_TALLA = ?,CAT_ID = ?,PROV_ID = ? where PRO_ID = ?"; //falta acabar
        $queryEditarConPrecioAnterior = "UPDATE PRODUCTO SET PRO_NOMBRE = ?, PRO_PRECIO_VENTA = ?,PRO_PRECIO_COMPRA = ?,PRO_TAMANIO_TALLA = ?,CAT_ID = ? ,PROV_ID = ?,PRO_PRECIO_ANTERIOR = ?,PRO_FECHA_CAMBIO_PRECIO = ? where PRO_ID = ?"; //falta acabar
        try {
            
            $stmtId = $this->conn->prepare($verificarExistenciaIdProducto);
            $stmtId->bind_param("s",$this->PRO_ID);
            $stmtId->execute();
            $resultId = get_result($stmtId);
            
            if(count($resultId) > 0){

                $stmtNombre = $this->conn->prepare($verificarExistenciaNombre);
                $stmtNombre->bind_param("ss",$this->PRO_NOMBRE,$this->PRO_ID);
                $stmtNombre->execute();
                $resultNombre = get_result($stmtNombre);

                if(count($resultNombre) < 1){

                    $stmtExistenciaCatID = $this->conn->prepare($verificarIdCategoria);
                    $stmtExistenciaCatID->bind_param("s",$this->CAT_ID);
                    $stmtExistenciaCatID->execute();
                    $resultCategoriaId = get_result($stmtExistenciaCatID);

                    //VERIFICAMOS QUE EXISTA EL ID DE LA CATEGORIA INGRESADA
                if(count($resultCategoriaId) > 0){

                    $stmtExistenciaProvID = $this->conn->prepare($verificarIdProveedor);
                    $stmtExistenciaProvID->bind_param("s",$this->PROV_ID);
                    $stmtExistenciaProvID->execute();
                    $resultProveedorId = get_result($stmtExistenciaProvID);

                    if(count($resultProveedorId) > 0){

                        $precioCompraAnterior = 0; 
                        while ($dato = array_shift($resultId)) {
                            $precioCompraAnterior = $dato['PRO_PRECIO_COMPRA'];
                         }
                        if($precioCompraAnterior == $this->PRO_PRECIO_COMPRA ){

                            $stmt = $this->conn->prepare($queryEditarSinPrecioAnterior);
                            $stmt->bind_param("sssssss",$this->PRO_NOMBRE,$this->PRO_PRECIO_VENTA,$this->PRO_PRECIO_COMPRA,$this->PRO_TAMANIO_TALLA,$this->CAT_ID,$this->PROV_ID,$this->PRO_ID);
                            $stmt->execute();

                        }else{
                            $fecha = date("Y-m-d");
                            echo $fecha; 
                            $stmt = $this->conn->prepare($queryEditarConPrecioAnterior);
                            $stmt->bind_param("sssssssss",$this->PRO_NOMBRE,$this->PRO_PRECIO_VENTA,$this->PRO_PRECIO_COMPRA,$this->PRO_TAMANIO_TALLA,$this->CAT_ID,$this->PROV_ID,$precioCompraAnterior,$fecha,$this->PRO_ID);
                            $stmt->execute();

                        }


                        $mensaje = "Se ha creado el producto con éxito";
                        return true;

                    }else{

                        //SI YA EXISTE UN PRODUCTO CON EL NOMBRE MOSTRAMOS EL ERROR
                        $code_error = "error_exitenciaProveedorId";
                        $mensaje = "El id del proveedor no existe.";
                        return false; 

                    }


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

            }else{
                
                $code_error = "error_exitenciaId";
                $mensaje = "El producto no existe.";
                return false; 

            }

        } catch (Throwable $th) {

            $code_error = "error_deBD";
            $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
            $exito = false;
        }
    }
    function obtenerProductoPorId(&$mensaje,&$exito,&$code_error){
        
        $verificarExistenciaDeProducto = "SELECT * FROM PRODUCTO WHERE PRO_ID = ?";
        $obtenerProductoPorId = "SELECT P.*, C.CAT_NOMBRE,PV.PROV_EMPRESA_PROVEEDORA FROM PRODUCTO P 
        INNER JOIN CATEGORIA C ON (P.CAT_ID = C.CAT_ID) 
        INNER JOIN PROVEEDOR PV ON (P.PROV_ID = PV.PROV_ID ) WHERE P.PRO_ID = ?";

        try {

            $stmtExistenciaProId = $this->conn->prepare($verificarExistenciaDeProducto);
            $stmtExistenciaProId->bind_param("s",$this->PRO_ID);
            $stmtExistenciaProId->execute();
            $resultProductoId = get_result($stmtExistenciaProId);

            if(count($resultProductoId) > 0 ){

                $stmt = $this->conn->prepare($obtenerProductoPorId);
                $stmt->bind_param("s",$this->PRO_ID);
                $stmt->execute();
                $result = get_result($stmt); 
                
    
                $mensaje = "Solicitud ejecutada con exito";
                $exito = true;
                return $result;

            }else{

                $code_error = "error_deExistenciaProducto";
                $mensaje = "El producto seleccionado no existe.";
                $exito = false;

            }

        } catch (Throwable $th) {
            
        }
    }

    function listarProductosPorProveedor(&$mensaje,&$exito,&$code_error){
        
        $validarExistenciaDeProveedor = "SELECT * FROM PROVEEDOR WHERE PROV_ID = ? ";
        $listarProductosPorProveedor = "SELECT P.*, C.CAT_NOMBRE,PV.PROV_EMPRESA_PROVEEDORA FROM PRODUCTO P 
        INNER JOIN CATEGORIA C ON (P.CAT_ID = C.CAT_ID) 
        INNER JOIN PROVEEDOR PV ON (P.PROV_ID = PV.PROV_ID ) WHERE P.PROV_ID = ?";
        $datos = []; 
        try {
            $stmtExistenciaProvId = $this->conn->prepare($validarExistenciaDeProveedor);
            $stmtExistenciaProvId->bind_param("s",$this->PROV_ID);
            $stmtExistenciaProvId->execute();
            $resultProveedorId = get_result($stmtExistenciaProvId);

            if(count($resultProveedorId) > 0){

                $stmt = $this->conn->prepare($listarProductosPorProveedor);
                $stmt->bind_param("s",$this->PROV_ID);
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

            }else{
                $code_error = "error_deExistenciaProveedor";
                $mensaje = "El proveedor seleccionado no existe.";
                $exito = false;
            }

        } catch (Throwable $th) {
            $code_error = "error_deBD";
            $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
            $exito = false;
        }
    }


    function listarProductos(&$mensaje,&$exito,&$code_error){
        $query = "SELECT P.*, C.CAT_NOMBRE,PV.PROV_EMPRESA_PROVEEDORA FROM PRODUCTO P 
        INNER JOIN CATEGORIA C ON (P.CAT_ID = C.CAT_ID) 
        INNER JOIN PROVEEDOR PV ON (P.PROV_ID = PV.PROV_ID )";
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
        $query = "UPDATE PRODUCTO SET PRO_ESTADO = ? WHERE PRO_ID = ?";

            try {
                //EJECTUAMOS LA CONSULTA PARA ACTUALIZAR EL ESTADO DE LA CATEGORIA 
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("ss",$this->PRO_ESTADO,$this->PRO_ID);
                $stmt->execute();

                $mensaje = "Se ha actualizado el producto con éxito";
                return true;
            } catch (Throwable $th) {
                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                $exito = false;
            }
    }
}
?>