<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

exigir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar('pages/minha-conta.php');
}

if (!csrf_valido()) {
    flash('erro', 'Sessão expirada. Tente novamente.');
    redirecionar('pages/minha-conta.php');
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$usuarioId = (int) $_SESSION['usuario_id'];

if ($id) {
    try {
        $stmt = $pdo->prepare('SELECT imagem FROM produtos WHERE id = :id AND vendedor_id = :v');
        $stmt->execute(['id' => $id, 'v' => $usuarioId]);
        $produto = $stmt->fetch();

        if ($produto) {
            $pdo->prepare('DELETE FROM produtos WHERE id = :id AND vendedor_id = :v')
                ->execute(['id' => $id, 'v' => $usuarioId]);
            if (!empty($produto['imagem'])) {
                @unlink(__DIR__ . '/../assets/img/produtos/' . basename($produto['imagem']));
            }
            flash('sucesso', 'Produto removido.');
        } else {
            flash('erro', 'Produto não encontrado.');
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        flash('erro', 'Não foi possível remover o produto.');
    }
}

redirecionar('pages/minha-conta.php');
