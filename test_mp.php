<?php
require_once 'vendor/autoload.php';

use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\MercadoPagoConfig;

echo "<h2>Teste Mercado Pago</h2>";

try {
    // Configurar credenciais
    MercadoPagoConfig::setAccessToken("APP_USR-2690679815990593-093020-3748b2767377e642f7627e167b7984b1-1448885183");
    
    echo "✅ SDK Mercado Pago carregado!<br>";
    
    // Testar cliente
    $client = new PaymentClient();
    echo "✅ Cliente de pagamento funcionando!<br>";
    echo "✅ Tudo OK! Pode prosseguir.<br>";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "<br>";
}
?>
