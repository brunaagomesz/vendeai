<?php
// Rodapé comum (logo + colunas de links) + assistente Capi.
$capiContexto = $capiContexto ?? 'geral';
?>
<footer class="rodape">
    <div class="envolve">
        <div class="rodape-col">
            <div>
                <a class="logo" href="<?= url('index.php') ?>">Vende<span>Aí</span></a>
                <p>Encontrou, gostou, VendeAí! Marketplace criado como trabalho acadêmico com PHP e MySQL.</p>
            </div>
            <div>
                <h3>Comprar</h3>
                <a href="<?= url('pages/catalogo.php') ?>">Catálogo</a>
                <a href="<?= url('index.php#categorias') ?>">Categorias</a>
                <a href="<?= url('pages/carrinho.php') ?>">Carrinho</a>
            </div>
            <div>
                <h3>Vender</h3>
                <a href="<?= url('pages/produto-form.php') ?>">Anunciar produto</a>
                <a href="<?= url('pages/minha-conta.php') ?>">Meus anúncios</a>
            </div>
            <div>
                <h3>Conta</h3>
                <?php if (usuario_logado()): ?>
                    <a href="<?= url('pages/minha-conta.php') ?>">Minha conta</a>
                <?php else: ?>
                    <a href="<?= url('login.php') ?>">Entrar</a>
                    <a href="<?= url('cadastro.php') ?>">Criar conta</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="rodape-base">
            <span>Projeto acadêmico. Compras e entregas são simuladas.</span>
            <span><?= date('Y') ?> VendeAí</span>
        </div>
    </div>
</footer>

<?php
// O Capi do canto aparece em todas as páginas, menos nas que pedirem $esconderCapi = true
if (empty($esconderCapi)) {
    include __DIR__ . '/capi.php';
}
?>
<script src="<?= url('assets/js/script.js') ?>"></script>
</body>
</html>
