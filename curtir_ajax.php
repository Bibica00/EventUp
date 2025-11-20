<?php
require_once "funct.php";

if (session_status() === PHP_SESSION_NONE) session_start();

// Garante que o retorno será sempre JSON válido
header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_SESSION['email'])) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Usuário não logado']);
        exit;
    }

    $emailUsuario = $_SESSION['email'];
    $id_evento = $_POST['id_evento'] ?? null;

    if (!$id_evento || !is_numeric($id_evento)) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'ID inválido']);
        exit;
    }

    // Define o tipo de usuário — por padrão 'usuario'
    $tipoUsuario = $_SESSION['tipo_usuario'] ?? 'usuario';

    // Verifica se o registro já existe
    $stmt = $pdo->prepare("
        SELECT id 
        FROM curtidas 
        WHERE id_evento = :id_evento 
          AND usuario = :usuario 
          AND tipo_usuario = :tipo
    ");
    $stmt->execute([
        ':id_evento' => $id_evento,
        ':usuario' => $emailUsuario,
        ':tipo' => $tipoUsuario
    ]);
    $curtiu = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($curtiu) {
        // Se já curtiu → remover curtida
        $pdo->prepare("
            DELETE FROM curtidas 
            WHERE id_evento = :id_evento 
              AND usuario = :usuario 
              AND tipo_usuario = :tipo
        ")->execute([
            ':id_evento' => $id_evento,
            ':usuario' => $emailUsuario,
            ':tipo' => $tipoUsuario
        ]);

        echo json_encode(['status' => 'descurtido']);
    } else {
        // Caso contrário → adicionar curtida
        $pdo->prepare("
            INSERT INTO curtidas (id_evento, usuario, tipo_usuario) 
            VALUES (:id_evento, :usuario, :tipo)
        ")->execute([
            ':id_evento' => $id_evento,
            ':usuario' => $emailUsuario,
            ':tipo' => $tipoUsuario
        ]);

        echo json_encode(['status' => 'curtido']);
    }
} catch (Throwable $e) {
    // Garante que qualquer erro ainda retorna JSON válido
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Erro interno: ' . $e->getMessage()
    ]);
}
?>
