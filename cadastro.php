<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (usuario_logado()) {
    redirecionar('pages/minha-conta.php');
}

$mensagem = '';
$tipoMensagem = '';
$nome = '';
$email = '';
$capiContexto = 'cadastro';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (!csrf_valido()) {
        $mensagem = 'Sessão expirada. Recarregue a página e tente novamente.';
        $tipoMensagem = 'erro';
    } elseif ($nome === '' || mb_strlen($nome) > 100) {
        $mensagem = 'Informe um nome válido.';
        $tipoMensagem = 'erro';
    } elseif (
        strlen($email) > 150 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        $mensagem = 'Informe um e-mail válido.';
        $tipoMensagem = 'erro';
    } elseif (strlen($senha) < 8) {
        $mensagem = 'A senha deve ter pelo menos 8 caracteres.';
        $tipoMensagem = 'erro';
    } else {
        try {
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

            $sql = "INSERT INTO usuarios
                        (nome, email, senha_hash)
                    VALUES (:nome, :email, :senha_hash)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'nome' => $nome,
                'email' => $email,
                'senha_hash' => $senhaHash,
            ]);

            $mensagem = 'Cadastro realizado com sucesso! Bem-vindo ao VendeAí.';
            $tipoMensagem = 'sucesso';
            $nome = '';
            $email = '';
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $mensagem = 'Este e-mail já está cadastrado.';
                $tipoMensagem = 'erro';
            } else {
                error_log($e->getMessage());
                $mensagem = 'Não foi possível concluir o cadastro. Tente novamente.';
                $tipoMensagem = 'erro';
            }
        }
    }

    if ($tipoMensagem === 'erro') {
        $capiContexto = 'erro';
    } elseif ($tipoMensagem === 'sucesso') {
        $capiContexto = 'sucesso';
    }
}

$titulo = 'Criar conta';
include __DIR__ . '/includes/header.php';
?>
<main class="cadastro-container">
    <section class="cadastro-card">
        <div class="capi">
            <img src="<?= url('assets/img/capi/capi-feliz.svg') ?>" alt="" width="96" height="96">
            <p>Oi! Eu sou o Capi. Vamos criar sua conta?</p>
        </div>

        <h1>Crie sua conta</h1>
        <p>Entre para descobrir (e vender) tudo no VendeAí.</p>

        <?php if ($mensagem !== ''): ?>
            <p class="mensagem <?= e($tipoMensagem) ?>" role="status"><?= e($mensagem) ?></p>
        <?php endif; ?>

        <form method="POST" action="<?= url('cadastro.php') ?>">
            <?= csrf_campo() ?>

            <label for="nome">Nome completo</label>
            <input type="text" id="nome" name="nome" maxlength="100"
                   autocomplete="name" value="<?= e($nome) ?>" required>

            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" maxlength="150"
                   autocomplete="email" value="<?= e($email) ?>" required>

            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" minlength="8"
                   autocomplete="new-password" required>
            <small class="ajuda">Mínimo de 8 caracteres.</small>

            <button type="submit" class="btn">Criar minha conta</button>
        </form>

        <p>Já possui uma conta? <a href="<?= url('login.php') ?>">Fazer login</a></p>
    </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
