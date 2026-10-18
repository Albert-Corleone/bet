<?php
require_once 'config.php';

// --- Funções utilitárias para validação/normalização de PIX ---
function onlyDigits($s) {
    return preg_replace('/\D/', '', (string)$s);
}

function validarCPF($cpf) {
    $cpf = onlyDigits($cpf);
    if (strlen($cpf) != 11) return false;
    if (preg_match('/^(.)\1+$/', $cpf)) return false; // seq repetida
    for ($t = 9; $t < 11; $t++) {
        $sum = 0;
        for ($i = 0; $i < $t; $i++) {
            $sum += intval($cpf[$i]) * (($t + 1) - $i);
        }
        $rest = ($sum * 10) % 11;
        if ($rest == 10) $rest = 0;
        if ($cpf[$t] != $rest) return false;
    }
    return true;
}

function validarCNPJ($cnpj) {
    $cnpj = onlyDigits($cnpj);
    if (strlen($cnpj) != 14) return false;
    if (preg_match('/^(.)\1+$/', $cnpj)) return false;

    $calc = function($cnpj, $tamanho) {
        $nums = $tamanho == 12 ? array(5,4,3,2,9,8,7,6,5,4,3,2) : array(6,5,4,3,2,9,8,7,6,5,4,3,2);
        $sum = 0;
        for ($i = 0; $i < count($nums); $i++) {
            $sum += intval($cnpj[$i]) * $nums[$i];
        }
        $rest = $sum % 11;
        return ($rest < 2) ? 0 : 11 - $rest;
    };

    $d1 = $calc(substr($cnpj, 0, 12), 12);
    if ($d1 != intval($cnpj[12])) return false;
    $d2 = $calc(substr($cnpj, 0, 13), 13);
    if ($d2 != intval($cnpj[13])) return false;
    return true;
}

function validarPix($tipo, $valor) {
    $valor = trim((string)$valor);
    if ($valor === '') return false;

    switch ($tipo) {
        case 'email':
            return filter_var($valor, FILTER_VALIDATE_EMAIL) !== false;

        case 'celular':
            $d = onlyDigits($valor);
            // Remove prefix country code '55' se existir
            if (preg_match('/^55/', $d)) $d = preg_replace('/^55/', '', $d);
            // aceitar 10 (fixo) ou 11 (celular com 9) dígitos
            return (strlen($d) === 10 || strlen($d) === 11);

        case 'cpf':
            return validarCPF($valor);

        case 'cnpj':
            return validarCNPJ($valor);

        case 'aleatoria':
            // chave aleatória: aceitar letras/números e alguns símbolos, tamanho típico até 36
            $len = mb_strlen($valor);
            return ($len >= 6 && $len <= 64);

        default:
            return false;
    }
}

function normalizePix($tipo, $valor) {
    $valor = trim((string)$valor);
    if ($valor === '') return null;
    switch ($tipo) {
        case 'email':
            return mb_strtolower($valor);
        case 'celular':
            $d = onlyDigits($valor);
            if (preg_match('/^55/', $d)) $d = preg_replace('/^55/', '', $d);
            return $d; // só dígitos (ex: 11999998888)
        case 'cpf':
        case 'cnpj':
            return onlyDigits($valor);
        case 'aleatoria':
            return $valor;
        default:
            return $valor;
    }
}

