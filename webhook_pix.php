<?php
require 'config.php';
require 'vendor/autoload.php';

use MercadoPago\SDK;
use MercadoPago\Payment;

SDK::setAccessToken('APP_USR-2690679815990593-093020-3748b2767377e642f7627e167b7984b1-1448885183');');

// Recebe notificação do MercadoPago
$input = json_decode(file_get_contents('php://input'), true);
$paymentId = $input['id'] ?? null;

if (!$paymentId){
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Payment ID ausente']);
    exit;
}

try {
    // Recupera pagamento
    $payment = Payment::find_by_id($paymentId);

    if (!$payment){
        throw new Exception("Pagamento não encontrado no MercadoPago");
    }

    // Determina status
    $status = 'pendente';
    if ($payment->status === 'approved') $status = 'confirmado';
    if (in_array($payment->status, ['cancelled', 'rejected'])) $status = 'cancelado';

    // Atualiza aposta no banco
    $stmt = $pdo->prepare("UPDATE apostas SET status = ? WHERE payment_id = ?");
    $stmt->execute([$status, $paymentId]);

    // Se confirmado, atualiza prêmio do bolão
    if ($status === 'confirmado'){
        $stmt = $pdo->prepare("SELECT boolao_id, valor FROM apostas WHERE payment_id = ?");
        $stmt->execute([$paymentId]);
        $aposta = $stmt->fetch(PDO::FETCH_ASSOC);

        if($aposta){
            $premio_incremento = $aposta['valor'] * 0.9; // 90%
            $stmt = $pdo->prepare("UPDATE boloes SET premio = premio + ? WHERE id = ?");
            $stmt->execute([$premio_incremento, $aposta['boolao_id']]);
        }
    }

    echo json_encode(['success' => true, 'status' => $status]);

} catch(Exception $e){
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
