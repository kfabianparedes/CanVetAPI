<?php
    include_once '../../util/mysqlnd.php';

    class Usuario{
        private $conn;

        public $USU_ID;
        public $USU_USUARIO;
        public $USU_CONTRASENIA;
        public $USU_NOMBRES;
        public $USU_APELLIDO_PATERNO;
        public $USU_APELLIDO_MATERNO;
        public $USU_SEXO; 
        public $USU_DNI;
        public $USU_CELULAR;
        public $USU_DIRECCION;
        public $USU_EMAIL;
        public $USU_ESTADO; //Habilitado(1) / Deshabilitado(0) / Cambio de contraseña (2) 
        public $ROL_ID; //administrador(1) y empleado(0)


        public function __construct($db){
            $this->conn = $db;
        }

        function login($IP ,&$usu_id, &$usu_nombres, &$usu_hash, &$usu_estado, &$usu_email, &$id_sesion,&$mensaje ,&$code_error){
            //Consulta
            $query0 = "SELECT COUNT(*) AS ID_ROL FROM ROL WHERE ROL_ID = ?";
            $query = "SELECT USU_ID, USU_CONTRASENIA, USU_ESTADO, USU_EMAIL, CONCAT(USU_NOMBRES, ' ', USU_APELLIDO_PATERNO, ' ', USU_APELLIDO_MATERNO) AS NOMBRE_COMPLETO FROM USUARIOS WHERE USU_USUARIO = ? AND ROL_ID = ? "; 
            $id_valido = false;
            try{
                $stmt = $this->conn->prepare($query0);
                $stmt->bind_param("s",$this->ROL_ID);
                $stmt->execute();
                $result = get_result($stmt); 
                if(count($result)<=0){
                    $code_error = "error_noExistenciaRol";                
                    $mensaje = "El rol buscado no existe";        
                    return false;
                }else{
                    try{
                        $stmt1 = $this->conn->prepare($query);
                        $stmt1->bind_param("ss",$this->USU_USUARIO,$this->ROL_ID);
                        $stmt1->execute();
                        $result1 = get_result($stmt1); 
                        if(count($result1)>0){ 
                            $usu = array_shift($result1);
                            if($this->USU_CONTRASENIA === $usu['USU_CONTRASENIA']){
                            //echo json_encode(array($this->USU_CONTRASENIA,$usu['USU_CONTRASENIA']));
                            //if(password_verify($this->USU_CONTRASENIA, $usu['USU_CONTRASENIA'])){//el primer parametro es la contraseña sin hash y el segundo parametro es la contraseña hasheada
                                $usu_hash = $usu['USU_CONTRASENIA'];
                                $usu_id = $usu['USU_ID'];
                                $usu_nombres = $usu['NOMBRE_COMPLETO'];
                                $usu_estado = $usu['USU_ESTADO'];
                                $usu_email = $usu['USU_EMAIL'];

                                $query2 = "CALL SP_CREAR_SESION(@P_USU_ID_VALIDO, @P_ID_SESION, ? , ?)";
                                $query3 = "SELECT @P_USU_ID_VALIDO";
                                $query4 = "SELECT @P_ID_SESION";

                                try{
                                    $stmt2 = $this->conn->prepare($query2);
                                    $stmt2->bind_param("ss",$IP,$usu['USU_ID']);
                                    if(!$stmt2->execute()){
                                        $code_error = "error_crearSesion";
                                        $mensaje = "No se pudo crear la sesión.";
                                        return false;
                                    }
                                    //Obtiene la variable P_USU_ID_VALIDO (boolean)
                                    $stmt3 = $this->conn->prepare($query3);
                                    $stmt3->execute();
                                    $result3 = get_result($stmt3);    

                                    //Obtiene el ID de la ultima sesion registrada
                                    $stmt4 = $this->conn->prepare($query4);
                                    $stmt4->execute();
                                    $result4 = get_result($stmt4);  

                                    if (count($result3) > 0) {
                                        //obtenemos verdadero o falso dependiendo si es correcto el id del servicio
                                        $id_valido = array_shift($result3)["@P_USU_ID_VALIDO"];
                                    }
                                    if (count($result4) > 0) {
                                        //obtenemos verdadero o falso dependiendo si es correcto el id del servicio
                                        $id_sesion = array_shift($result4)["@P_ID_SESION"];
                                    }
                                    if(!$id_valido ){
                                        $code_error = 'error_noExistenciaDeId';
                                        $mensaje = 'No se encontró registro del ID de usuario';
                                        return false;
                                    }

                                }catch(Throwable  $e) {
                                    $code_error = "error_deBD";                
                                    $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";       
                                    return false;
                                }
                                $mensaje = "Se inicio sesion";
                            }else{
                                $code_error = "error_contraseniaInvalid";
                                $mensaje = "La contrasenia es incorrecta.";
                                return false;
                            }
                        }else{
                            $code_error = "error_userInvalid";
                            $mensaje = "El nombre de usuario/email es incorrecto.";
                            return false;
                        }
                    }catch(Throwable  $e) {                
                        $code_error = "error_deBD";                
                        $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";       
                        return false;   
                    }
                }
            }catch(Throwable  $e) {                
                $code_error = "error_deBD";                
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";       
                return false;   
            }
            return true;
        }

        function buscarAliasUsuario(&$tipo,$identificador, &$exito, &$code_error, &$mensaje){
            $query = "SELECT USU_USUARIO, ROL_ID FROM USUARIOS WHERE (USU_USUARIO=? OR USU_EMAIL=?);";
            $alias = '';
            try{
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("ss", $identificador, $identificador);
                $stmt->execute();
                $result = get_result($stmt); 
                
                if (count($result) > 0) {
                    $usu = array_shift($result);
                    $alias = $usu['USU_USUARIO'];
                    $tipo = $usu['ROL_ID'];
                    $mensaje = "Solicitud ejecutada con éxito.";
                    $exito = true;
                    return $alias;
                }else{
                    $code_error = "error_noExistenciaDeUsuario";
                    $exito = false;
                    $mensaje = "No existe un nickname para este usuario";
                    return $alias;
                }
                
            }catch(Throwable  $e){
                $exito = false;
                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";       
                return $alias;
            }
        }

        function obtenerIpUsuario() {
            $ipaddress = '';
            if (getenv('HTTP_CLIENT_IP'))
                $ipaddress = getenv('HTTP_CLIENT_IP');
            else if(getenv('HTTP_X_FORWARDED_FOR'))
                $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
            else if(getenv('HTTP_X_FORWARDED'))
                $ipaddress = getenv('HTTP_X_FORWARDED');
            else if(getenv('HTTP_FORWARDED_FOR'))
                $ipaddress = getenv('HTTP_FORWARDED_FOR');
            else if(getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
            else if(getenv('REMOTE_ADDR'))
                $ipaddress = getenv('REMOTE_ADDR');
            else
                $ipaddress = 'UNKNOWN';
            return $ipaddress;
        }

        function cerrarSesion(){
            
        }

        function crearUsuario(&$code_error,&$mensaje){
            $query = "INSERT INTO USUARIOS(USU_USUARIO,USU_CONTRASENIA,USU_NOMBRES,USU_APELLIDO_PATERNO,USU_APELLIDO_MATERNO,USU_SEXO,USU_DNI,USU_CELULAR,USU_DIRECCION,USU_EMAIL,USU_ESTADO,ROL_ID) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)";
            $query0 = "SELECT USU_ID FROM USUARIOS WHERE USU_EMAIL = ?";
            $query1 = "SELECT USU_ID FROM USUARIOS WHERE USU_USUARIO = ?";
            $query2 = "SELECT USU_ID FROM USUARIOS WHERE USU_DNI = ?";
            $query3 = "SELECT USU_ID FROM USUARIOS WHERE USU_CELULAR = ?";
            try{
                $stmt0 = $this->conn->prepare($query0);
                $stmt0->bind_param("s",$this->USU_EMAIL);
                $stmt0->execute();
                $result0 = get_result($stmt0);
                // echo json_encode(array("conteo"=>(array_shift($result0)['V_EMAIL'])));
                if(count($result0)>0){
                    $code_error = "error_emailExistente";
                    $mensaje = "El email ingresado ya pertenece a una cuenta.";  
                    return false;    
                }else{
                    $stmt1 = $this->conn->prepare($query1);
                    $stmt1->bind_param("s",$this->USU_USUARIO);
                    $stmt1->execute();
                    $result1 = get_result($stmt1);
                    if(count($result1)>0){
                        $code_error = "error_nombreUsuarioExistente";
                        $mensaje = "El nombre de usuario ingresado ya pertenece a una cuenta.";  
                        return false; 
                    }else{
                        $stmt2 = $this->conn->prepare($query2);
                        $stmt2->bind_param("s",$this->USU_DNI);
                        $stmt2->execute();
                        $result2 = get_result($stmt2);
                        if(count($result2)>0){
                            $code_error = "error_dniExistente";
                            $mensaje = "El DNI ingresado ya pertenece a una cuenta.";  
                            return false; 
                        }else{
                            $stmt3 = $this->conn->prepare($query3);
                            $stmt3->bind_param("s",$this->USU_CELULAR);
                            $stmt3->execute();
                            $result3 = get_result($stmt3);
                            if(count($result3)>0){
                                $code_error = "error_celularExistente";
                                $mensaje = "El celular ingresado ya pertenece a una cuenta.";  
                                return false;
                            }else{
                                $hash = password_hash($this->USU_CONTRASENIA,PASSWORD_DEFAULT);
                                $stmt = $this->conn->prepare($query);
                                $stmt->bind_param("ssssssssssss",$this->USU_USUARIO,$hash,$this->USU_NOMBRES,$this->USU_APELLIDO_PATERNO,$this->USU_APELLIDO_MATERNO,$this->USU_SEXO,$this->USU_DNI,$this->USU_CELULAR,$this->USU_DIRECCION,$this->USU_EMAIL,$this->USU_ESTADO,$this->ROL_ID);
                                if(!$stmt->execute()){
                                    $mensaje = "Hubo un error al registrar el usuario.";
                                    return false;
                                }
                                $mensaje = "Solicitud ejecutada con éxito.";
                                return true;
                            }
                        }
                    }
                }
                
            }catch(Throwable  $e){
                $code_error = "error_deBD";
                $mensaje = "Ha ocurrido un error con la BD. No se pudo ejecutar la consulta.";  
                return false;     
            }
        }

        function listarUsuarios(&$mensaje, &$exito, &$code_error){
            $query = "SELECT * FROM USUARIOS";
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