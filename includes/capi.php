<?php
// Assistente virtual Capi: botão redondo amarelo + balão com mensagens controladas por JavaScript.
// Não há IA: o texto vem de contextos fixos (data-contexto) e dicas pré-definidas.
// Contextos: geral, cadastro, login, busca, erro, sucesso, vazio, vender, conta
$capiContexto = $capiContexto ?? 'geral';
?>
<aside class="capi-widget" id="capi" data-contexto="<?= e($capiContexto) ?>" aria-live="polite">
    <div class="capi-balao" id="capi-balao" hidden>
        <button type="button" class="capi-fechar" id="capi-fechar" aria-label="Fechar mensagem do Capi">×</button>
        <strong class="capi-nome">Capi</strong>
        <p id="capi-texto"></p>
        <button type="button" class="capi-dica" id="capi-dica">Outra dica</button>
    </div>
    <!-- Círculo amarelo com um ícone de conversa (antes era a imagem do Capi) -->
    <button type="button" class="capi-botao" id="capi-botao" aria-label="Falar com o Capi">
        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16v11H9l-5 4z"/><path d="M8.5 10.5h.01M12 10.5h.01M15.5 10.5h.01"/></svg>
    </button>
</aside>
