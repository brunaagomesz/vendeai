<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (usuario_logado()) {
    redirecionar('pages/minha-conta.php');
}

$mensagem = '';
$email = '';
$capiContexto = 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (!csrf_valido()) {
        $mensagem = 'Sessão expirada. Recarregue a página e tente novamente.';
    } elseif ($email === '' || $senha === '') {
        $mensagem = 'Informe e-mail e senha.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'SELECT id, nome, senha_hash FROM usuarios WHERE email = :email LIMIT 1'
            );
            $stmt->execute(['email' => $email]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
                session_regenerate_id(true);
                $_SESSION['usuario_id'] = (int) $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                if (password_needs_rehash($usuario['senha_hash'], PASSWORD_DEFAULT)) {
                    $novo = $pdo->prepare('UPDATE usuarios SET senha_hash = :h WHERE id = :id');
                    $novo->execute(['h' => password_hash($senha, PASSWORD_DEFAULT), 'id' => $usuario['id']]);
                }

                flash('sucesso', 'Que bom ter você de volta, ' . explode(' ', $usuario['nome'])[0] . '!');
                redirecionar('pages/minha-conta.php');
            }

            // Mesma mensagem para e-mail inexistente e senha errada.
            $mensagem = 'E-mail ou senha incorretos.';
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $mensagem = 'Não foi possível entrar agora. Tente novamente.';
        }
    }

    if ($mensagem !== '') {
        $capiContexto = 'erro';
    }
}

$titulo = 'Entrar';
include __DIR__ . '/includes/header.php';
?>
<main class="cadastro-container">
    <section class="cadastro-card">
        <div class="capi">
            <img src="<?= url('assets/img/capi/capi-feliz.svg') ?>" alt="" width="96" height="96">
            <p>Que bom ter você de volta!</p>
        </div>

        <h1>Entrar</h1>
        <p>Acesse sua conta para comprar e vender.</p>

        <?php if ($mensagem !== ''): ?>
            <p class="mensagem erro" role="status"><?= e($mensagem) ?></p>
        <?php endif; ?>

        <form method="POST" action="<?= url('login.php') ?>">
            <?= csrf_campo() ?>

            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" maxlength="150"
                   autocomplete="email" value="<?= e($email) ?>" required>

            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha"
                   autocomplete="current-password" required>

            <button type="submit" class="btn">Entrar</button>
        </form>

        <p>Ainda não tem conta? <a href="<?= url('cadastro.php') ?>">Criar conta</a></p>
    </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
