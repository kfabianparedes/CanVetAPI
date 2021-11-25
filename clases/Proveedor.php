<?php
include_once '../../util/mysqlnd.php';

class Proveedor{
    private $conn; 

    public $PROV_ID;
    public $PROV_RUC;
    public $PROV_EMPRESA_PROVEEDORA;
    public $PROV_NUMERO_CONTACTO;
    public $PROV_ESTADO; 

    public function __construct($db){
        $this->conn = $db;
    }

    function listarProveedor(&$mensaje,&$exito,&$code_error){
        $query = "SELECT * FROM PROVEEDOR";
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

    function actualizarProveedor(&$mensaje,&$code_error){

        $queryVerificarExistenciaId = "SELECT * from PROVEEDOR WHERE PROV_ID = ? ";
        $queryVerificarExistenciaRuc = "SELECT * from PROVEEDOR WHERE PROV_RUC = ? AND PROV_ID <> ? ";
        $queryVerificarExistenciaNombreEmpresa = "SELECT * from PROVEEDOR WHERE PROV_EMPRESA_PROVEEDORA = ?  AND PROV_ID  <> ?";
        $query = "UPDATE PROVEEDOR SET PROV_RUC = ?, PROV_EMPRESA_PROVEEDORA = ?, PROV_NUMERO_CONTACTO= ? WHERE PROV_ID = ? "; 

        
        try {

        $stmtId = $this->conn->prepare($queryVerificarExistenciaId);
        $stmtId->bind_param("s",$this->PROV_ID);
        $stmtId->execute();
        $resultId = get_result($stmtId);

        if(count($resultId) > 0){
            $stmtRuc = $this->conn->prepare($queryVerificarExistenciaRuc);
            $stmtRuc->bind_param("ss",$this->PROV_RUC,$this->PROV_ID);
            $stmtRuc->execute();
            $resultRuc = get_result($stmtRuc);
            //VERIFICAMOS SI EL RUC INGRESADO YA EXISTE
            if(count($resultRuc) < 1){
                //SI ES QUE EL RUC INGRESADO NO EXISTE PASA EL FILTRO Y VERIFICAMOS SI YA EXISTE UN PROVEEDOR CON EL NOMBRE INGRESADO

                $stmtExistenciaNombreEmpresa = $this->conn->prepare($queryVerificarExistenciaNombreEmpresa);
                $stmtExistenciaNombreEmpresa->bind_param("ss",$this->PROV_EMPRESA_PROVEEDORA,$this->PROV_ID);
                $stmtExistenciaNombreEmpresa->execute();
                $resultaNombreEmpresa = get_result($stmtExistenciaNombreEmpresa);
                
                if(count($resultaNombreEmpresa) < 1){
                    //SI ES QUE PASA LOS DOS FILTRO CREAMOS EL PROVEEDOR
                    $stmt = $this->conn->prepare($query);
                    $stmt->bind_param("ssss",$this->PROV_RUC,$this->PROV_EMPRESA_PROVEEDORA,$this->PROV_NUMERO_CONTACTO,$this->PROV_ID);
                    $stmt->execute();
    
                    $mensaje = "Se ha actualizó el proveedor con éxito";
                    return true;
                }else{
                    $code_error = "error_existenciaNombreEmpresa";
                    $mensaje = "El nombre de la empresa ingresado ya existe.";
                    return false; 
                }
                
            }else{
                $code_error = "error_existenciaRuc";
                $mensaje = "El RUC ingresado ya existe.";
                return false; 
            }
        }else{
            $code_error = "error_existenciaId";
            $mensaje = "El id de la empresa ingresado no existe.";
            return false; 
        }
            
        }catch (Throwable $th) {
            $code_error = "error_deBD";
            $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
            return false;
        }
    }



    function crearProveedor(&$mensaje,&$code_error){

        $queryVerificarExistenciaRuc = "select * from PROVEEDOR where PROV_RUC = ? ";
        $queryVerificarExistenciaNombreEmpresa = "select * from PROVEEDOR where PROV_EMPRESA_PROVEEDORA = ? ";
        $query = "INSERT INTO PROVEEDOR(PROV_RUC,PROV_EMPRESA_PROVEEDORA,PROV_NUMERO_CONTACTO,PROV_ESTADO) VALUES(?,?,?,1)"; 
       

        try {
            $stmtRuc = $this->conn->prepare($queryVerificarExistenciaRuc);
            $stmtRuc->bind_param("s",$this->PROV_RUC);
            $stmtRuc->execute();
            $resultRuc = get_result($stmtRuc);
            //VERIFICAMOS SI EL RUC INGRESADO YA EXISTE
            if(count($resultRuc) < 1){
                //SI ES QUE EL RUC INGRESADO NO EXISTE PASA EL FILTRO Y VERIFICAMOS SI YA EXISTE UN PROVEEDOR CON EL NOMBRE INGRESADO

                $stmtExistenciaNombreEmpresa = $this->conn->prepare($queryVerificarExistenciaNombreEmpresa);
                $stmtExistenciaNombreEmpresa->bind_param("s",$this->PROV_EMPRESA_PROVEEDORA);
                $stmtExistenciaNombreEmpresa->execute();
                $resultaNombreEmpresa = get_result($stmtExistenciaNombreEmpresa);
                
                if(count($resultaNombreEmpresa) < 1){
                    //SI ES QUE PASA LOS DOS FILTRO CREAMOS EL PROVEEDOR
                    $stmt = $this->conn->prepare($query);
                    $stmt->bind_param("sss",$this->PROV_RUC,$this->PROV_EMPRESA_PROVEEDORA,$this->PROV_NUMERO_CONTACTO);
                    $stmt->execute();
    
                    $mensaje = "Se ha ingresó el proveedor con éxito";
                    return true;
                }else{
                    $code_error = "error_exitenciaNombreEmpresa";
                    $mensaje = "El nombre de la empresa ingresado ya existe.";
                    return false; 
                }
                
            }else{
                $code_error = "error_exitenciaRuc";
                $mensaje = "El RUC ingresado ya existe.";
                return false; 
            }
  
        } catch (Throwable $th) {
            $code_error = "error_deBD";
            $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
            return false;
        }
    }

    function habilitarInhabilitarProveedor(&$mensaje,&$code_error){

        $queryVerificarExistenciaId = "SELECT * from PROVEEDOR WHERE PROV_ID = ? ";
        $query = "UPDATE PROVEEDOR SET PROV_ESTADO = ? WHERE PROV_ID = ?";

            try {
                $stmtId = $this->conn->prepare($queryVerificarExistenciaId);
                $stmtId->bind_param("s",$this->PROV_ID);
                $stmtId->execute();
                $resultId = get_result($stmtId);

                if(count($resultId) > 0){

                    //EJECTUAMOS LA CONSULTA PARA ACTUALIZAR EL ESTADO DE LA CATEGORIA 
                    $stmt = $this->conn->prepare($query);
                    $stmt->bind_param("ss",$this->PROV_ESTADO,$this->PROV_ID);
                    $stmt->execute();

                    $mensaje = "Se ha actualizado el proveedor con éxito";
                    return true;

                }else{
                    $code_error = "error_existenciaId";
                    $mensaje = "El id de la empresa ingresado no existe.";
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