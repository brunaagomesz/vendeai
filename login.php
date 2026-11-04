<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (usuario_logado()) {
    redirecionar('pages/minha-conta.php');
}

// Limite de tentativas: depois de 5 senhas erradas, o login fica bloqueado por 30 segundos.
// A contagem fica na sessão, então vale para este navegador.
const MAX_TENTATIVAS = 5;
const SEGUNDOS_BLOQUEIO = 30;

if (!isset($_SESSION['tentativas_login'])) {
    $_SESSION['tentativas_login'] = 0;
}

// Confere se existe um bloqueio e se ele já terminou
$segundosRestantes = 0;
if (isset($_SESSION['bloqueado_ate'])) {
    $segundosRestantes = $_SESSION['bloqueado_ate'] - time();
    if ($segundosRestantes <= 0) {
        // O tempo passou: libera de novo e zera as tentativas
        unset($_SESSION['bloqueado_ate']);
        $_SESSION['tentativas_login'] = 0;
        $segundosRestantes = 0;
    }
}

$mensagem = '';
$email = '';
$capiContexto = 'login';

// Veio do cadastro? O e-mail da conta nova já aparece preenchido (uma vez só)
$contaCriada = isset($_SESSION['email_cadastrado']);
if ($contaCriada) {
    $email = $_SESSION['email_cadastrado'];
    unset($_SESSION['email_cadastrado']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (!csrf_valido()) {
        $mensagem = 'Sessão expirada. Recarregue a página e tente novamente.';
    } elseif ($segundosRestantes > 0) {
        // Bloqueado: nem consulta o banco. A mensagem é montada mais abaixo.
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
                unset($_SESSION['tentativas_login'], $_SESSION['bloqueado_ate']);
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

            // Senha errada (ou e-mail que não existe): conta mais uma tentativa
            $_SESSION['tentativas_login']++;

            if ($_SESSION['tentativas_login'] >= MAX_TENTATIVAS) {
                $_SESSION['bloqueado_ate'] = time() + SEGUNDOS_BLOQUEIO;
                $segundosRestantes = SEGUNDOS_BLOQUEIO;
            } else {
                // Mesma mensagem para e-mail inexistente e senha errada, para não ajudar quem tenta adivinhar contas
                $mensagem = 'E-mail ou senha incorretos. Tentativa '
                    . $_SESSION['tentativas_login'] . ' de ' . MAX_TENTATIVAS . '.';
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $mensagem = 'Não foi possível entrar agora. Tente novamente.';
        }
    }

    if ($mensagem !== '') {
        $capiContexto = 'erro';
    }
}

// Bloqueado (mesmo só abrindo a página): mostra quanto tempo falta
if ($segundosRestantes > 0) {
    $mensagem = 'Muitas tentativas erradas. Espere ' . $segundosRestantes . ' segundos para tentar de novo.';
    $capiContexto = 'erro';
}

// O que o Capi fala no balão depende do que aconteceu
$fala = 'Que bom ter você de volta!';
if ($segundosRestantes > 0) {
    $fala = 'Vamos respirar um pouco? Daqui a pouquinho você tenta de novo.';
} elseif ($mensagem !== '') {
    $fala = 'Hmm, algo não bateu. Confere o e-mail e a senha?';
} elseif ($contaCriada) {
    $fala = 'Conta criada! Agora é só digitar sua senha.';
}

$titulo = 'Entrar';
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
