<?php
require_once __DIR__ . '/../config/db_Connect.php';

function determinarResultado(array $cartaJugador, array $cartaServidor): array
{
    $db = DB::getConnection();

    $fuerzaJugador = $cartaJugador['ataque'];
    $atributoJugador = $cartaJugador['atributo_id'];

    $fuerzaServidor = $cartaServidor['ataque'];
    $atributoServidor = $cartaServidor['atributo_id'];

    // Consultar si el atributo del jugador vence al del servidor
    $stmt = $db->prepare("SELECT 1 FROM gana_a WHERE atributo_id = :jugador AND atributo_id2 = :servidor LIMIT 1");
    $stmt->execute([
        ':jugador' => $atributoJugador,
        ':servidor' => $atributoServidor
    ]);
    $tieneVentajaJugador = $stmt->fetchColumn() !== false; // true si el jugador tiene ventaja

    // Consultar si el atributo del servidor vence al del jugador
    $stmt = $db->prepare("SELECT 1 FROM gana_a WHERE atributo_id = :servidor AND atributo_id2 = :jugador LIMIT 1");
    $stmt->execute([
        ':servidor' => $atributoServidor,
        ':jugador' => $atributoJugador
    ]);
    $tieneVentajaServidor = $stmt->fetchColumn() !== false; // true si el servidor tiene ventaja

    // Aplicar bonus de ventaja
    if ($tieneVentajaJugador) {
        $fuerzaJugador *= 1.3;
    }
    if ($tieneVentajaServidor) {
        $fuerzaServidor *= 1.3;
    }

    // Determinar resultado final
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