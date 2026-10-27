<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

exigir_login();

$usuarioId = (int) $_SESSION['usuario_id'];
$id = filter_var($_GET['id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: null;
$editando = $id !== null;

$produto = [
    'nome' => '', 'descricao' => '', 'preco' => '', 'estoque' => '1',
    'categoria_id' => '', 'imagem' => null, 'ativo' => 1,
];

try {
    $categorias = $pdo->query('SELECT id, nome FROM categorias ORDER BY nome')->fetchAll();

    if ($editando) {
        $stmt = $pdo->prepare('SELECT * FROM produtos WHERE id = :id AND vendedor_id = :v');
        $stmt->execute(['id' => $id, 'v' => $usuarioId]);
        $existente = $stmt->fetch();
        if (!$existente) {
            flash('erro', 'Produto não encontrado ou você não tem permissão para editá-lo.');
            redirecionar('pages/minha-conta.php');
        }
        $produto = $existente;
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    flash('erro', 'Não foi possível carregar o formulário agora.');
    redirecionar('pages/minha-conta.php');
}

$erros = [];
$capiContexto = 'vender';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $precoTexto = trim($_POST['preco'] ?? '');
    $estoqueTexto = trim($_POST['estoque'] ?? '');
    $categoriaId = filter_var($_POST['categoria_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
    $ativo = isset($_POST['ativo']) || !$editando ? 1 : 0;

    // Mantém o que a pessoa digitou ao reexibir o formulário.
    $produto = array_merge($produto, [
        'nome' => $nome, 'descricao' => $descricao, 'preco' => $precoTexto,
        'estoque' => $estoqueTexto, 'categoria_id' => $categoriaId, 'ativo' => $ativo,
    ]);

    if (!csrf_valido()) {
        $erros[] = 'Sessão expirada. Recarregue a página e tente novamente.';
    }
    if ($nome === '' || mb_strlen($nome) > 150) {
        $erros[] = 'Informe o nome do produto (até 150 caracteres).';
    }
    if (mb_strlen($descricao) > 5000) {
        $erros[] = 'A descrição pode ter no máximo 5000 caracteres.';
    }

    // Aceita "149,90", "1.249,90" e "149.90".
    $precoNormalizado = str_replace(' ', '', $precoTexto);
    if (strpos($precoNormalizado, ',') !== false) {
        $precoNormalizado = str_replace('.', '', $precoNormalizado);
        $precoNormalizado = str_replace(',', '.', $precoNormalizado);
    }
    if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $precoNormalizado)) {
        $erros[] = 'Informe um preço válido, por exemplo 149,90.';
    }

    if (!preg_match('/^\d{1,6}$/', $estoqueTexto)) {
        $erros[] = 'Informe o estoque como um número inteiro (0 ou mais).';
    }

    if ($categoriaId !== null) {
        $ids = array_map('intval', array_column($categorias, 'id'));
        if (!in_array($categoriaId, $ids, true)) {
            $erros[] = 'Categoria inválida.';
        }
    }

    // Upload da imagem (opcional)
    $novaImagem = null;
    if (!empty($_FILES['imagem']['name'])) {
        $arq = $_FILES['imagem'];
        if ($arq['error'] !== UPLOAD_ERR_OK) {
            $erros[] = 'Não foi possível enviar a imagem. Tente outro arquivo.';
        } elseif ($arq['size'] > 2 * 1024 * 1024) {
            $erros[] = 'A imagem deve ter no máximo 2 MB.';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($arq['tmp_name']);
            $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($permitidos[$mime]) || @getimagesize($arq['tmp_name']) === false) {
                $erros[] = 'Use uma imagem JPG, PNG ou WEBP.';
            } else {
                $novaImagem = bin2hex(random_bytes(16)) . '.' . $permitidos[$mime];
            }
        }
    }

    if (!$erros) {
        try {
            $dir = __DIR__ . '/../assets/img/produtos/';
            if ($novaImagem !== null) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $dir . $novaImagem)) {
                    throw new RuntimeException('Falha ao salvar a imagem enviada.');
                }
            }

            $dados = [
                'categoria_id' => $categoriaId,
                'nome' => $nome,
                'descricao' => $descricao !== '' ? $descricao : null,
                'preco' => $precoNormalizado,
                'estoque' => (int) $estoqueTexto,
            ];

            if ($editando) {
                $sql = 'UPDATE produtos
                           SET categoria_id = :categoria_id, nome = :nome, descricao = :descricao,
                               preco = :preco, estoque = :estoque, ativo = :ativo'
                     . ($novaImagem !== null ? ', imagem = :imagem' : '')
                     . ' WHERE id = :id AND vendedor_id = :vendedor_id';
                $dados['ativo'] = $ativo;
                $dados['id'] = $id;
                $dados['vendedor_id'] = $usuarioId;
                if ($novaImagem !== null) {
                    $dados['imagem'] = $novaImagem;
                }
                $pdo->prepare($sql)->execute($dados);

                // Remove a imagem antiga depois que a nova foi gravada.
                if ($novaImagem !== null && !empty($existente['imagem'])) {
                    @unlink($dir . basename($existente['imagem']));
                }
                flash('sucesso', 'Tudo certo! Seu produto foi atualizado.');
            } else {
                $sql = 'INSERT INTO produtos
                            (categoria_id, vendedor_id, nome, descricao, preco, imagem, estoque)
                        VALUES (:categoria_id, :vendedor_id, :nome, :descricao, :preco, :imagem, :estoque)';
                $dados['vendedor_id'] = $usuarioId;
                $dados['imagem'] = $novaImagem;
                $pdo->prepare($sql)->execute($dados);
                flash('sucesso', 'Tudo certo! Seu produto já está no catálogo.');
            }

            redirecionar('pages/minha-conta.php');
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $erros[] = 'Não foi possível salvar o produto. Tente novamente.';
        }
    }

    if ($erros) {
        $capiContexto = 'erro';
    }
}

