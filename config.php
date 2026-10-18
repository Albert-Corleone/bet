<?php
// config.php - Conexão, sessões e funções básicas

// Exibir erros temporariamente para debug (remover em produção)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Dados de conexão
$host = 'localhost';
$dbname = 'wwcorl_sistema_apostas';
$user = 'wwcorl_apostas_user';
$pass = '415263748596.';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Erro de conexão: " . $e->getMessage());
}

// Inicia sessão se não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Funções básicas
function estaLogado() {
    return isset($_SESSION['usuario_id']);
}

function isAdmin() {
    return isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin';
}

// Função para exigir admin
function requireAdmin() {
    if (!isAdmin()) {
        header('HTTP/1.1 403 Forbidden');
        exit('Acesso negado. Você precisa ser administrador.');
    }
}

// Formatar telefone
function formatarTelefone($telefone) {
    if (empty($telefone)) return '';
    $telefone = preg_replace('/[^0-9]/', '', $telefone);

    if (strlen($telefone) === 11) {
        return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
    }

    return $telefone;
}

// Validar telefone
function validarTelefone($telefone) {
    $telefone = preg_replace('/[^0-9]/', '', $telefone);

    if (strlen($telefone) !== 11) return false;

    $ddd = substr($telefone, 0, 2);
    if ($ddd < 11 || $ddd > 99) return false;

    if (substr($telefone, 2, 1) !== '9') return false;

    return $telefone;
}