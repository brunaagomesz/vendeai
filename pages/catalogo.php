<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/produto-card.php';

$titulo = 'Catálogo';
$capiContexto = 'busca';

$q = campo_get('q');
$q = mb_substr($q, 0, 100);
$categoriaId = filter_var($_GET['categoria'] ?? null, FILTER_VALIDATE_INT) ?: null;
$ordem = campo_get('ordem');
$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$porPagina = 12;

$ordenacoes = [
    'recentes' => 'p.data_cadastro DESC, p.id DESC',
    'menor'    => 'p.preco ASC, p.id DESC',
    'maior'    => 'p.preco DESC, p.id DESC',
    'nome'     => 'p.nome ASC',
];
if (!isset($ordenacoes[$ordem])) {
    $ordem = 'recentes';
}

$produtos = [];
$categorias = [];
$total = 0; 

try {
    $categorias = $pdo->query('SELECT id, nome FROM categorias ORDER BY nome')->fetchAll();

    $where = ['p.ativo = 1'];
    $params = [];

       if ($q !== '') {
        // Cada marcador (:nome) só pode aparecer uma vez na consulta.
        // Por isso são dois marcadores diferentes, recebendo o mesmo texto.
        $where[] = '(p.nome LIKE :busca_nome OR p.descricao LIKE :busca_descricao)';

        // Os % em volta fazem o LIKE achar o texto em qualquer parte do nome ou da descrição.
        // O addcslashes impede que um % ou _ digitado pela pessoa vire curinga.
        $termo = '%' . addcslashes($q, '%_\\') . '%';
        $params['busca_nome'] = $termo;
        $params['busca_descricao'] = $termo;
    }
    if ($categoriaId) {
        $where[] = 'p.categoria_id = :categoria';
        $params['categoria'] = $categoriaId;
    }
    $whereSql = implode(' AND ', $where);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM produtos p WHERE $whereSql");
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    $totalPaginas = max(1, (int) ceil($total / $porPagina));
    $pagina = min($pagina, $totalPaginas);
    $offset = ($pagina - 1) * $porPagina;

    $sql = "SELECT p.id, p.nome, p.preco, p.imagem, p.estoque, p.data_cadastro, c.nome AS categoria_nome
              FROM produtos p
         LEFT JOIN categorias c ON c.id = p.categoria_id
             WHERE $whereSql
          ORDER BY {$ordenacoes[$ordem]}
             LIMIT $porPagina OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $produtos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    flash('erro', 'Não foi possível carregar os produtos agora.');
    $totalPaginas = 1;
}

if (!$produtos) {
    $capiContexto = 'vazio';
}

function link_catalogo(array $extra): string
{
    global $q, $categoriaId, $ordem;
    $base = ['q' => $q, 'categoria' => $categoriaId, 'ordem' => $ordem];
    $params = array_filter(array_merge($base, $extra), fn($v) => $v !== null && $v !== '' && $v !== 'recentes');
    return url('pages/catalogo.php') . ($params ? '?' . http_build_query($params) : '');
}

// Nome da categoria escolhida (para o título e o aviso de filtro ativo)
$categoriaNome = '';
foreach ($categorias as $c) {
    if ($categoriaId === (int) $c['id']) {
        $categoriaNome = $c['nome'];
    }
}

include __DIR__ . '/../includes/header.php';
?>
<main class="envolve pagina">
    <div class="catalogo">
        <!-- Coluna da esquerda: filtros. Usa os mesmos parâmetros de antes (q, categoria, ordem). -->
        <form class="filtros painel" method="GET" action="<?= url('pages/catalogo.php') ?>" aria-label="Filtros">
            <div>
                <label class="filtro-titulo" for="f-q">Buscar</label>
                <input type="search" id="f-q" name="q" maxlength="100" value="<?= e($q) ?>" placeholder="Nome ou descrição">
            </div>

            <fieldset>
                <legend class="filtro-titulo">Categoria</legend>
                <label class="opcao-filtro">
                    <input type="radio" name="categoria" value="" <?= !$categoriaId ? 'checked' : '' ?>> Todas
                </label>
                <?php foreach ($categorias as $c): ?>
                    <label class="opcao-filtro">
                        <input type="radio" name="categoria" value="<?= (int) $c['id'] ?>" <?= $categoriaId === (int) $c['id'] ? 'checked' : '' ?>>
                        <?= e($c['nome']) ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>

            <div>
                <label class="filtro-titulo" for="f-ordem">Ordenar por</label>
                <select id="f-ordem" name="ordem">
                    <option value="recentes" <?= $ordem === 'recentes' ? 'selected' : '' ?>>Mais recentes</option>
                    <option value="menor" <?= $ordem === 'menor' ? 'selected' : '' ?>>Menor preço</option>
                    <option value="maior" <?= $ordem === 'maior' ? 'selected' : '' ?>>Maior preço</option>
                    <option value="nome" <?= $ordem === 'nome' ? 'selected' : '' ?>>Nome (A a Z)</option>
                </select>
            </div>

            <button type="submit" class="btn btn-azul">Filtrar</button>
        </form>

        <!-- Coluna da direita: título, quantidade e produtos -->
        <section>
            <div class="cat-topo">
                <div>
                    <h1><?= $categoriaNome !== '' ? e($categoriaNome) : 'Catálogo' ?></h1>
                    <p><?= $total ?> produto<?= $total === 1 ? '' : 's' ?> encontrado<?= $total === 1 ? '' : 's' ?></p>
                </div>
            </div>

            <?php if ($q !== '' || $categoriaNome !== ''): ?>
                <div class="chips-ativos">
                    <?php if ($q !== ''): ?><span class="chip">Busca: <?= e($q) ?></span><?php endif; ?>
                    <?php if ($categoriaNome !== ''): ?><span class="chip"><?= e($categoriaNome) ?></span><?php endif; ?>
                    <a class="limpar-filtros" href="<?= url('pages/catalogo.php') ?>">Limpar filtros</a>
                </div>
            <?php endif; ?>

            <?php if (!$produtos): ?>
                <p class="vazio">Ainda não encontrei produtos por aqui.</p>
            <?php else: ?>
                <div class="grade">
                    <?php foreach ($produtos as $p) { renderizar_produto_card($p); } ?>
                </div>

                <?php if ($totalPaginas > 1): ?>
                    <nav class="paginacao" aria-label="Paginação">
                        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                            <a href="<?= e(link_catalogo(['pagina' => $i])) ?>"
                               class="<?= $i === $pagina ? 'ativa' : '' ?>"
                               <?= $i === $pagina ? 'aria-current="page"' : '' ?>><?= $i ?></a>
                        <?php endfor; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
