// VendeAí • Capi (mensagens controladas, sem IA) e pequenas interações.
(function () {
    'use strict';

    // Mensagens por situação (tom: simpático, curto e claro) + expressão do Capi.
    var CONTEXTOS = {
        geral:    { msg: 'Oi! Eu sou o Capi. Precisa de ajuda para comprar ou vender?', humor: 'feliz' },
        cadastro: { msg: 'Oi! Vamos criar sua conta?', humor: 'feliz' },
        login:    { msg: 'Que bom ter você de volta!', humor: 'feliz' },
        busca:    { msg: 'O que você está procurando hoje?', humor: 'curioso' },
        erro:     { msg: 'Ops! Confira esse campo e tente novamente.', humor: 'pensativo' },
        sucesso:  { msg: 'Tudo certo! Seu cadastro foi realizado.', humor: 'comemorando' },
        vazio:    { msg: 'Ainda não encontrei produtos por aqui.', humor: 'pensativo' },
        vender:   { msg: 'Vamos anunciar? Uma boa foto e um preço claro ajudam muito!', humor: 'curioso' },
        conta:    { msg: 'Aqui você acompanha e edita os seus anúncios.', humor: 'feliz' }
    };

    // Dicas para quem está começando a vender (o botão "Outra dica" percorre a lista).
    var DICAS = [
        'Dica: use um título claro, com o nome e a característica principal do produto.',
        'Dica: fotos com boa luz e fundo limpo passam confiança.',
        'Dica: descreva tamanho, cor, material e estado do produto. Menos dúvidas, mais vendas!',
        'Dica: pesquise preços parecidos no catálogo antes de definir o seu.',
        'Dica: mantenha o estoque atualizado. Produto esgotado aparece como indisponível.',
        'Dica: está sem tempo? Pause o anúncio em "Meus produtos" sem precisar apagar.',
        'Dica: nunca compartilhe sua senha com ninguém, nem comigo!'
    ];

    var widget = document.getElementById('capi');
    if (!widget) { return; }

    var balao = document.getElementById('capi-balao');
    var texto = document.getElementById('capi-texto');
    var botao = document.getElementById('capi-botao');
    var fechar = document.getElementById('capi-fechar');
    var outraDica = document.getElementById('capi-dica');
    var img = document.getElementById('capi-img');

    var contexto = CONTEXTOS[widget.getAttribute('data-contexto')] || CONTEXTOS.geral;
    var indiceDica = Math.floor(Math.random() * DICAS.length);

    function mudarHumor(humor) {
        var src = img.getAttribute('data-' + humor);
        if (src) { img.src = src; }
    }

    function mostrar(mensagem, humor) {
        texto.textContent = mensagem;   // textContent: nunca interpreta HTML
        mudarHumor(humor);
        balao.hidden = false;
    }

    botao.addEventListener('click', function () {
        if (balao.hidden) {
            mostrar(contexto.msg, contexto.humor);
        } else {
            balao.hidden = true;
        }
    });

    fechar.addEventListener('click', function () {
        balao.hidden = true;
        botao.focus();
    });

    outraDica.addEventListener('click', function () {
        indiceDica = (indiceDica + 1) % DICAS.length;
        mostrar(DICAS[indiceDica], 'curioso');
    });

    // Mensagem inicial: abre sozinha uma vez por contexto e sessão, sem atrapalhar a navegação.
    var chave = 'capi-visto-' + widget.getAttribute('data-contexto');
    var jaVisto = false;
    try { jaVisto = sessionStorage.getItem(chave) === '1'; } catch (e) { /* ignora */ }

    var importante = ['erro', 'sucesso', 'vazio'].indexOf(widget.getAttribute('data-contexto')) !== -1;
    if (importante || !jaVisto) {
        mostrar(contexto.msg, contexto.humor);
        try { sessionStorage.setItem(chave, '1'); } catch (e) { /* ignora */ }
    }

    // Confirmação antes de excluir produto.
    document.querySelectorAll('form[data-confirmar]').forEach(function (form) {
        form.addEventListener('submit', function (ev) {
            if (!window.confirm(form.getAttribute('data-confirmar'))) {
                ev.preventDefault();
            }
        });
    });
})();
