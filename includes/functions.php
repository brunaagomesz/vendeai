<?php
// Configurações gerais e funções auxiliares do VendeAí.

// Caminho da aplicação no Apache (http://localhost/vendeai).
define('BASE_URL', '/vendeai');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

function url(string $caminho = ''): string
{
    return BASE_URL . '/' . ltrim($caminho, '/');
}

function redirecionar(string $caminho): void
{
    header('Location: ' . url($caminho));
    exit;
}

function preco_formatado($valor): string
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

// ---- Autenticação ----

function usuario_logado(): bool
{
    return isset($_SESSION['usuario_id']);
}

function exigir_login(): void
{
    if (!usuario_logado()) {
        $_SESSION['flash'] = ['tipo' => 'erro', 'texto' => 'Faça login para continuar.'];
        redirecionar('login.php');
    }
}

// ---- Mensagens rápidas (flash) ----

function flash(string $tipo, string $texto): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'texto' => $texto];
}

function pegar_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

// ---- Proteção CSRF ----

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valido(): bool
{
    $enviado = $_POST['csrf_token'] ?? '';
    return is_string($enviado)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $enviado);
}

// ---- Imagem do produto ----

function imagem_produto(?string $arquivo): string
{
    if ($arquivo) {
        return url('assets/img/produtos/' . rawurlencode($arquivo));
    }
    return url('assets/img/capi/capi-caixa.svg');
}
