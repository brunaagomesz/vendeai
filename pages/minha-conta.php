<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

exigir_login();

$usuarioId = (int) $_SESSION['usuario_id'];
$usuario = null;
$meusProdutos = [];

try {
    $stmt = $pdo->prepare('SELECT nome, email, data_cadastro FROM usuarios WHERE id = :id');
    $stmt->execute(['id' => $usuarioId]);
    $usuario = $stmt->fetch();

    $stmt = $pdo->prepare(
        'SELECT p.id, p.nome, p.preco, p.estoque, p.ativo, p.imagem, c.nome AS categoria_nome
           FROM produtos p
      LEFT JOIN categorias c ON c.id = p.categoria_id
          WHERE p.vendedor_id = :v
       ORDER BY p.data_cadastro DESC, p.id DESC'
    );
    $stmt->execute(['v' => $usuarioId]);
    $meusProdutos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    flash('erro', 'Não foi possível carregar sua conta agora.');
}

if (!$usuario) {
    // Conta removida do banco: encerra a sessão.
    redirecionar('logout.php');
}

$titulo = 'Minha conta';
$capiContexto = $meusProdutos ? 'conta' : 'vender';
include __DIR__ . '/../includes/header.php';
?>
<main class="conteudo">
    <h1>Minha conta</h1>

    <section class="painel">
        <h2>Meus dados</h2>
        <p><strong>Nome:</strong> <?= e($usuario['nome']) ?></p>
        <p><strong>E-mail:</strong> <?= e($usuario['email']) ?></p>
        <p><strong>Cliente desde:</strong> <?= e(date('d/m/Y', strtotime($usuario['data_cadastro']))) ?></p>
    </section>

    <section class="painel">
        <div class="painel-topo">
            <h2>Meus produtos</h2>
            <a class="btn" href="<?= url('pages/produto-form.php') ?>">+ Anunciar produto</a>
        </div>

        <?php if (!$meusProdutos): ?>
            <p class="vazio">Você ainda não anunciou nada. Que tal começar agora?</p>
        <?php else: ?>
            <div class="tabela-rolagem">
                <table class="tabela">
                    <thead>
                        <tr><th>Produto</th><th>Categoria</th><th>Preço</th><th>Estoque</th><th>Status</th><th>Ações</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($meusProdutos as $p): ?>
                        <tr>
                            <td data-rotulo="Produto">
                                <img class="miniatura" src="<?= imagem_produto($p['imagem']) ?>" alt="">
                                <a href="<?= url('pages/produto.php?id=' . (int) $p['id']) ?>"><?= e($p['nome']) ?></a>
                            </td>
                            <td data-rotulo="Categoria"><?= e($p['categoria_nome'] ?? '—') ?></td>
                            <td data-rotulo="Preço"><?= e(preco_formatado($p['preco'])) ?></td>
                            <td data-rotulo="Estoque"><?= (int) $p['estoque'] ?></td>
                            <td data-rotulo="Status"><?= $p['ativo'] ? 'Ativo' : 'Pausado' ?></td>
                            <td data-rotulo="Ações" class="acoes">
                                <a href="<?= url('pages/produto-form.php?id=' . (int) $p['id']) ?>">Editar</a>
                                <form method="POST" action="<?= url('pages/produto-excluir.php') ?>"
                                      data-confirmar="Remover &quot;<?= e($p['nome']) ?>&quot;? Essa ação não pode ser desfeita.">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                    <button type="submit" class="link-perigo">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
