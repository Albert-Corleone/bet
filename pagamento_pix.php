<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento PIX - BetMax</title>
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            max-width: 500px;
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            color: white;
        }

        .logo {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(to right, #fdbb2d, #ff6b00);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .subtitle {
            font-size: 1rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .payment-card {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .payment-title {
            font-size: 1.2rem;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .qr-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            display: inline-block;
            margin: 15px 0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .qr-code {
            width: 200px;
            height: 200px;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto;
            position: relative;
            overflow: hidden;
        }

        .qr-code img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .pix-key-container {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pix-key {
            font-family: monospace;
            font-size: 0.9rem;
            word-break: break-all;
            text-align: left;
            flex: 1;
            padding-right: 10px;
        }

        .copy-btn {
            background: linear-gradient(to right, #ff6b00, #ff3c00);
            border: none;
            border-radius: 8px;
            color: white;
            padding: 10px 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(255, 107, 0, 0.3);
        }

        .copy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(255, 107, 0, 0.4);
        }

        .copy-btn:active {
            transform: translateY(0);
        }

        .copy-btn.copied {
            background: linear-gradient(to right, #4CAF50, #45a049);
        }

        .instructions {
            font-size: 0.85rem;
            margin-top: 25px;
            opacity: 0.8;
            line-height: 1.5;
        }

        .betmax-badge {
            margin-top: 20px;
            font-size: 0.8rem;
            opacity: 0.7;
        }

        .payment-status {
            margin-top: 20px;
            padding: 15px;
            border-radius: 10px;
            font-weight: 600;
            display: none;
        }

        .payment-status.success {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.5);
            display: block;
        }

        .payment-status.pending {
            background: rgba(255, 193, 7, 0.2);
            border: 1px solid rgba(255, 193, 7, 0.5);
            display: block;
        }

        .payment-status.error {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid rgba(244, 67, 54, 0.5);
            display: block;
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(255, 107, 0, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(255, 107, 0, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(255, 107, 0, 0);
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 20px;
            }
            
            .logo {
                font-size: 2rem;
            }
            
            .qr-code {
                width: 180px;
                height: 180px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="logo">BetMax</h1>
        <p class="subtitle">Pagamento via PIX</p>
        
        <div class="payment-card">
            <h2 class="payment-title">Escaneie o QR code abaixo no app do seu banco</h2>
            <div class="qr-container pulse">
                <div class="qr-code" id="qrCodeContainer">
                    <div id="loadingQr">Carregando QR Code...</div>
                </div>
            </div>
        </div>
        
        <div class="payment-card">
            <h2 class="payment-title">Ou PIX copie e cole</h2>
            <div class="pix-key-container">
                <div class="pix-key" id="pixKey">Carregando chave PIX...</div>
                <button class="copy-btn" id="copyButton">Copiar</button>
            </div>
        </div>
        
        <div class="payment-status pending" id="paymentStatus">
            Aguardando confirmação do pagamento...
        </div>
        
        <p class="instructions">
            Após o pagamento, sua aposta será confirmada automaticamente em até 5 minutos.
        </p>
        
        <div class="betmax-badge">
            Pagamento seguro processado pela BetMax
        </div>
    </div>

    <script>
        // Dados da aposta (agora usando PHP para pegar os dados com segurança)
        const apostaData = {
    bolaoId: <?php echo isset($_GET['bolao_id']) ? json_encode($_GET['bolao_id']) : 'null'; ?>,
    participanteId: <?php echo isset($_GET['participante_id']) ? json_encode($_GET['participante_id']) : 'null'; ?>,
    valor: <?php echo isset($_GET['valor']) ? json_encode($_GET['valor']) : 'null'; ?>,
    userId: <?php echo isset($_GET['user_id']) ? json_encode($_GET['user_id']) : 'null'; ?>,
    equipeId: <?php echo isset($_GET['equipe_id']) ? json_encode($_GET['equipe_id']) : 'null'; ?> // 👈 ADICIONE ESTA LINHA
};

        // Elementos da página
        const qrCodeContainer = document.getElementById('qrCodeContainer');
        const pixKeyElement = document.getElementById('pixKey');
        const copyButton = document.getElementById('copyButton');
        const paymentStatus = document.getElementById('paymentStatus');
        let paymentId = null;
        let checkInterval = null;

        // Inicializar página de pagamento
        document.addEventListener('DOMContentLoaded', function() {
            if (!apostaData.bolaoId || !apostaData.participanteId || !apostaData.valor || !apostaData.userId) {
                showError('Dados da aposta incompletos. Por favor, volte e tente novamente.');
                return;
            }

            iniciarPagamento();
        });

        // Função para iniciar o processo de pagamento
        async function iniciarPagamento() {
            try {
                const response = await fetch('gerar_pix.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(apostaData)
                });

                const data = await response.json();

                if (data.success) {
                    // Exibir QR Code
                    if (data.qr_code) {
                        qrCodeContainer.innerHTML = `<img src="${data.qr_code}" alt="QR Code PIX">`;
                    } else {
                        qrCodeContainer.innerHTML = '<p>QR Code não disponível</p>';
                    }

                    // Exibir chave PIX
                    pixKeyElement.textContent = data.pix_copia_cola;
                    paymentId = data.payment_id;

                    // Iniciar verificação de status
                    iniciarVerificacaoStatus();

                } else {
                    showError('Erro ao gerar pagamento: ' + data.error);
                }
            } catch (error) {
                showError('Erro de conexão: ' + error.message);
            }
        }

        // Função para verificar status do pagamento
        async function verificarStatusPagamento() {
            if (!paymentId) return;

            try {
                const response = await fetch(`verificar_pagamento.php?payment_id=${paymentId}`);
                const data = await response.json();

                if (data.status === 'approved') {
                    showSuccess('Pagamento confirmado! Sua aposta foi registrada com sucesso.');
                    clearInterval(checkInterval);
                    
                    // Redirecionar após alguns segundos
                    setTimeout(() => {
                        window.location.href = 'interface.php?aposta_sucesso=true';
                    }, 3000);
                } else if (data.status === 'rejected' || data.status === 'cancelled') {
                    showError('Pagamento não aprovado. Tente novamente.');
                    clearInterval(checkInterval);
                }
                // Se ainda estiver pendente, continua verificando
            } catch (error) {
                console.error('Erro ao verificar pagamento:', error);
            }
        }

        // Iniciar verificação periódica
        function iniciarVerificacaoStatus() {
            checkInterval = setInterval(verificarStatusPagamento, 5000); // Verifica a cada 5 segundos
        }

        // Função para copiar chave PIX
        copyButton.addEventListener('click', function() {
            const pixKey = pixKeyElement.textContent;
            
            navigator.clipboard.writeText(pixKey).then(() => {
                const originalText = this.textContent;
                this.textContent = 'Copiado!';
                this.classList.add('copied');
                
                setTimeout(() => {
                    this.textContent = originalText;
                    this.classList.remove('copied');
                }, 2000);
            }).catch(err => {
                console.error('Erro ao copiar texto: ', err);
                alert('Não foi possível copiar a chave PIX. Tente selecionar e copiar manualmente.');
            });
        });

        // Funções auxiliares para mostrar status
        function showSuccess(message) {
            paymentStatus.textContent = message;
            paymentStatus.className = 'payment-status success';
        }

        function showError(message) {
            paymentStatus.textContent = message;
            paymentStatus.className = 'payment-status error';
        }

        // Verificar se veio com sucesso
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('success') === 'true') {
            showSuccess('Pagamento confirmado! Sua aposta foi registrada.');
        }
    </script>
</body>
</html>