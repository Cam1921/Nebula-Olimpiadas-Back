<?php

if (!function_exists('separarNombreCompleto')) {
    /**
     * Separa un nombre completo en nombres y apellidos.
     *
     * @param string $nombreCompleto
     * @return array ['nombres' => string, 'apellidos' => string]
     */
    function separarNombreCompleto(string $nombreCompleto): array
    {
        $nombreCompleto = trim($nombreCompleto);
        $partes = preg_split('/\s+/', $nombreCompleto); // divide por múltiples espacios

        if (count($partes) >= 3) {
            // Ejemplo: "Juan Carlos Pérez López"
            $nombres = $partes[0] . ' ' . $partes[1];
            $apellidos = implode(" ", array_slice($partes, 2));
        } else {
            // Ejemplo: "Juan Pérez"
            $nombres = $partes[0] ?? '';
            $apellidos = $partes[1] ?? '';
        }

        return [
            'nombres' => $nombres,
            'apellidos' => $apellidos
        ];
    }
}

if (!function_exists('unirNombreCompleto')) {
    /**
     * Une nombres y apellidos en un solo string (nombre completo).
     *
     * @param string $nombres
     * @param string $apellidos
     * @return string
     */
    function unirNombreCompleto(string $nombres, string $apellidos): string
    {
        $nombres = trim($nombres);
        $apellidos = trim($apellidos);

        return trim($nombres . ' ' . $apellidos);
    }
}
