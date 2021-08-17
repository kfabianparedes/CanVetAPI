<?php
    include_once '../util/mysqlnd.php';

    class Usuario{
        private $conn;

        public $USU_ID;
        public $USU_TIPO; //administrador(1) y empleado(0)
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

        public function __construct($db){
            $this->conn = $db;
        }

        function login($IP ,$usu_tipo , &$usu_id, &$usu_nombres, &$usu_hash, &$usu_estado, &$usu_email, &$mensaje, &$exito){
            //Consulta
            $query="SELECT USU_ID, USU_CONTRASENIA, USU_ESTADO, USU_EMAIL, CONCAT(USU_NOMBRES, ' ', USU_APELLIDO_PATERNO, ' ', USU_APELLIDO_MATERNO) AS NOMBRE_COMPLETO FROM USUARIOS WHERE USU_USUARIO = ? AND USU_TIPO = ? "; 
            
            try{
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("ss",$this->USU_USUARIO,$usu_tipo);

                $stmt->execute();

                $result = get_result($stmt); 

                if(count($result)>0){ 
                    $usu = array_shift($result);
                    if(password_verify($this->USU_CONTRASENIA, $usu['USU_CONTRASENIA'])){//el primer parametro es la contraseña sin hash y el segundo parametro es la contraseña hasheada
                        $usu_hash = $usu['USU_CONTRASENIA'];
                        $usu_id = $usu['USU_ID'];
                        $usu_nombres = $usu['NOMBRE_COMPLETO'];
                        $usu_estado = $usu['USU_ESTADO'];
                        $usu_email = $usu['USU_EMAIL'];
                        $query2 ="INSERT INTO SESION(SES_FECHA_INI,SES_RASTREO,USU_ID)VALUES(NOW2(),?,?)";
                        
                        try{
                            $stmt = $this->conn->prepare($query2);
                            $stmt->bind_param("ss",$IP,$usu['USU_ID']);
                            $stmt->execute();

                            $mensaje = "Se inicio sesion";
                            $exito = true;
                        }catch(Throwable  $e) {                
                            echo "Connection error: " . $e->getMessage();        
                        }
                    }else{
                        $mensaje = "La contrasenia es incorrecta.";
                        $exito = false;
                    }
                }else{
                    $mensaje = "El nombre de usuario/email es incorrecto.";
                    $exito = false;
                }
            }catch(Throwable  $e) {                
                echo "Connection error: " . $e->getMessage();        
            }
        }

        function buscarAliasUsuario($identificador, &$exito, &$code_error, &$mensaje){
            $query = "SELECT USU_USUARIO FROM USUARIOS WHERE (USU_USUARIO=? OR USU_EMAIL=?);";
            $alias = '';
            try{
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("ss", $identificador, $identificador);
                $stmt->execute();
                $result = get_result($stmt); 
                
                if (count($result) > 0) {
                    $alias = array_shift($result)['USU_USUARIO'];
                    $exito = true;
                    $mensaje = "Solicitud ejecutada con exito";
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