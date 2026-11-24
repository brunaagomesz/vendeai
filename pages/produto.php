<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$produto = null;

if ($id) {
    try {
        $stmt = $pdo->prepare(
            'SELECT p.*, c.nome AS categoria_nome, u.nome AS vendedor_nome
               FROM produtos p
          LEFT JOIN categorias c ON c.id = p.categoria_id
          LEFT JOIN usuarios u ON u.id = p.vendedor_id
              WHERE p.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $produto = $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}

$ehDono = $produto
    && usuario_logado()
    && (int) $produto['vendedor_id'] === (int) $_SESSION['usuario_id'];

// Produto inativo só aparece para o próprio vendedor.
if (!$produto || (!$produto['ativo'] && !$ehDono)) {
    http_response_code(404);
    $titulo = 'Produto não encontrado';
    $capiContexto = 'vazio';
    include __DIR__ . '/../includes/header.php';
    echo '<main class="conteudo"><h1>Produto não encontrado</h1>'
       . '<p class="vazio">Não achei esse produto por aqui.</p>'
       . '<p><a class="btn" href="' . url('pages/catalogo.php') . '">Voltar ao catálogo</a></p></main>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$titulo = $produto['nome'];
$capiContexto = 'geral';
$estoque = (int) $produto['estoque'];
$vendedor = $produto['vendedor_nome'] ?? 'VendeAí';
include __DIR__ . '/../includes/header.php';
?>
<main class="envolve pagina">
    <nav class="migalhas" aria-label="Você está em">
        <a href="<?= url('index.php') ?>">Início</a> ›
        <?php if ($produto['categoria_nome']): ?>
            <a href="<?= url('pages/catalogo.php?categoria=' . (int) $produto['categoria_id']) ?>"><?= e($produto['categoria_nome']) ?></a> ›
        <?php else: ?>
            <a href="<?= url('pages/catalogo.php') ?>">Catálogo</a> ›
        <?php endif; ?>
        <span><?= e($produto['nome']) ?></span>
    </nav>

    <article class="produto painel">
        <!-- Foto (ou o ícone da categoria, quando não tem foto) -->
        <div class="foto">
            <?php if ($produto['imagem']): ?>
                <img src="<?= imagem_produto($produto['imagem']) ?>" alt="<?= e($produto['nome']) ?>">
            <?php else: ?>
                <?= icone_categoria($produto['categoria_nome']) ?>
                <span class="sem">Sem foto</span>
            <?php endif; ?>
        </div>

        <div class="prod-dados">
            <?php if (!$produto['ativo']): ?>
                <span class="selo-linha selo-esgotado">Anúncio pausado</span>
            <?php endif; ?>
            <?php if ($produto['categoria_nome']): ?>
                <span class="card-cat"><?= e($produto['categoria_nome']) ?></span>
            <?php endif; ?>
            <h1><?= e($produto['nome']) ?></h1>
            <p class="prod-preco"><?= preco_html($produto['preco']) ?></p>

            <!-- Selo de estoque: verde quando tem, vermelho quando acabou -->
            <?php if ($estoque > 0): ?>
                <span class="estoque">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>
                    <?= $estoque ?> em estoque
                </span>
            <?php else: ?>
                <span class="estoque fim">Produto esgotado</span>
            <?php endif; ?>

            <div class="frete-linha">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/></svg>
                <div>Calcule o frete no carrinho. <strong>Grátis acima de <?= e(preco_formatado(FRETE_GRATIS_ACIMA)) ?>.</strong></div>
            </div>

            <?php if ($ehDono): ?>
                <a class="btn btn-azul" href="<?= url('pages/produto-form.php?id=' . (int) $produto['id']) ?>">Editar anúncio</a>
            <?php elseif ($estoque > 0 && $produto['ativo']): ?>
                <!-- Mesmo formulário de antes: envia acao, id, qtd e destino para carrinho-acao.php -->
                <form class="compra" method="POST" action="<?= url('pages/carrinho-acao.php') ?>">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="acao" value="adicionar">
                    <input type="hidden" name="id" value="<?= (int) $produto['id'] ?>">
                    <div class="qtd-linha">
                        <label for="qtd">Quantidade</label>
                        <!-- Os botões − e + só mudam o número do campo (ver script.js) -->
                        <div class="qtd" data-qtd>
                            <button type="button" data-passo="-1" aria-label="Diminuir quantidade">−</button>
                            <input type="number" id="qtd" name="qtd" value="1" min="1"
                                   max="<?= min($estoque, CARRINHO_MAX_POR_ITEM) ?>" inputmode="numeric">
                            <button type="button" data-passo="1" aria-label="Aumentar quantidade">+</button>
                        </div>
                    </div>
                    <button type="submit" name="destino" value="carrinho" class="btn btn-azul">Comprar agora</button>
                    <button type="submit" name="destino" value="produto" class="btn btn-claro">Adicionar ao carrinho</button>
                </form>
            <?php else: ?>
                <button type="button" class="btn" disabled>Produto esgotado</button>
            <?php endif; ?>

            <div class="vendedor">
                <span class="av"><?= e(inicial($vendedor)) ?></span>
                <div>
                    <strong>Vendido por <?= e($vendedor) ?></strong>
                    <span>Anunciante do VendeAí</span>
                </div>
            </div>
        </div>
    </article>

    <section class="descricao painel">
        <h2>Descrição</h2>
        <p><?= nl2br(e($produto['descricao'] ?: 'Sem descrição.')) ?></p>
    </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
