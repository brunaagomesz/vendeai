<?php
// Recebe as ações do carrinho (somente POST + CSRF) e volta para a página certa.
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/carrinho.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    flash('erro', 'Não foi possível concluir a ação. Tente novamente.');
    redirecionar('pages/carrinho.php');
}

$acao = $_POST['acao'] ?? '';
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

// Para onde voltar (só aceita destinos conhecidos, nunca um endereço vindo do formulário).
$destino = 'pages/carrinho.php';
if (($_POST['destino'] ?? '') === 'produto' && $id) {
    $destino = 'pages/produto.php?id=' . $id;
}

function buscar_produto_ativo(PDO $pdo, int $id): ?array
{
    try {
        $stmt = $pdo->prepare('SELECT id, nome, estoque, vendedor_id FROM produtos WHERE id = :id AND ativo = 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return null;
    }
}

switch ($acao) {
    case 'adicionar':
        $qtd = filter_var($_POST['qtd'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => CARRINHO_MAX_POR_ITEM]]);
        $produto = $id ? buscar_produto_ativo($pdo, $id) : null;

        if (!$qtd) {
            flash('erro', 'Escolha uma quantidade entre 1 e ' . CARRINHO_MAX_POR_ITEM . '.');
            $destino = $id ? 'pages/produto.php?id=' . $id : 'pages/catalogo.php';
        } elseif (!$produto) {
            flash('erro', 'Esse produto não está disponível.');
            $destino = 'pages/catalogo.php';
        } elseif (usuario_logado() && (int) $produto['vendedor_id'] === (int) $_SESSION['usuario_id']) {
            flash('erro', 'Você não pode comprar o seu próprio anúncio.');
            $destino = 'pages/produto.php?id=' . $id;
        } elseif ((int) $produto['estoque'] < 1) {
            flash('erro', 'Produto esgotado.');
            $destino = 'pages/produto.php?id=' . $id;
        } else {
            $atual = carrinho_bruto()[$id] ?? 0;
            $novaQtd = $atual + $qtd;
            $estoque = (int) $produto['estoque'];
            if ($novaQtd > $estoque) {
                $novaQtd = $estoque;
                flash('erro', 'Só temos ' . $estoque . ' em estoque. Colocamos esse máximo no carrinho.');
            } else {
                flash('sucesso', '"' . $produto['nome'] . '" foi para o carrinho.');
            }
            carrinho_definir($id, $novaQtd);
        }
        break;

    case 'atualizar':
        $qtd = filter_var($_POST['qtd'] ?? null, FILTER_VALIDATE_INT);
        $delta = filter_var($_POST['delta'] ?? 0, FILTER_VALIDATE_INT);
        $produto = $id ? buscar_produto_ativo($pdo, $id) : null;
        if ($id && $qtd !== false && $qtd !== null && $delta !== false) {
            $nova = $qtd + $delta;
            if ($produto && $nova > (int) $produto['estoque']) {
                $nova = (int) $produto['estoque'];
                flash('erro', 'Só temos ' . $nova . ' em estoque desse produto.');
            }
            carrinho_definir($id, $produto ? $nova : 0);
        } else {
            flash('erro', 'Quantidade inválida.');
        }
        break;

    case 'remover':
        if ($id) {
            carrinho_definir($id, 0);
            flash('sucesso', 'Item removido do carrinho.');
        }
        break;

    case 'esvaziar':
        carrinho_esvaziar();
        unset($_SESSION['frete_opcao']);
        flash('sucesso', 'Carrinho esvaziado.');
        break;

    case 'frete':
        $cep = cep_normalizar($_POST['cep'] ?? '');
        if ($cep === null) {
            flash('erro', 'CEP inválido. Digite os 8 números, por exemplo 01310-100.');
        } else {
            $_SESSION['frete_cep'] = $cep;
            $_SESSION['frete_opcao'] = 'economico';
        }
        break;

    case 'escolher_frete':
        $opcao = $_POST['opcao'] ?? '';
        if (in_array($opcao, ['economico', 'expresso'], true)) {
            $_SESSION['frete_opcao'] = $opcao;
        }
        break;
}

redirecionar($destino);
