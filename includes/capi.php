<?php
// Assistente virtual Capi: ilustração + mensagens controladas por JavaScript.
// Não há IA: o texto vem de contextos fixos (data-contexto) e dicas pré-definidas.
// Contextos: geral, cadastro, login, busca, erro, sucesso, vazio, vender, conta
$capiContexto = $capiContexto ?? 'geral';
?>
<aside class="capi-widget" id="capi" data-contexto="<?= e($capiContexto) ?>" aria-live="polite">
    <div class="capi-balao" id="capi-balao" hidden>
        <button type="button" class="capi-fechar" id="capi-fechar" aria-label="Fechar mensagem do Capi">×</button>
        <p id="capi-texto"></p>
        <button type="button" class="capi-dica" id="capi-dica">Outra dica</button>
    </div>
    <button type="button" class="capi-botao" id="capi-botao" aria-label="Falar com o Capi">
        <img src="<?= url('assets/img/capi/capi-feliz.svg') ?>" alt="Capi, a capivara mascote do VendeAí"
             id="capi-img" width="84" height="84"
             data-feliz="<?= url('assets/img/capi/capi-feliz.svg') ?>"
             data-curioso="<?= url('assets/img/capi/capi-curioso.svg') ?>"
             data-pensativo="<?= url('assets/img/capi/capi-pensativo.svg') ?>"
             data-comemorando="<?= url('assets/img/capi/capi-comemorando.svg') ?>">
    </button>
</aside>
