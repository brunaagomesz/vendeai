<?php
require_once __DIR__ . '/includes/functions.php';

// Encerra a sessão do usuário atual e volta para a página inicial.
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

session_destroy();

session_start();
flash('sucesso', 'Você saiu da sua conta. Até logo!');
redirecionar('index.php');
