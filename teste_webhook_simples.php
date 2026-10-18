<?php
// teste_webhook_simples.php
echo "Testando webhook...<br>";

$url = "https://seudominio.com.br/webhook_pagamento.php";

// Dados de teste
$dados = [
    'data' => [
        'id' => '128176365983'
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "<br>";
echo "Erro: " . $error . "<br>";
echo "Resultado: " . $result . "<br>";

// Verificar se o arquivo de debug foi criado
if (file_exists('webhook_debug.txt')) {
    echo "<br>=== DEBUG LOG ===<br>";
    echo nl2br(file_get_contents('webhook_debug.txt'));
} else {
    echo "<br>Arquivo debug não criado - verifique permissões";
}
?>