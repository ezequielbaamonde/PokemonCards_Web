<?php
require_once __DIR__ . '/../config/db_Connect.php';

function determinarResultado(array $cartaJugador, array $cartaServidor): array
{
    $ventajas = [
        [1, 3],
        [1, 6],
        [2, 1],
        [3, 6],
        [5, 4],
        [6, 2],
        [7, 3],
        [7, 6],
        [7, 2],
        [1, 7],
        [5, 7]
    ];

    $fuerzaJugador = $cartaJugador['ataque'];
    $atributoJugador = $cartaJugador['atributo_id'];

    $fuerzaServidor = $cartaServidor['ataque'];
    $atributoServidor = $cartaServidor['atributo_id'];

    $tieneVentajaJugador = false;
    $tieneVentajaServidor = false;

    foreach ($ventajas as [$atacante, $defensor]) {
        if ($atributoJugador == $atacante && $atributoServidor == $defensor) {
            $tieneVentajaJugador = true;
            break;
        }
        if ($atributoServidor == $atacante && $atributoJugador == $defensor) {
            $tieneVentajaServidor = true;
            break;
        }
    }

    if ($tieneVentajaJugador) {
        $fuerzaJugador *= 1.3;
    }
    if ($tieneVentajaServidor) {
        $fuerzaServidor *= 1.3;
    }

    if ($fuerzaJugador > $fuerzaServidor) {
        $resultado = "gano";
    } elseif ($fuerzaJugador < $fuerzaServidor) {
        $resultado = "perdio";
    } else {
        $resultado = "empato";
    }

    return [
        'resultado' => $resultado,
        'fuerza_jugador' => round($fuerzaJugador, 2),
        'fuerza_servidor' => round($fuerzaServidor, 2)
    ];
}