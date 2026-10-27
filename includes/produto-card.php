<?php
// Card de produto reutilizado na home e no catálogo.
// Uso: renderizar_produto_card($produto)
function renderizar_produto_card(array $p): void
{
    $semEstoque = (int) $p['estoque'] === 0;
    ?>
    <article class="produto-card">
        <a href="<?= url('pages/produto.php?id=' . (int) $p['id']) ?>" class="produto-img">
            <img src="<?= imagem_produto($p['imagem']) ?>" alt="<?= e($p['nome']) ?>" loading="lazy">
            <?php if ($semEstoque): ?><span class="selo selo-esgotado">Esgotado</span><?php endif; ?>
        </a>
        <div class="produto-info">
            <?php if (!empty($p['categoria_nome'])): ?>
                <span class="produto-categoria"><?= e($p['categoria_nome']) ?></span>
            <?php endif; ?>
            <h3><a href="<?= url('pages/produto.php?id=' . (int) $p['id']) ?>"><?= e($p['nome']) ?></a></h3>
            <p class="preco"><?= e(preco_formatado($p['preco'])) ?></p>
        </div>
    </article>
    <?php
}
