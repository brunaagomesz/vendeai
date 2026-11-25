<?php
// Cabeçalho comum. Variáveis opcionais antes do include:
//   $titulo        título da página
//   $capiContexto  chave do contexto do Capi (ver includes/capi.php)
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/carrinho.php';
$titulo = $titulo ?? 'VendeAí';
$busca_atual = campo_get('q');
$flash = pegar_flash();

// Dados usados no topo e no menu lateral
$logado = usuario_logado();
$nomeCompleto = $logado ? ($_SESSION['usuario_nome'] ?? '') : '';
$primeiroNome = $logado ? explode(' ', trim($nomeCompleto))[0] : '';
$qtdCarrinho = carrinho_quantidade();

// Página atual, para destacar o item certo no menu lateral
$paginaAtual = basename($_SERVER['SCRIPT_NAME']);
$categoriaAtual = (int) ($_GET['categoria'] ?? 0);

// Categorias do menu lateral (vêm do banco; se der erro, o menu fica sem elas)
$categoriasMenu = [];
if (isset($pdo)) {
    try {
        $categoriasMenu = $pdo->query('SELECT id, nome FROM categorias ORDER BY nome')->fetchAll();
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> | VendeAí</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>?v=<?= (int) @filemtime(__DIR__ . '/../assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/carrinho.css') ?>?v=<?= (int) @filemtime(__DIR__ . '/../assets/css/carrinho.css') ?>">
</head>
<body>
<header class="topo">
    <div class="envolve topo-linha">
        <!-- Botão de três barras: abre o menu lateral (o JavaScript está em assets/js/script.js) -->
        <button class="botao-menu" id="botao-menu" type="button" aria-label="Abrir menu" aria-controls="menu-lateral" aria-expanded="false">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>

        <a class="logo" href="<?= url('index.php') ?>">Vende<span>Aí</span></a>

        <form class="busca" method="GET" action="<?= url('pages/catalogo.php') ?>" role="search">
            <label for="busca-topo" class="sr-only">Buscar produtos</label>
            <input type="search" id="busca-topo" name="q" maxlength="100"
                   placeholder="O que você está procurando hoje?"
                   value="<?= e($busca_atual) ?>">
            <button type="submit" aria-label="Buscar">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            </button>
        </form>

        <div class="acoes-topo">
            <!-- Botão amarelo "Vender": some no celular (lá ele fica no menu lateral) -->
            <a class="vender-topo" href="<?= url('pages/produto-form.php') ?>">Vender</a>

            <?php if ($logado): ?>
                <a class="icone-btn" href="<?= url('pages/minha-conta.php') ?>" aria-label="Minha conta">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                    <span class="txt"><small>Olá, <?= e($primeiroNome) ?></small>Minha conta</span>
                </a>
            <?php else: ?>
                <a class="icone-btn" href="<?= url('login.php') ?>" aria-label="Entrar">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                    <span class="txt"><small>Olá, visitante</small>Entrar</span>
                </a>
                <a class="icone-btn so-computador" href="<?= url('cadastro.php') ?>">Criar conta</a>
            <?php endif; ?>

            <a class="icone-btn" href="<?= url('pages/carrinho.php') ?>" aria-label="Carrinho, <?= $qtdCarrinho ?> <?= $qtdCarrinho === 1 ? 'item' : 'itens' ?>">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 1.9-1.4L21 8H6.2"/><circle cx="10" cy="20.5" r="1.3"/><circle cx="17" cy="20.5" r="1.3"/></svg>
                <?php if ($qtdCarrinho > 0): ?><span class="badge"><?= $qtdCarrinho ?></span><?php endif; ?>
                <span class="txt">Carrinho</span>
            </a>
        </div>
    </div>
</header>

<!-- Menu lateral: abre com a classe "menu-aberto" no body (ver assets/js/script.js) -->
<div class="fundo-menu" id="fundo-menu"></div>
<aside class="menu-lateral" id="menu-lateral" aria-label="Menu" aria-hidden="true">
    <div class="menu-topo">
        <div class="menu-ola">
            <?php if ($logado): ?>
                <span class="menu-av"><?= e(inicial($primeiroNome)) ?></span>
                <div><small>Olá,</small><?= e($primeiroNome) ?></div>
            <?php else: ?>
                <span class="menu-av">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                </span>
                <div><small>Olá,</small>visitante</div>
            <?php endif; ?>
        </div>
        <button class="fechar-menu" id="fechar-menu" type="button" aria-label="Fechar menu">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
        </button>
    </div>

    <nav class="menu-nav">
        <div class="menu-grupo">
            <a class="menu-item <?= $paginaAtual === 'index.php' ? 'ativo' : '' ?>" href="<?= url('index.php') ?>">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11 12 4l9 7"/><path d="M5 10v10h14V10"/></svg>
                <span>Início</span>
            </a>
            <a class="menu-item <?= $paginaAtual === 'catalogo.php' && !$categoriaAtual ? 'ativo' : '' ?>" href="<?= url('pages/catalogo.php') ?>">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/></svg>
                <span>Catálogo</span>
            </a>
        </div>

        <?php if ($categoriasMenu): ?>
            <div class="menu-grupo">
                <span class="menu-titulo">Categorias</span>
                <?php foreach ($categoriasMenu as $c): ?>
                    <a class="menu-item <?= $paginaAtual === 'catalogo.php' && $categoriaAtual === (int) $c['id'] ? 'ativo' : '' ?>"
                       href="<?= url('pages/catalogo.php?categoria=' . (int) $c['id']) ?>">
                        <?= icone_categoria($c['nome']) ?>
                        <span><?= e($c['nome']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="menu-grupo">
            <span class="menu-titulo">Minha conta</span>
            <a class="menu-item <?= $paginaAtual === 'produto-form.php' ? 'ativo' : '' ?>" href="<?= url('pages/produto-form.php') ?>">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                <span>Anunciar produto</span>
            </a>
            <?php if ($logado): ?>
                <a class="menu-item <?= $paginaAtual === 'minha-conta.php' ? 'ativo' : '' ?>" href="<?= url('pages/minha-conta.php') ?>">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                    <span>Meus anúncios</span>
                </a>
            <?php endif; ?>
            <a class="menu-item <?= $paginaAtual === 'carrinho.php' ? 'ativo' : '' ?>" href="<?= url('pages/carrinho.php') ?>">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 1.9-1.4L21 8H6.2"/><circle cx="10" cy="20.5" r="1.3"/><circle cx="17" cy="20.5" r="1.3"/></svg>
                <span>Carrinho<?= $qtdCarrinho > 0 ? ' (' . $qtdCarrinho . ')' : '' ?></span>
            </a>
        </div>
    </nav>

    <?php if ($logado): ?>
        <!-- Sair continua sendo um formulário POST com o token CSRF, como antes -->
        <form method="POST" action="<?= url('logout.php') ?>" class="form-sair">
            <?= csrf_campo() ?>
            <button type="submit" class="menu-sair">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 4h4v16h-4"/><path d="M10 8l-4 4 4 4"/><path d="M6 12h10"/></svg>
                <span>Sair</span>
            </button>
        </form>
    <?php else: ?>
        <div class="menu-entrar">
            <a class="btn btn-azul" href="<?= url('login.php') ?>">Entrar</a>
            <a class="btn btn-claro" href="<?= url('cadastro.php') ?>">Criar conta</a>
        </div>
    <?php endif; ?>
</aside>

<?php if ($flash): ?>
    <div class="flash-area">
        <p class="mensagem <?= e($flash['tipo']) ?>" role="status"><?= e($flash['texto']) ?></p>
    </div>
<?php endif; ?>
