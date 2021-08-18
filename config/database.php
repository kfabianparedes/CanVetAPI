<?php
    error_reporting(E_ALL ^ E_WARNING);
    date_default_timezone_set('America/Lima');

    class Database{
    
        //Database credentials
        private $host = "213.190.6.85";
        private $db_name = "u507536466_CanVet";
        private $username = "u507536466_SolucionesMK";
        private $password = "solucionesMK_2021";
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