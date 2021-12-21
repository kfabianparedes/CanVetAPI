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
                // if($i==0)
                // {
                //     if(ord($cadena[$i])!=43 ) //43 es el codigo ASCII para "+"
                //         return false;
                // }
                // else{
                if(ord($cadena[$i])<48 || ord($cadena[$i])>57)
                    return false;
                // }
                
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

    function calcularedad($fechanacimiento){  // Función que calcula la edad.
        $fecha_nacimiento = new DateTime($fechanacimiento);
        $hoy = new DateTime();
        $edad = $hoy->diff($fecha_nacimiento);

        return intval($edad->y);
    }

    function verificarFecha($fecha){ // Formato : año-mes-día.
        $valores = explode('-', $fecha);
        
        if(count($valores) == 3 && checkdate($valores[1], $valores[2], $valores[0])){
            return true;
        }

        return false;
    }

    function esMenorFechaActual($fechaIngresada){

        $fecha_ingresada = new DateTime($fechaIngresada);
        $hoy = new DateTime();

        return $fecha_ingresada < $hoy; 
    }

    function esIgualFechaActual($fechaIngresada){

        $fecha_ingresada = new DateTime($fechaIngresada);
        $hoy = new DateTime();

        return $fecha_ingresada = $hoy; 
    }
?>