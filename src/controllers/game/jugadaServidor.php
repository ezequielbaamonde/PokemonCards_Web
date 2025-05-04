<?php
require_once __DIR__ . '/../../config/db_Connect.php';

function jugadaServidor(): int
{
    $db = DB::getConnection();
    $idServidor = 1;

    // Buscar cartas 'en_mano' del servidor
    $stmt = $db->prepare("
        SELECT mc.carta_id
        FROM mazo_carta mc
        JOIN mazo m ON mc.mazo_id = m.id
        WHERE m.usuario_id = :idServidor
          AND mc.estado = 'en_mano'
    ");
    $stmt->bindParam(':idServidor', $idServidor);
    $stmt->execute();
    $cartasDisponibles = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($cartasDisponibles)) {
        throw new Exception('No hay cartas disponibles para el servidor');
    }

    $idCartaSeleccionada = $cartasDisponibles[array_rand($cartasDisponibles)];

    // DESCARTAR solo la carta del mazo del servidor
    $stmt = $db->prepare("
        UPDATE mazo_carta 
        SET estado = 'descartado' 
        WHERE carta_id = :idCartaServidor 
        AND mazo_id IN (SELECT id FROM mazo WHERE usuario_id = :idServidor)
    ");
    $stmt->bindParam(':idCartaServidor', $idCartaSeleccionada);
    $stmt->bindParam(':idServidor', $idServidor);
    $stmt->execute();

    return (int) $idCartaSeleccionada;
}