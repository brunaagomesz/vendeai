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
// O session_start acima reabre a sessão com o código antigo, que o navegador acabou de apagar.
// Gerar um código novo faz o navegador receber a chave certa e encontrar o aviso abaixo.
session_regenerate_id(true);

flash('sucesso', 'Você saiu da sua conta. Até logo!');
redirecionar('index.php');

