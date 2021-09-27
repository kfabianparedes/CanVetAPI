<?php
    class Autorizacion{
        public $USE_SUB; // TOKEN ENVIADO DEL FRONTEND
        public $TYPE_USER; // 
        public $FIRST_HEADER;
        public $SECOND_HEADER;
        public $HEADER_COUNT;
        public $AUTH_ADM;
        public $AUTH_EMP;
        public $ROL;

        public function __construct(){
            $this->USE_SUB = '';
            $this->TYPE_USER = '';
            $this->FIRST_HEADER = 'authorization';
            $this->SECOND_HEADER = 'user';
            $this->HEADER_COUNT = 0;
            $this->AUTH_ADM = 'dmMLAeOtrn';
            $this->AUTH_EMP = 'me2Ia1NMer';
            $this->ROL = 0;
        }
    }
?>