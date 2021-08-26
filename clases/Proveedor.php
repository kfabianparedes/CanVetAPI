<?php
include_once '../../util/mysqlnd.php';

class Proveedor{
    private $conn; 

    public $PROV_ID;
    public $PROV_RUC;
    public $PROV_EMPRESA_PROVEEDORA;
    public $PROV_NUMERO_CONTACTO;

    listarProveedor(&$mensaje,&$exito,&$code_error){
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

        $queryVerificarExistenciaId = "select * from proveedor where prov_id = ? ";
        $queryVerificarExistenciaRuc = "select * from proveedor where prov_ruc = ? ";
        $queryVerificarExistenciaNombreEmpresa = "select * from proveedor where PROV_EMPRESA_PROVEEDOR = ? ";
        $query = "INSERT INTO PROVEEDOR(PROV_RUC,PROV_EMPRESA_PROVEEDOR,PROV_NUMERO_CONTACTO) VALUES(?,?,?)"; 

        
        try {

        $stmtId = $this->conn->prepare($queryVerificarExistenciaId);
        $stmtId->bind_param("s",$this->PROV_ID);
        $stmtId->execute();
        $resultId = get_result($stmtId);

        if(count($resultRuc) < 1){
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
                    $stmt->bind_param("s",$this->PROV_EMPRESA_PROVEEDORA);
                    $stmt->execute();
    
                    $mensaje = "Se ha actualizó el proveedor con éxito";
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
        }else{
            $code_error = "error_exitenciaId";
            $mensaje = "El id de la empresa ingresado no existe.";
            return false; 
        }
            
        }catch (\Throwable $th) {
            $code_error = "error_deBD";
            $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
            $exito = false;
        }
    }



    function crearProveedor(&$mensaje,&$code_error){

        $queryVerificarExistenciaRuc = "select * from proveedor where prov_ruc = ? ";
        $queryVerificarExistenciaNombreEmpresa = "select * from proveedor where PROV_EMPRESA_PROVEEDOR = ? ";
        $query = "INSERT INTO PROVEEDOR(PROV_RUC,PROV_EMPRESA_PROVEEDOR,PROV_NUMERO_CONTACTO) VALUES(?,?,?)"; 


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
  
        } catch (\Throwable $th) {
            $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";
                $exito = false;
        }
    }

    function habilitarInhabilitarProveedor(&$mensaje,&$code_error){

    }
}


?>