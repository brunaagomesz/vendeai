<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/carrinho.php';

try {
    $carrinho = carrinho_detalhado($pdo);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $carrinho = ['itens' => [], 'subtotal' => 0.0, 'unidades' => 0, 'avisos' => []];
}

$itens = $carrinho['itens'];
$subtotal = $carrinho['subtotal'];
$unidades = $carrinho['unidades'];

// Frete: só calcula quando existe CEP salvo e o carrinho não está vazio.
$cep = $_SESSION['frete_cep'] ?? null;
$opcoes = ($cep && $itens) ? frete_opcoes($cep, $subtotal, $unidades) : [];
$escolhida = $_SESSION['frete_opcao'] ?? 'economico';
if (!isset($opcoes[$escolhida])) {
    $escolhida = 'economico';
}
$frete = $opcoes ? $opcoes[$escolhida]['valor'] : null;
$total = $subtotal + ($frete ?? 0);
$faltaGratis = max(0, FRETE_GRATIS_ACIMA - $subtotal);

$titulo = 'Carrinho';
$capiContexto = $itens ? 'geral' : 'vazio';
include __DIR__ . '/../includes/header.php';
$acao = url('pages/carrinho-acao.php');
?>
<main class="conteudo">
    <h1>Meu carrinho</h1>

    <?php foreach ($carrinho['avisos'] as $aviso): ?>
        <p class="mensagem erro"><?= e($aviso) ?></p>
    <?php endforeach; ?>

    <?php if (!$itens): ?>
        <p class="vazio">Seu carrinho está vazio. Que tal dar uma olhada no catálogo?</p>
        <p><a class="btn" href="<?= url('pages/catalogo.php') ?>">Ver produtos</a></p>
    <?php else: ?>
    <div class="carrinho-grade">
        <section class="carrinho-itens" aria-label="Itens do carrinho">
            <?php foreach ($itens as $i): ?>
                <article class="carrinho-item">
                    <a class="carrinho-foto" href="<?= url('pages/produto.php?id=' . $i['id']) ?>">
                        <img src="<?= imagem_produto($i['imagem']) ?>" alt="<?= e($i['nome']) ?>" loading="lazy">
                    </a>

                    <div class="carrinho-info">
                        <h2><a href="<?= url('pages/produto.php?id=' . $i['id']) ?>"><?= e($i['nome']) ?></a></h2>
                        <p class="carrinho-unitario"><?= e(preco_formatado($i['preco'])) ?> cada</p>

                        <form class="qtd-form" method="POST" action="<?= $acao ?>">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="acao" value="atualizar">
                            <input type="hidden" name="id" value="<?= $i['id'] ?>">
                            <!-- Botão invisível: é o que o Enter usa para salvar a quantidade digitada. -->
                            <button type="submit" name="delta" value="0" class="sr-only" tabindex="-1">Atualizar quantidade</button>
                            <span class="qtd-controle">
                                <button type="submit" name="delta" value="-1" aria-label="Diminuir quantidade de <?= e($i['nome']) ?>">−</button>
                                <input type="number" name="qtd" value="<?= $i['qtd'] ?>" min="1" max="<?= min($i['estoque'], CARRINHO_MAX_POR_ITEM) ?>"
                                       aria-label="Quantidade de <?= e($i['nome']) ?>" inputmode="numeric">
                                <button type="submit" name="delta" value="1" aria-label="Aumentar quantidade de <?= e($i['nome']) ?>"
                                        <?= $i['qtd'] >= min($i['estoque'], CARRINHO_MAX_POR_ITEM) ? 'disabled' : '' ?>>+</button>
                            </span>
                        </form>
                        <?php if ($i['estoque'] <= 5): ?>
                            <p class="carrinho-aviso">Restam só <?= $i['estoque'] ?> em estoque</p>
                        <?php endif; ?>
                    </div>

                    <div class="carrinho-lado">
                        <p class="preco"><?= e(preco_formatado($i['total'])) ?></p>
                        <form method="POST" action="<?= $acao ?>">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="acao" value="remover">
                            <input type="hidden" name="id" value="<?= $i['id'] ?>">
                            <button type="submit" class="link-perigo carrinho-remover">Remover</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>

            <div class="carrinho-rodape-itens">
                <a href="<?= url('pages/catalogo.php') ?>">← Continuar comprando</a>
                <form method="POST" action="<?= $acao ?>">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="acao" value="esvaziar">
                    <button type="submit" class="link-perigo">Esvaziar carrinho</button>
                </form>
            </div>
        </section>

        <aside class="carrinho-resumo" aria-label="Resumo do pedido">
            <h2>Resumo</h2>

            <dl class="resumo-linhas">
                <div><dt>Produtos (<?= $unidades ?> <?= $unidades === 1 ? 'item' : 'itens' ?>)</dt><dd><?= e(preco_formatado($subtotal)) ?></dd></div>
                <div>
                    <dt>Frete</dt>
                    <dd>
                        <?php if ($frete === null): ?>
                            <span class="resumo-suave">calcule abaixo</span>
                        <?php elseif ($frete == 0): ?>
                            <span class="frete-gratis">Grátis</span>
                        <?php else: ?>
                            <?= e(preco_formatado($frete)) ?>
                        <?php endif; ?>
                    </dd>
                </div>
            </dl>

            <form class="frete-form" method="POST" action="<?= $acao ?>">
                <?= csrf_campo() ?>
                <input type="hidden" name="acao" value="frete">
                <label for="cep">Calcular frete</label>
                <div class="cep-linha">
                    <input type="text" id="cep" name="cep" inputmode="numeric" autocomplete="postal-code"
                           maxlength="9" placeholder="00000-000" value="<?= $cep ? e(cep_formatado($cep)) : '' ?>">
                    <button type="submit" class="btn btn-claro">OK</button>
                </div>
                <a class="ajuda-cep" href="https://buscacepinter.correios.com.br/app/endereco/index.php" target="_blank" rel="noopener">Não sei meu CEP</a>
            </form>

            <?php if ($opcoes): ?>
                <form class="frete-opcoes" method="POST" action="<?= $acao ?>">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="acao" value="escolher_frete">
                    <p class="frete-regiao">Entrega para <?= e($opcoes['economico']['regiao']) ?></p>
                    <?php foreach ($opcoes as $o): ?>
                        <label class="frete-opcao <?= $o['id'] === $escolhida ? 'marcada' : '' ?>">
                            <input type="radio" name="opcao" value="<?= e($o['id']) ?>" <?= $o['id'] === $escolhida ? 'checked' : '' ?>
                                   onchange="this.form.submit()">
                            <span class="frete-texto">
                                <strong><?= e($o['nome']) ?></strong>
                                <small>em até <?= $o['dias'] ?> dias úteis</small>
                            </span>
                            <span class="frete-valor"><?= $o['gratis'] ? 'Grátis' : e(preco_formatado($o['valor'])) ?></span>
                        </label>
                    <?php endforeach; ?>
                    <noscript><button type="submit" class="btn btn-claro">Usar essa opção</button></noscript>
                </form>
            <?php endif; ?>

            <?php if ($faltaGratis > 0): ?>
                <p class="frete-dica">Faltam <strong><?= e(preco_formatado($faltaGratis)) ?></strong> para o frete Econômico ser grátis.</p>
            <?php else: ?>
                <p class="frete-dica ok">Você ganhou frete Econômico grátis! 🎉</p>
            <?php endif; ?>

            <p class="resumo-total"><span>Total</span><strong><?= e(preco_formatado($total)) ?></strong></p>
            <?php if ($frete === null): ?>
                <p class="resumo-suave pequeno">Sem o frete. Informe seu CEP para ver o total final.</p>
            <?php endif; ?>

            <button type="button" class="btn" disabled title="Pagamento online fica para a próxima etapa">Finalizar compra (em breve)</button>
        </aside>
    </div>
    <?php endif; ?>
</main>
<script>
// Deixa o CEP no formato 00000-000 enquanto a pessoa digita.
(function () {
    var cep = document.getElementById('cep');
    if (!cep) { return; }
    cep.addEventListener('input', function () {
        var n = cep.value.replace(/\D/g, '').slice(0, 8);
        cep.value = n.length > 5 ? n.slice(0, 5) + '-' + n.slice(5) : n;
    });
})();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
