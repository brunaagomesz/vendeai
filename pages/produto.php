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
include __DIR__ . '/../includes/header.php';
?>
<main class="conteudo">
    <p class="migalhas">
        <a href="<?= url('pages/catalogo.php') ?>">Catálogo</a>
        <?php if ($produto['categoria_nome']): ?>
            › <a href="<?= url('pages/catalogo.php?categoria=' . (int) $produto['categoria_id']) ?>"><?= e($produto['categoria_nome']) ?></a>
        <?php endif; ?>
    </p>

    <article class="produto-detalhe">
        <div class="produto-foto">
            <img src="<?= imagem_produto($produto['imagem']) ?>" alt="<?= e($produto['nome']) ?>">
        </div>
        <div class="produto-dados">
            <?php if (!$produto['ativo']): ?>
                <span class="selo selo-esgotado">Anúncio pausado</span>
            <?php endif; ?>
            <h1><?= e($produto['nome']) ?></h1>
            <p class="preco preco-grande"><?= e(preco_formatado($produto['preco'])) ?></p>

            <?php if ((int) $produto['estoque'] > 0): ?>
                <p class="estoque ok"><?= (int) $produto['estoque'] ?> em estoque</p>
            <?php else: ?>
                <p class="estoque fim">Produto esgotado</p>
            <?php endif; ?>

            <p class="vendedor">Vendido por <strong><?= e($produto['vendedor_nome'] ?? 'VendeAí') ?></strong></p>

            <?php if ($ehDono): ?>
                <a class="btn" href="<?= url('pages/produto-form.php?id=' . (int) $produto['id']) ?>">Editar anúncio</a>
            <?php else: ?>
                <button type="button" class="btn" disabled title="Pagamento online fica para etapas futuras">Comprar (em breve)</button>
            <?php endif; ?>

            <h2>Descrição</h2>
            <p class="descricao"><?= nl2br(e($produto['descricao'] ?: 'Sem descrição.')) ?></p>
        </div>
    </article>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
