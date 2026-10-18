<?php
require_once 'config.php';
require_once 'vendor/autoload.php';

use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;

// Configurar credenciais do Mercado Pago
MercadoPagoConfig::setAccessToken("APP_USR-2690679815990593-093020-3748b2767377e642f7627e167b7984b1-1448885183");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ler dados do POST
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    $bolaoId = $data['bolaoId'];
    $participanteId = $data['participanteId'];
    $valor = floatval($data['valor']);
    $userId = $data['userId'];
    
    try {
        $client = new PaymentClient();
        
        // Criar pagamento no Mercado Pago
        $payment = $client->create([
            "transaction_amount" => $valor,
            "description" => "Aposta BetMax - Bolão #" . $bolaoId,
            "payment_method_id" => "pix",
            "payer" => [
                "email" => "cliente@betmax.com",
                "first_name" => "Cliente",
                "last_name" => "BetMax"
            ]
        ]);
        
        // Salvar dados do pagamento na tabela pagamentos
        $stmt = $pdo->prepare("
    INSERT INTO pagamentos 
    (usuario_id, bolao_id, participante_id, equipe_id, valor, payment_id, status, pix_copia_cola, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
");
$stmt->execute([
    $userId,
    $bolaoId,
    $participanteId,
    $data['equipeId'],
    $valor,
    $payment->id,
    $payment->point_of_interaction->transaction_data->qr_code
]);
        
        // Preparar resposta
        $response = [
            'success' => true,
            'payment_id' => $payment->id,
            'pix_copia_cola' => $payment->point_of_interaction->transaction_data->qr_code,
            'transaction_amount' => $payment->transaction_amount
        ];
        
        // Adicionar QR code se disponível
        if (isset($payment->point_of_interaction->transaction_data->qr_code_base64)) {
            $response['qr_code'] = "data:image/png;base64," . $payment->point_of_interaction->transaction_data->qr_code_base64;
        }
        
        echo json_encode($response);
        
    } catch (MPApiException $e) {
        error_log("Erro Mercado Pago: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao processar pagamento. Tente novamente.'
        ]);
    } catch (Exception $e) {
        error_log("Erro geral: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Erro interno do sistema. Tente novamente.'
        ]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
}
?>
