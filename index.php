<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/produto-card.php';

$titulo = 'Início';
$capiContexto = 'geral';

try {
    $categorias = $pdo->query('SELECT id, nome FROM categorias ORDER BY nome')->fetchAll();
    $destaques = $pdo->query(
        'SELECT p.id, p.nome, p.preco, p.imagem, p.estoque, c.nome AS categoria_nome
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

include __DIR__ . '/includes/header.php';
?>
<main>
    <section class="hero">
        <div class="hero-texto">
            <h1>Encontrou, gostou, <span>VendeAí!</span></h1>
            <p>O marketplace onde você compra de tudo um pouco — e também abre a sua loja em poucos minutos.</p>
            <div class="hero-acoes">
                <a class="btn" href="<?= url('pages/catalogo.php') ?>">Ver produtos</a>
                <?php if (usuario_logado()): ?>
                    <a class="btn btn-amarelo" href="<?= url('pages/produto-form.php') ?>">Anunciar produto</a>
                <?php else: ?>
                    <a class="btn btn-amarelo" href="<?= url('cadastro.php') ?>">Quero vender</a>
                <?php endif; ?>
            </div>
        </div>
        <img class="hero-capi" src="<?= url('assets/img/capi/capi-comemorando.svg') ?>"
             alt="Capi, o mascote do VendeAí, comemorando" width="240" height="240">
    </section>

    <section class="bloco">
        <h2>Categorias</h2>
        <div class="chips">
            <?php foreach ($categorias as $c): ?>
                <a class="chip" href="<?= url('pages/catalogo.php?categoria=' . (int) $c['id']) ?>"><?= e($c['nome']) ?></a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="bloco">
        <h2>Novidades</h2>
        <?php if (!$destaques): ?>
            <p class="vazio">Ainda não encontrei produtos por aqui.</p>
        <?php else: ?>
            <div class="grade-produtos">
                <?php foreach ($destaques as $p) { renderizar_produto_card($p); } ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="bloco como-funciona">
        <h2>Como vender no VendeAí</h2>
        <ol class="passos">
            <li><strong>Crie sua conta</strong><span>Cadastro rápido com nome, e-mail e senha.</span></li>
            <li><strong>Cadastre o produto</strong><span>Nome, preço, foto e estoque.</span></li>
            <li><strong>Apareça no catálogo</strong><span>Seu produto fica visível para todos.</span></li>
        </ol>
    </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
