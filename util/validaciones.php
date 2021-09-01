<?php

    function obtenerCantidadDeCaracteres( $datos ) {
            
        $cadena = str_split($datos);
        $tam = count($cadena);

        return $tam;
    }
    function verificarCelular($celular) {
        $cadena = str_split($celular);
        $tam = count($cadena);
        if($tam > 1) {      // Debe tener al menos el signo mas ('+') y un número
            for($i=0; $i<$tam; $i++)
            {
                if($i==0)
                {
                    if(ord($cadena[$i])!=43 ) //43 es el codigo ASCII para "+"
                        return false;
                }
                else{
                    if(ord($cadena[$i])<48 || ord($cadena[$i])>57)
                    return false;
                }
                
            }
        }
        else {
            return false;
        }
        return true;
    }

    function esTextoAlfabetico($texto) {
        return preg_match("/^[a-zA-Z ñÑáéíóúÁÉÍÓÚ]+$/i", $texto);
    }
?>