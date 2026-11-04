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

            // Conta criada: volta para o login com o e-mail já preenchido
            flash('sucesso', 'Conta criada! Agora é só entrar com seu e-mail e senha.');
            $_SESSION['email_cadastrado'] = $email;
            redirecionar('login.php');
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
    }
}

// O que o Capi fala no balão depende do que aconteceu
$fala = 'Oi! Eu sou o Capi. Vamos criar sua conta?';
if ($tipoMensagem === 'erro') {
    $fala = 'Quase lá! Dá uma olhada na mensagem em vermelho.';
}

$titulo = 'Criar conta';
$esconderCapi = true; // o Capi já aparece grande nesta página, então o pequeno do canto sai
include __DIR__ . '/includes/header.php';
?>
<main class="login-tela">
    <!-- Lado do mascote: o Capi com o balão de fala -->
    <section class="login-mascote" aria-label="Capi, o mascote do VendeAí">
        <p class="login-balao"><?= e($fala) ?></p>
        <img class="login-capivara" src="<?= url('assets/img/capi/capivara.webp') ?>"
             alt="Capi, a capivara mascote do VendeAí, fazendo joinha" width="600" height="968">
    </section>

    <!-- Lado do formulário -->
    <section class="cadastro-card">
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
