<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/produto-card.php';

$titulo = 'Início';
$capiContexto = 'geral';

try {
    $categorias = $pdo->query('SELECT id, nome, descricao FROM categorias ORDER BY nome')->fetchAll();
    $destaques = $pdo->query(
        'SELECT p.id, p.nome, p.preco, p.imagem, p.estoque, p.data_cadastro, c.nome AS categoria_nome
           FROM produtos p
      LEFT JOIN categorias c ON c.id = p.categoria_id
          WHERE p.ativo = 1
       ORDER BY p.data_cadastro DESC, p.id DESC
          LIMIT 8'
    )->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $categorias = [];
    $destaques = [];
}

// Botão de vender: quem já tem conta vai direto anunciar; visitante vai criar conta
$linkVender = usuario_logado() ? url('pages/produto-form.php') : url('cadastro.php');

include __DIR__ . '/includes/header.php';
?>
<main class="envolve pagina">
    <!-- Destaque amarelo: texto à esquerda, vídeo do Capi à direita -->
    <section class="hero">
        <div class="hero-texto">
            <span class="rotulo">Compre e venda</span>
            <h1>Encontrou, gostou, <em>VendeAí!</em></h1>
            <p>O marketplace onde você compra de tudo um pouco e abre a sua loja em poucos minutos.</p>
            <div class="hero-acoes">
                <a class="btn btn-azul" href="<?= url('pages/catalogo.php') ?>">Ver produtos</a>
                <a class="btn btn-contorno" href="<?= $linkVender ?>"><?= usuario_logado() ? 'Anunciar produto' : 'Quero vender' ?></a>
            </div>
        </div>
        <div class="hero-capi">
            <p class="balao"><strong>Capi</strong>Oi! Bora achar algo legal?</p>
            <!-- O vídeo roda sozinho, sem som e em loop. Quem pediu "menos movimento" no
                 sistema vê o vídeo parado (o script.js pausa). As bordas somem pelo CSS (mask). -->
            <div class="capi-video">
                <video id="capi-video" autoplay muted loop playsinline
                       poster="<?= url('assets/video/capi-poster.jpg') ?>"
                       aria-label="Capi, a capivara mascote do VendeAí, acenando">
                    <source src="<?= url('assets/video/capi-loop.webm') ?>" type="video/webm">
                    <source src="<?= url('assets/video/capi-loop.mp4') ?>" type="video/mp4">
                </video>
            </div>
        </div>
    </section>

    <!-- Três vantagens do site -->
    <section class="vantagens" aria-label="Vantagens">
        <div class="vantagem painel">
            <span class="ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/></svg></span>
            <div><strong>Frete grátis acima de R$ 199</strong><span>Na entrega Econômica, para todo o Brasil</span></div>
        </div>
        <div class="vantagem painel">
            <span class="ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v18M3 12h18"/></svg></span>
            <div><strong>Anuncie de graça</strong><span>Nome, preço, foto e pronto</span></div>
        </div>
        <div class="vantagem painel">
            <span class="ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4 6v6c0 4.5 3.4 8 8 9 4.6-1 8-4.5 8-9V6z"/></svg></span>
            <div><strong>Conta protegida</strong><span>Senha guardada com segurança</span></div>
        </div>
    </section>

    <?php if ($categorias): ?>
        <section id="categorias" aria-labelledby="t-cats">
            <div class="secao-topo">
                <h2 id="t-cats">Categorias</h2>
                <a href="<?= url('pages/catalogo.php') ?>">Ver todas</a>
            </div>
            <div class="cats">
                <?php foreach ($categorias as $c): ?>
                    <a class="cat painel" href="<?= url('pages/catalogo.php?categoria=' . (int) $c['id']) ?>">
                        <span class="ico"><?= icone_categoria($c['nome']) ?></span>
                        <div>
                            <strong><?= e($c['nome']) ?></strong>
                            <?php if (!empty($c['descricao'])): ?><span><?= e($c['descricao']) ?></span><?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section aria-labelledby="t-novos">
        <div class="secao-topo">
            <h2 id="t-novos">Novidades</h2>
            <a href="<?= url('pages/catalogo.php') ?>">Ver o catálogo</a>
        </div>
        <?php if (!$destaques): ?>
            <p class="vazio">Ainda não encontrei produtos por aqui.</p>
        <?php else: ?>
            <div class="grade">
                <?php foreach ($destaques as $p) { renderizar_produto_card($p); } ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Convite para vender, com a ilustração de sacola -->
    <section class="painel vender-bloco" aria-labelledby="t-vender">
        <div class="vender-ilustra" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8h14l-1.2 12H6.2z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/><path d="M9.5 13.5l2 2 3.5-4"/></svg>
        </div>
        <div>
            <h2 id="t-vender">Tem algo parado em casa? Venda no VendeAí.</h2>
            <ol class="passos">
                <li><strong>Crie sua conta</strong>Nome, e-mail e senha.</li>
                <li><strong>Anuncie o produto</strong>Foto, preço e estoque.</li>
                <li><strong>Apareça no catálogo</strong>Todo mundo pode ver.</li>
            </ol>
            <a class="btn btn-azul" href="<?= $linkVender ?>">Anunciar agora</a>
        </div>
    </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