// --- Processar cadastro ---
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nome = trim($_POST['nome']);
        $email = trim($_POST['email']);
        $telefone = trim($_POST['telefone']);
        $senha = $_POST['senha'] ?? '';
        $confirmar_senha = $_POST['confirmar_senha'] ?? '';
        $pix_tipo = isset($_POST['pix_tipo']) ? $_POST['pix_tipo'] : null;
        $chave_pix_raw = isset($_POST['chave_pix']) ? trim($_POST['chave_pix']) : '';

        // Validar dados obrigatórios
        if (empty($nome) || empty($email) || empty($senha) || empty($confirmar_senha)) {
            throw new Exception('Todos os campos obrigatórios devem ser preenchidos!');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido!');
        }

        if (strlen($senha) < 6) {
            throw new Exception('A senha deve ter pelo menos 6 caracteres!');
        }

        if ($senha !== $confirmar_senha) {
            throw new Exception('As senhas não coincidem!');
        }

        // Validar telefone (se você já tem função validarTelefone no config.php, mantenha-a)
        $telefone_validado = null;
        if (!empty($telefone)) {
            if (function_exists('validarTelefone')) {
                $telefone_validado = validarTelefone($telefone); // manter compatibilidade
                if (!$telefone_validado) {
                    throw new Exception('Telefone inválido! Use o formato: 11999998888 ou (11) 99999-8888');
                }
            } else {
                // fallback simples: armazenar apenas dígitos
                $telefone_validado = onlyDigits($telefone);
                if (strlen($telefone_validado) < 10) {
                    throw new Exception('Telefone inválido! Use o formato correto.');
                }
            }
        }

        // Validar PIX (opcional)
        $chave_pix = null;
        if (empty($chave_pix_raw)) {
        throw new Exception('A chave PIX é obrigatória!');
        }

        if (!in_array($pix_tipo, ['celular','email','cpf','cnpj','aleatoria'])) {
        throw new Exception('Tipo de chave PIX inválido!');
        }

        if (!validarPix($pix_tipo, $chave_pix_raw)) {
        throw new Exception('Chave PIX inválida para o tipo selecionado!');
        }

