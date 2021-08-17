<?php
    error_reporting(E_ALL ^ E_WARNING);
    date_default_timezone_set('America/Lima');

    class Database{
    
        //Database credentials
        private $host = "btymmzkjpr9zdpc2cgww-mysql.services.clever-cloud.com";
        private $db_name = "rolando2_MilOficiosDev";
        private $username = "u03ihtda1vnbkyaj";
        private $password = "t39kuv6JvphhwOvRJ00n";
        public $conn;

        //Constructor
        public function __construct()
        {
            try{
                // Create connection
                $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);          
                $this->conn->set_charset("utf8");
            }catch(Throwable  $e){
                echo "Connection error: " . $e->getMessage();        
            }        
        }
    
        //Get the database connection
        public function getConnection(){    
            return $this->conn;
        }        
    }
?>