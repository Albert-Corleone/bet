<?php
require_once 'config.php';
require_once 'vendor/autoload.php';

use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\MercadoPagoConfig;

MercadoPagoConfig::setAccessToken("APP_USR-2690679815990593-093020-3748b2767377e642f7627e167b7984b1-1448885183");

header('Content-Type: application/json');

$paymentId = $_GET['payment_id'];

try {
    $client = new PaymentClient();
    $payment = $client->get($paymentId);
    
    // Atualizar status no banco
    $stmt = $pdo->prepare("UPDATE pagamentos SET status = ? WHERE payment_id = ?");
    $stmt->execute([$payment->status, $paymentId]);
    
    // Se pagamento aprovado, registrar aposta
    if ($payment->status === 'approved') {
        $stmt = $pdo->prepare("SELECT * FROM pagamentos WHERE payment_id = ?");
        $stmt->execute([$paymentId]);
        $pagamento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pagamento) {
            // Verificar se aposta já foi registrada
            $stmt = $pdo->prepare("SELECT id FROM apostas WHERE usuario_id = ? AND boolao_id = ? AND participante_id = ? AND payment_id = ?");
            $stmt->execute([
                $pagamento['usuario_id'], 
                $pagamento['bolao_id'], 
                $pagamento['participante_id'], 
                $paymentId
            ]);
            
            if (!$stmt->fetch()) {
                // Registrar aposta na tabela apostas
$stmt = $pdo->prepare("
    INSERT INTO apostas 
    (usuario_id, boolao_id, participante_id, equipe_id, valor, payment_id, status, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, 'ativa', NOW())
");
$stmt->execute([
    $pagamento['usuario_id'], 
    $pagamento['bolao_id'], 
    $pagamento['participante_id'], 
    $pagamento['equipe_id'], // ✅ agora incluímos o ID da equipe
    $pagamento['valor'],
    $paymentId
]);
                
                // ✅✅✅ ATUALIZAR BOLÃO (FALTANDO NO CÓDIGO 1) ✅✅✅
                $valor_premio = $pagamento['valor'] * 0.9;
                $stmt = $pdo->prepare("UPDATE boloes SET premio = premio + ?, total_apostas = total_apostas + 1 WHERE id = ?");
                $stmt->execute([$valor_premio, $pagamento['bolao_id']]);
                
                echo json_encode([
                    'status' => 'approved', 
                    'message' => 'Aposta registrada com sucesso!'
                ]);
                exit;
            }
        }
    }
    
    echo json_encode(['status' => $payment->status]);
    
} catch (Exception $e) {
    error_log("Erro ao verificar pagamento: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>