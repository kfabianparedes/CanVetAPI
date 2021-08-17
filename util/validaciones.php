<?php

    function obtenerCantidadDeCaracteres( $datos ) {
            
        $cadena = str_split($datos);
        $tam = count($cadena);

        return $tam;
    }

?>