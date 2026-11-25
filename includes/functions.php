<?php
// Configurações gerais e funções auxiliares do VendeAí.

// Horário de Brasília (o php.ini do XAMPP vem com Europe/Berlin).
date_default_timezone_set('America/Sao_Paulo');

// Caminho da aplicação no servidor, descoberto sozinho:
//   http://localhost/vendeai/   -> "/vendeai"
//   http://localhost/outro-nome -> "/outro-nome"
//   php -S / PHP Server do VS Code (pasta do projeto como raiz) -> ""
// Para forçar um valor, defina BASE_URL antes de incluir este arquivo.
function detectar_base_url(): string
{
    $raiz = realpath(__DIR__ . '/..');
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    if ($raiz && $docRoot) {
        $raiz = rtrim(str_replace('\\', '/', $raiz), '/');
        $docRoot = rtrim(str_replace('\\', '/', $docRoot), '/');
        // No Windows a caixa das letras do caminho pode variar (C:/ x c:/)
        if (strncasecmp($raiz, $docRoot, strlen($docRoot)) === 0) {
            $resto = substr($raiz, strlen($docRoot));
            return implode('/', array_map('rawurlencode', explode('/', $resto)));
        }
    }
    return '/vendeai';
}

if (!defined('BASE_URL')) {
    define('BASE_URL', detectar_base_url());
}

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

    // A conta ainda existe no banco? (pode ter sido apagada com a sessão aberta)
    $pdo = $GLOBALS['pdo'] ?? null;
    if ($pdo instanceof PDO) {
        try {
            $stmt = $pdo->prepare('SELECT 1 FROM usuarios WHERE id = :id');
            $stmt->execute(['id' => (int) $_SESSION['usuario_id']]);
            if (!$stmt->fetchColumn()) {
                encerrar_sessao();
                flash('erro', 'Sua sessão não é mais válida. Entre novamente.');
                redirecionar('login.php');
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
        }
    }
}

// Apaga a sessão atual por completo e começa uma nova, vazia (para o flash funcionar).
function encerrar_sessao(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    session_start();
    // Código novo de sessão, para o navegador receber a chave certa.
    session_regenerate_id(true);
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

function campo_get(string $nome): string
{
    $valor = $_GET[$nome] ?? '';
    return is_string($valor) ? trim($valor) : '';
}

// ---- Visual ----

// Preço com o "R$" menor que o número, como no visual novo: <small>R$</small> 149,90
// Só números entram aqui, então não precisa de e().
function preco_html($valor): string
{
    return '<small>R$</small> ' . number_format((float) $valor, 2, ',', '.');
}

// Produto cadastrado nos últimos 7 dias ganha o selo "Novo".
function produto_novo(?string $dataCadastro): bool
{
    if (!$dataCadastro) {
        return false;
    }
    return strtotime($dataCadastro) >= strtotime('-7 days');
}

// Primeira letra do nome, em maiúscula (usada nas bolinhas com a inicial).
function inicial(?string $nome): string
{
    $nome = trim($nome ?? '');
    return $nome === '' ? '?' : mb_strtoupper(mb_substr($nome, 0, 1));
}

// Ícone (SVG) de cada categoria, escolhido pelo nome.
// Categoria nova, que não está na lista, recebe um ícone genérico de caixa.
// O SVG usa "currentColor": a cor vem do CSS (sempre o azul do site).
function icone_categoria(?string $nome, int $tamanho = 22): string
{
    $desenhos = [
        'eletronicos' => '<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="3" y="14" width="5" height="7" rx="2"/><rect x="16" y="14" width="5" height="7" rx="2"/>',
        'moda'        => '<path d="M8 3 3 6l2 5 2-1v11h10V10l2 1 2-5-5-3a4 4 0 0 1-8 0z"/>',
        'casa'        => '<path d="M3 11 12 4l9 7"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
        'papelaria'   => '<path d="M4 20l4-1L19 8l-3-3L5 16z"/><path d="m14 7 3 3"/>',
    ];
    // Caixa: usada quando a categoria não tem ícone próprio
    $caixa = '<path d="M3 8l9-5 9 5v8l-9 5-9-5z"/><path d="M3 8l9 5 9-5"/><path d="M12 13v8"/>';

    // Tira acentos e deixa minúsculo: "Eletrônicos" vira "eletronicos"
    $chave = mb_strtolower(strtr(trim($nome ?? ''), ['ô' => 'o', 'Ô' => 'o', 'ó' => 'o', 'é' => 'e', 'ê' => 'e', 'á' => 'a', 'ã' => 'a', 'í' => 'i', 'ú' => 'u', 'ç' => 'c']));
    $desenho = $desenhos[$chave] ?? $caixa;

    return '<svg class="icone-cat" width="' . $tamanho . '" height="' . $tamanho . '" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
        . ' stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $desenho . '</svg>';
}