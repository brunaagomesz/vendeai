<?php
// Card de produto reutilizado na home e no catálogo.
// Uso: renderizar_produto_card($produto)
// O SELECT precisa trazer: id, nome, preco, imagem, estoque, categoria_nome e data_cadastro.
function renderizar_produto_card(array $p): void
{
    $semEstoque = (int) $p['estoque'] === 0;
    $novo = produto_novo($p['data_cadastro'] ?? null);
    ?>
    <a class="card" href="<?= url('pages/produto.php?id=' . (int) $p['id']) ?>">
        <div class="foto">
            <?php if ($p['imagem']): ?>
                <img src="<?= imagem_produto($p['imagem']) ?>" alt="<?= e($p['nome']) ?>" loading="lazy">
            <?php else: ?>
                <!-- Sem foto: fundo cinza com o ícone da categoria -->
                <?= icone_categoria($p['categoria_nome'] ?? null) ?>
                <span class="sem">Sem foto</span>
            <?php endif; ?>

            <?php if ($novo): ?><span class="selo selo-novo">Novo</span><?php endif; ?>
            <?php if ($semEstoque): ?><span class="selo selo-esgotado">Esgotado</span><?php endif; ?>
        </div>
        <div class="card-info">
            <?php if (!empty($p['categoria_nome'])): ?>
                <span class="card-cat"><?= e($p['categoria_nome']) ?></span>
            <?php endif; ?>
            <h3 class="card-nome"><?= e($p['nome']) ?></h3>
            <p class="card-preco"><?= preco_html($p['preco']) ?></p>
        </div>
    </a>
    <?php
}