// normaliza antes de salvar
$chave_pix = normalizePix($pix_tipo, $chave_pix_raw);

        // Verificar se email já existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception('Este email já está cadastrado!');
        }

        // Verificar duplicidade de chave PIX (se fornecida)
        if ($chave_pix !== null) {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE pix_chave = ?");
            $stmt->execute([$chave_pix]);
            if ($stmt->fetch()) {
                throw new Exception('Esta chave PIX já está cadastrada em outra conta!');
            }
        }

        // Criptografar senha
        $senha_criptografada = password_hash($senha, PASSWORD_DEFAULT);

        // Inserir no banco como usuário comum (inclui pix_tipo e chave_pix)
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, telefone, senha, tipo, ativo, pix_tipo, pix_chave) VALUES (?, ?, ?, ?, 'comum', 1, ?, ?)");
        $stmt->execute([
            $nome,
            $email,
            $telefone_validado,
            $senha_criptografada,
            $pix_tipo,
            $chave_pix
        ]);

        $mensagem = 'Cadastro realizado com sucesso! Você já pode fazer login.';
        $tipo_mensagem = 'sucesso';

        header('Location: login.php?cadastro=sucesso');
        exit;

    } catch (Exception $e) {
        $mensagem = $e->getMessage();
        $tipo_mensagem = 'erro';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - BetMax</title>
    <style>
        /* (mantive o seu CSS original - cole o seu se preferir) */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        :root { --primary-bg: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d); --card-bg: rgba(255,255,255,0.95); --accent: #ff6b00; --accent-hover: #ff3c00; --text-dark: #333; --text-light: #666; --success: #4CAF50; --danger: #f44336; --border: #e0e0e0; }
        body { background: var(--primary-bg); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .cadastro-container { width: 100%; max-width: 450px; background: var(--card-bg); border-radius: 20px; overflow: hidden; box-shadow: 0 15px 30px rgba(0,0,0,0.3); }
        .cadastro-header { background: linear-gradient(135deg, #2d3748, #4a5568); color: white; padding: 30px; text-align: center; border-bottom: 3px solid var(--accent); }
        .logo { font-size: 32px; font-weight: bold; color: var(--accent); margin-bottom: 10px; }
        .cadastro-subtitle { font-size: 16px; opacity: 0.8; }
        .cadastro-body { padding: 40px; }
        .mensagem { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; text-align: center; }
        .mensagem.sucesso { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .mensagem.erro { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-dark); }
        .form-group input { width: 100%; padding: 15px; border: 2px solid var(--border); border-radius: 10px; font-size: 16px; transition: all 0.3s; }
        .form-group input:focus { border-color: var(--accent); outline: none; box-shadow: 0 0 0 3px rgba(255,107,0,0.1); }
        .btn { width: 100%; padding: 15px; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; text-decoration: none; display: inline-block; text-align: center; }
        .btn-primary { background: linear-gradient(135deg, var(--accent), var(--accent-hover)); color: white; }
        .btn-secondary { background: #6c757d; color: white; margin-top: 10px; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .login-link { text-align: center; margin-top: 25px; color: var(--text-light); }
        .login-link a { color: var(--accent); text-decoration: none; font-weight: 600; }
        .login-link a:hover { text-decoration: underline; }
        .password-strength { height: 4px; background: #e0e0e0; border-radius: 2px; margin-top: 5px; overflow: hidden; }
        .strength-weak { background: #f44336; width: 33%; } .strength-medium { background: #ff9800; width: 66%; } .strength-strong { background: #4caf50; width: 100%; }
        .pix-options {
    display: flex;
    flex-wrap: wrap; /* permite quebrar em várias linhas se necessário */
    gap: 10px; /* espaço entre cada opção */
    align-items: center; /* alinha verticalmente com o input se houver */
}
.pix-options label {
    display: flex;
    align-items: center; /* alinha o radio com o texto */
    gap: 4px; /* espaço entre o círculo e o texto */
    cursor: pointer;
    font-weight: 500;
}
        .small-note { font-size: 13px; color: var(--text-light); margin-top: 6px; display: block; }
        @media (max-width: 480px) { .cadastro-body { padding: 30px 20px; } .cadastro-header { padding: 20px; } }
    </style>
</head>
<body>
    <div class="cadastro-container">
        <!-- Header -->
        <div class="cadastro-header">
            <div class="logo">BETMAX</div>
            <div class="cadastro-subtitle">Crie sua conta e comece a apostar</div>
        </div>
        
        <!-- Body -->
        <div class="cadastro-body">
            <?php if ($mensagem): ?>
            <div class="mensagem <?php echo $tipo_mensagem; ?>">
                <?php echo $mensagem; ?>
            </div>
            <?php endif; ?>

            <form method="POST" id="cadastroForm">
                <div class="form-group">
                    <label for="nome">Nome Completo</label>
                    <input type="text" id="nome" name="nome" required 
                           placeholder="Digite seu nome completo"
                           value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="seu@email.com"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="telefone">Telefone (Opcional)</label>
                    <input type="tel" id="telefone" name="telefone" 
                           placeholder="(11) 99999-8888"
                           value="<?php echo isset($_POST['telefone']) ? htmlspecialchars($_POST['telefone']) : ''; ?>">
                </div>

                <!-- Nova seção: Chave PIX -->
                <div class="form-group">
                    <label>Adicionar sua Chave PIX </label>
                    <div class="pix-options" style="margin-bottom:8px;">
                        <label><input type="radio" name="pix_tipo" value="celular" <?php echo (isset($_POST['pix_tipo']) && $_POST['pix_tipo']=='celular') || !isset($_POST['pix_tipo']) ? 'checked' : ''; ?>> Celular</label>
                        <label><input type="radio" name="pix_tipo" value="email" <?php echo isset($_POST['pix_tipo']) && $_POST['pix_tipo']=='email' ? 'checked' : ''; ?>> E-mail</label>
                        <label><input type="radio" name="pix_tipo" value="cpf" <?php echo isset($_POST['pix_tipo']) && $_POST['pix_tipo']=='cpf' ? 'checked' : ''; ?>> CPF</label>
                        <label><input type="radio" name="pix_tipo" value="cnpj" <?php echo isset($_POST['pix_tipo']) && $_POST['pix_tipo']=='cnpj' ? 'checked' : ''; ?>> CNPJ</label>
                        <label><input type="radio" name="pix_tipo" value="aleatoria" <?php echo isset($_POST['pix_tipo']) && $_POST['pix_tipo']=='aleatoria' ? 'checked' : ''; ?>> Chave Aleatória</label>
                    </div>
                    <input type="text" id="chave_pix" name="chave_pix" 
                    placeholder="(11) 99999-8888 ou seu email..."
                    value="<?php echo isset($_POST['chave_pix']) ? htmlspecialchars($_POST['chave_pix']) : ''; ?>"
                    required>
                    <span class="small-note">Opcional — informe se quiser receber prêmios via PIX. A chave será verificada no momento do cadastro.</span>
                </div>
                <!-- Fim chave PIX -->

                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" required 
                           placeholder="Mínimo 6 caracteres" minlength="6">
                    <div class="password-strength">
                        <div id="passwordStrength" class="strength-weak"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirmar_senha">Confirmar Senha</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required 
                           placeholder="Digite a senha novamente" minlength="6">
                </div>
                
                <button type="submit" class="btn btn-primary">
                    📝 Criar Conta
                </button>
                
                <a href="login.php" class="btn btn-secondary">
                    ← Voltar para Login
                </a>
            </form>
            
            <div class="login-link">
                Já tem uma conta? <a href="login.php">Faça login aqui</a>
            </div>
        </div>
    </div>

    <script>
        // Força da senha
        document.getElementById('senha').addEventListener('input', function() {
            const senha = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            let strength = 0;
            if (senha.length >= 6) strength += 1;
            if (senha.length >= 8) strength += 1;
            if (/[A-Z]/.test(senha)) strength += 1;
            if (/[0-9]/.test(senha)) strength += 1;
            if (/[^A-Za-z0-9]/.test(senha)) strength += 1;
            strengthBar.className = 'strength-weak';
            if (strength >= 3) strengthBar.className = 'strength-medium';
            if (strength >= 5) strengthBar.className = 'strength-strong';
        });

        // Confirmar senha
        document.getElementById('confirmar_senha').addEventListener('input', function() {
            const senha = document.getElementById('senha').value;
            const confirmar = this.value;
            if (confirmar && senha !== confirmar) {
                this.style.borderColor = 'var(--danger)';
            } else {
                this.style.borderColor = 'var(--border)';
            }
        });

        // Formatação automática do telefone (mantive sua lógica)
        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.substring(0, 11);
            if (value.length > 10) value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            else if (value.length > 6) value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
            else if (value.length > 2) value = value.replace(/(\d{2})(\d{0,5})/, '($1) $2');
            else if (value.length > 0) value = value.replace(/(\d{0,2})/, '($1');
            e.target.value = value;
        });

        // PIX - alterar placeholder conforme tipo selecionado e validar simples no cliente
        const pixRadios = document.querySelectorAll('input[name="pix_tipo"]');
        const pixInput = document.getElementById('chave_pix');

        function updatePixPlaceholder() {
            const tipo = document.querySelector('input[name="pix_tipo"]:checked').value;
            switch(tipo) {
                case 'email':
                    pixInput.placeholder = 'seu@email.com';
                    pixInput.type = 'text';
                    break;
                case 'cpf':
                    pixInput.placeholder = '000.000.000-00';
                    pixInput.type = 'text';
                    break;
                case 'cnpj':
                    pixInput.placeholder = '00.000.000/0000-00';
                    pixInput.type = 'text';
                    break;
                case 'celular':
                    pixInput.placeholder = '(11) 99999-8888';
                    pixInput.type = 'tel';
                    break;
                case 'aleatoria':
                    pixInput.placeholder = 'Chave aleatória (ex: abcd1234-...)';
                    pixInput.type = 'text';
                    break;
            }
        }

        pixRadios.forEach(r => r.addEventListener('change', updatePixPlaceholder));
        updatePixPlaceholder(); // set inicial

        // Validação simples antes do envio (ajuda UX; validação final no servidor)
        document.getElementById('cadastroForm').addEventListener('submit', function(e) {
            const tipo = document.querySelector('input[name="pix_tipo"]:checked').value;
            const val = pixInput.value.trim();
            if (val === '') {
            e.preventDefault();
            alert('A chave PIX é obrigatória!');
            pixInput.focus();
            return;
            }

            let ok = true;
            if (tipo === 'email') {
                ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
            } else if (tipo === 'cpf') {
                ok = /^\d{3}\.?\d{3}\.?\d{3}-?\d{2}$/.test(val);
            } else if (tipo === 'cnpj') {
                ok = /^\d{2}\.?\d{3}\.?\d{3}\/?\d{4}-?\d{2}$/.test(val);
            } else if (tipo === 'celular') {
                const d = val.replace(/\D/g,'');
                if (d.length === 0) ok = false;
                else ok = (d.length === 10 || d.length === 11 || (d.length>11 && d.startsWith('55')));
            } else if (tipo === 'aleatoria') {
                ok = val.length >= 6 && val.length <= 64;
            }
            if (!ok) {
                e.preventDefault();
                alert('Chave PIX inválida para o tipo selecionado. Verifique e tente novamente.');
                pixInput.focus();
            }
        });
    </script>
</body>
</html>
