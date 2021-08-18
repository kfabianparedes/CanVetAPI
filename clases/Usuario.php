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

        function login($IP ,&$usu_id, &$usu_nombres, &$usu_hash, &$usu_estado, &$usu_email, &$mensaje ,&$code_error){
            //Consulta
            $query0 = "SELECT COUNT(*) AS ID_ROL FROM ROL WHERE ROL_ID = ?";
            $query = "SELECT USU_ID, USU_CONTRASENIA, USU_ESTADO, USU_EMAIL, CONCAT(USU_NOMBRES, ' ', USU_APELLIDO_PATERNO, ' ', USU_APELLIDO_MATERNO) AS NOMBRE_COMPLETO FROM USUARIOS WHERE USU_USUARIO = ? AND ROL_ID = ? "; 
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
                        $usu = array_shift($result1);
                        if(count($usu)>0){ 
                            if($this->USU_CONTRASENIA === $usu['USU_CONTRASENIA']){//if(password_verify($this->USU_CONTRASENIA, $usu['USU_CONTRASENIA'])){//el primer parametro es la contraseña sin hash y el segundo parametro es la contraseña hasheada
                                $usu_hash = $usu['USU_CONTRASENIA'];
                                $usu_id = $usu['USU_ID'];
                                $usu_nombres = $usu['NOMBRE_COMPLETO'];
                                $usu_estado = $usu['USU_ESTADO'];
                                $usu_email = $usu['USU_EMAIL'];
                                $query2 ="INSERT INTO SESION(SES_FECHA_INI,SES_RASTREO,USU_ID) VALUES(DATE_SUB(NOW(), INTERVAL 5 HOUR),?,?)";
                                
                                try{
                                    $stmt = $this->conn->prepare($query2);
                                    $stmt->bind_param("ss",$IP,$usu['USU_ID']);
                                    if(!$stmt->execute()){
                                        $code_error = "error_crearSesion";
                                        $mensaje = "No se pudo crear la sesión.";
                                        return false;
                                    }
                                    $mensaje = "Se inicio sesion";
                                }catch(Throwable  $e) {
                                    $code_error = "error_deBD";                
                                    $mensaje = "Connection error: " . $e->getMessage();        
                                    return false;
                                }   
                            }else{
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
                        $mensaje = "Connection error: " . $e->getMessage();        
                        return false;   
                    }
                }
            }catch(Throwable  $e) {                
                $code_error = "error_deBD";                
                $mensaje = "Connection error: " . $e->getMessage();        
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
                $mensaje = "Connection error: " . $e->getMessage();
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



    }

?>