$titulo = $editando ? 'Editar produto' : 'Anunciar produto';
include __DIR__ . '/../includes/header.php';
?>
<main class="cadastro-container">
    <section class="cadastro-card card-largo">
        <h1><?= $editando ? 'Editar produto' : 'Anunciar produto' ?></h1>
        <p>Preencha os dados abaixo. Fotos nítidas e uma boa descrição vendem mais!</p>

        <?php if ($erros): ?>
            <div class="mensagem erro" role="alert">
                <ul>
                    <?php foreach ($erros as $erro): ?><li><?= e($erro) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('pages/produto-form.php' . ($editando ? '?id=' . (int) $id : '')) ?>" enctype="multipart/form-data">
            <?= csrf_campo() ?>
            <?php if ($editando): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>

            <label for="nome">Nome do produto</label>
            <input type="text" id="nome" name="nome" maxlength="150" value="<?= e($produto['nome']) ?>" required>

            <label for="categoria_id">Categoria</label>
            <select id="categoria_id" name="categoria_id">
                <option value="">Sem categoria</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (int) $produto['categoria_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
                <?php endforeach; ?>
            </select>

            <div class="duas-colunas">
                <div>
                    <label for="preco">Preço (R$)</label>
                    <input type="text" id="preco" name="preco" inputmode="decimal" placeholder="149,90"
                           value="<?= e((string) $produto['preco']) ?>" required>
                </div>
                <div>
                    <label for="estoque">Estoque</label>
                    <input type="number" id="estoque" name="estoque" min="0" max="999999"
                           value="<?= e((string) $produto['estoque']) ?>" required>
                </div>
            </div>

            <label for="descricao">Descrição</label>
            <textarea id="descricao" name="descricao" rows="5" maxlength="5000"><?= e($produto['descricao']) ?></textarea>

            <label for="imagem">Foto do produto (JPG, PNG ou WEBP, até 2 MB)</label>
            <?php if ($editando && $produto['imagem']): ?>
                <img class="previa-imagem" src="<?= imagem_produto($produto['imagem']) ?>" alt="Foto atual do produto">
            <?php endif; ?>
            <input type="file" id="imagem" name="imagem" accept="image/jpeg,image/png,image/webp">

            <?php if ($editando): ?>
                <label class="check">
                    <input type="checkbox" name="ativo" value="1" <?= $produto['ativo'] ? 'checked' : '' ?>>
                    Anúncio ativo (visível no catálogo)
                </label>
            <?php endif; ?>

            <button type="submit" class="btn"><?= $editando ? 'Salvar alterações' : 'Publicar produto' ?></button>
            <a class="btn btn-claro" href="<?= url('pages/minha-conta.php') ?>">Cancelar</a>
        </form>
    </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
