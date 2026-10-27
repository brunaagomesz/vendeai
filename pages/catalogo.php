<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/produto-card.php';

$titulo = 'Catálogo';
$capiContexto = 'busca';

$q = trim($_GET['q'] ?? '');
$q = mb_substr($q, 0, 100);
$categoriaId = filter_var($_GET['categoria'] ?? null, FILTER_VALIDATE_INT) ?: null;
$ordem = $_GET['ordem'] ?? 'recentes';
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
        $where[] = '(p.nome LIKE :q OR p.descricao LIKE :q)';
        // Escapa curingas do LIKE digitados pelo usuário.
        $params['q'] = '%' . addcslashes($q, '%_\\') . '%';
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

    $sql = "SELECT p.id, p.nome, p.preco, p.imagem, p.estoque, c.nome AS categoria_nome
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

include __DIR__ . '/../includes/header.php';
?>
<main class="conteudo">
    <h1>Catálogo</h1>

    <form class="filtros" method="GET" action="<?= url('pages/catalogo.php') ?>">
        <div>
            <label for="f-q">Buscar</label>
            <input type="search" id="f-q" name="q" maxlength="100" value="<?= e($q) ?>" placeholder="Nome ou descrição">
        </div>
        <div>
            <label for="f-cat">Categoria</label>
            <select id="f-cat" name="categoria">
                <option value="">Todas</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $categoriaId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="f-ordem">Ordenar por</label>
            <select id="f-ordem" name="ordem">
                <option value="recentes" <?= $ordem === 'recentes' ? 'selected' : '' ?>>Mais recentes</option>
                <option value="menor" <?= $ordem === 'menor' ? 'selected' : '' ?>>Menor preço</option>
                <option value="maior" <?= $ordem === 'maior' ? 'selected' : '' ?>>Maior preço</option>
                <option value="nome" <?= $ordem === 'nome' ? 'selected' : '' ?>>Nome (A–Z)</option>
            </select>
        </div>
        <button type="submit" class="btn">Filtrar</button>
    </form>

    <p class="resultado-info"><?= $total ?> produto<?= $total === 1 ? '' : 's' ?> encontrado<?= $total === 1 ? '' : 's' ?></p>

    <?php if (!$produtos): ?>
        <p class="vazio">Ainda não encontrei produtos por aqui.</p>
    <?php else: ?>
        <div class="grade-produtos">
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
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
