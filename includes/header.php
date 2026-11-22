<?php
// Cabeçalho comum. Variáveis opcionais antes do include:
//   $titulo        título da página
//   $capiContexto  chave do contexto do Capi (ver includes/capi.php)
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/carrinho.php';
$titulo = $titulo ?? 'VendeAí';
$busca_atual = campo_get('q');
$flash = pegar_flash();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> | VendeAí</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/carrinho.css') ?>">
</head>
<body>
<header class="topo">
    <div class="topo-conteudo">
        <a class="logo" href="<?= url('index.php') ?>">Vende<span>Aí</span></a>

        <form class="busca" method="GET" action="<?= url('pages/catalogo.php') ?>" role="search">
            <label for="busca-topo" class="sr-only">Buscar produtos</label>
            <input type="search" id="busca-topo" name="q" maxlength="100"
                   placeholder="O que você está procurando hoje?"
                   value="<?= e($busca_atual) ?>">
            <button type="submit">Buscar</button>
        </form>

     <nav class="menu" aria-label="Menu principal">
    <a href="<?= url('pages/catalogo.php') ?>">Catálogo</a>

    <a href="<?= url('pages/carrinho.php') ?>" class="menu-carrinho" aria-label="Carrinho, <?= carrinho_quantidade() ?> itens">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.7 12.4a2 2 0 0 0 2 1.6h8.1a2 2 0 0 0 2-1.5L21.5 8H6"/></svg>
        Carrinho<?php if (carrinho_quantidade() > 0): ?> <span class="carrinho-contador"><?= carrinho_quantidade() ?></span><?php endif; ?>
    </a>

    <?php if (usuario_logado()): ?>
        <a href="<?= url('pages/produto-form.php') ?>" class="destaque">Vender</a>
        <a href="<?= url('pages/minha-conta.php') ?>">Olá, <?= e(explode(' ', $_SESSION['usuario_nome'])[0]) ?></a>
        <form method="POST" action="<?= url('logout.php') ?>" class="form-sair">
            <?= csrf_campo() ?>
            <button type="submit">Sair</button>
        </form>
    <?php else: ?>
        <a href="<?= url('login.php') ?>">Entrar</a>
        <a href="<?= url('cadastro.php') ?>" class="destaque">Criar conta</a>
    <?php endif; ?>
</nav>
    </div>
</header>

<?php if ($flash): ?>
    <div class="flash-area">
        <p class="mensagem <?= e($flash['tipo']) ?>" role="status"><?= e($flash['texto']) ?></p>
    </div>
<?php endif; ?>
