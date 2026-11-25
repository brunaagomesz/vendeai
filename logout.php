<?php
require_once __DIR__ . '/includes/functions.php';

// Só aceita sair pelo botão do menu (POST com o token).
// Se alguém abrir o endereço direto ou vier de outro site, só volta para a página inicial.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    redirecionar('index.php');
}

// Encerra a sessão do usuário atual e volta para a página inicial.
encerrar_sessao();

flash('sucesso', 'Você saiu da sua conta. Até logo!');
redirecionar('index.php');
