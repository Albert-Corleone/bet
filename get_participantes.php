<?php
require 'config.php';

$bolaoId = $_GET['boolao_id'] ?? 0;
$tipo = $_GET['tipo'] ?? '';

// Se for bol���o de pombos
if ($tipo === 'pombo') {
    $stmt = $pdo->prepare("SELECT id, nome FROM pombos WHERE boolao_id = ?");
} else {
    $stmt = $pdo->prepare("SELECT id, nome FROM equipes WHERE boolao_id = ?");
}

$stmt->execute([$bolaoId]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
