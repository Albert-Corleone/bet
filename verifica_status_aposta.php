<?php
require 'config.php'; // conexão PDO

// Recebe payment_id via GET ou POST
$payment_id = $_GET['payment_id'] ?? null;

if (!$payment_id){
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Payment ID ausente']);
    exit;
}

try {
    // Recupera status da aposta
    $stmt = $pdo->prepare("
        SELECT a.status, a.valor, b.premio 
        FROM apostas a
        JOIN boloes b ON a.boolao_id = b.id
        WHERE a.payment_id = ?
    ");
    $stmt->execute([$payment_id]);
    $aposta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$aposta){
        echo json_encode(['success' => false, 'error' => 'Aposta não encontrada']);
        exit;
    }

    // Retorna status atual e valor do prêmio
    echo json_encode([
        'success' => true,
        'status'  => $aposta['status'],
        'premio'  => number_format($aposta['premio'], 2, ',', '.')
    ]);

} catch(Exception $e){
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
