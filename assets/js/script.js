// VendeAí • Pequenas interações do site:
//   1. Menu lateral
//   2. Capi (mensagens controladas, sem IA)
//   3. Botões − e + da quantidade (página do produto)
//   4. Vídeo do Capi parado para quem pede menos movimento
//   5. Confirmação antes de excluir produto
// Cada parte é independente: se a página não tiver aquele pedaço, ele é ignorado.
(function () {
    'use strict';

    // ---------- 1. Menu lateral ----------
    // Abrir = colocar a classe "menu-aberto" no body; fechar = tirar. O CSS faz o resto.
    var botaoMenu = document.getElementById('botao-menu');
    var menu = document.getElementById('menu-lateral');
    var fecharMenuBtn = document.getElementById('fechar-menu');
    var fundoMenu = document.getElementById('fundo-menu');

    if (botaoMenu && menu) {
        var abrirMenu = function () {
            document.body.classList.add('menu-aberto');
            botaoMenu.setAttribute('aria-expanded', 'true');
            menu.setAttribute('aria-hidden', 'false');
            fecharMenuBtn.focus();
        };
        var fecharMenu = function () {
            document.body.classList.remove('menu-aberto');
            botaoMenu.setAttribute('aria-expanded', 'false');
            menu.setAttribute('aria-hidden', 'true');
            botaoMenu.focus();
        };

        botaoMenu.addEventListener('click', abrirMenu);
        fecharMenuBtn.addEventListener('click', fecharMenu);   // fecha no X
        fundoMenu.addEventListener('click', fecharMenu);       // fecha no fundo escuro
        document.addEventListener('keydown', function (ev) {   // fecha com Esc
            if (ev.key === 'Escape' && document.body.classList.contains('menu-aberto')) {
                fecharMenu();
            }
        });
    }

    // ---------- 2. Capi ----------
    // Mensagens por situação (tom: simpático, curto e claro).
    var CONTEXTOS = {
        geral:    'Oi! Eu sou o Capi. Precisa de ajuda para comprar ou vender?',
        cadastro: 'Oi! Vamos criar sua conta?',
        login:    'Que bom ter você de volta!',
        busca:    'O que você está procurando hoje?',
        erro:     'Ops! Confira esse campo e tente novamente.',
        sucesso:  'Tudo certo! Seu cadastro foi realizado.',
        vazio:    'Ainda não encontrei produtos por aqui.',
        vender:   'Vamos anunciar? Uma boa foto e um preço claro ajudam muito!',
        conta:    'Aqui você acompanha e edita os seus anúncios.'
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
    if (widget) {
        var balao = document.getElementById('capi-balao');
        var texto = document.getElementById('capi-texto');
        var botao = document.getElementById('capi-botao');
        var contextoNome = widget.getAttribute('data-contexto');
        var mensagem = CONTEXTOS[contextoNome] || CONTEXTOS.geral;
        var indiceDica = Math.floor(Math.random() * DICAS.length);

        var mostrar = function (frase) {
            texto.textContent = frase;   // textContent: nunca interpreta HTML
            balao.hidden = false;
        };

        // O botão amarelo abre e fecha o balão
        botao.addEventListener('click', function () {
            if (balao.hidden) {
                mostrar(mensagem);
            } else {
                balao.hidden = true;
            }
        });

        document.getElementById('capi-fechar').addEventListener('click', function () {
            balao.hidden = true;
            botao.focus();
        });

        document.getElementById('capi-dica').addEventListener('click', function () {
            indiceDica = (indiceDica + 1) % DICAS.length;
            mostrar(DICAS[indiceDica]);
        });

        // Mensagem inicial: abre sozinha uma vez por contexto e sessão, sem atrapalhar a navegação.
        var chave = 'capi-visto-' + contextoNome;
        var jaVisto = false;
        try { jaVisto = sessionStorage.getItem(chave) === '1'; } catch (e) { /* ignora */ }

        var importante = ['erro', 'sucesso', 'vazio'].indexOf(contextoNome) !== -1;
        if (importante || !jaVisto) {
            mostrar(mensagem);
            try { sessionStorage.setItem(chave, '1'); } catch (e) { /* ignora */ }
        }
    }

    // ---------- 3. Botões − e + da quantidade ----------
    // Só mudam o número do campo, respeitando o mínimo e o máximo (estoque).
    // O formulário continua enviando o campo "qtd" do mesmo jeito.
    document.querySelectorAll('[data-qtd]').forEach(function (caixa) {
        var campo = caixa.querySelector('input');
        caixa.querySelectorAll('[data-passo]').forEach(function (b) {
            b.addEventListener('click', function () {
                var min = parseInt(campo.min, 10) || 1;
                var max = parseInt(campo.max, 10) || 99;
                var valor = (parseInt(campo.value, 10) || min) + parseInt(b.getAttribute('data-passo'), 10);
                campo.value = Math.min(max, Math.max(min, valor));
            });
        });
    });

    // ---------- 4. Vídeo do Capi ----------
    // Quem ativou "reduzir movimento" no sistema vê o vídeo parado (só a primeira imagem).
    var video = document.getElementById('capi-video');
    if (video && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        video.removeAttribute('autoplay');
        video.pause();
    }

    // ---------- 5. Confirmação antes de excluir produto ----------
    // Janela própria, no estilo do site.
    function confirmarExclusao(aoConfirmar) {
        var anterior = document.activeElement;
        var fundo = document.createElement('div');
        fundo.className = 'modal-fundo';
        fundo.innerHTML =
            '<div class="modal" role="alertdialog" aria-modal="true" aria-labelledby="modal-titulo" aria-describedby="modal-desc">' +
            '<div class="modal-icone" aria-hidden="true">!</div>' +
            '<h2 id="modal-titulo">Tem certeza que deseja excluir este item?</h2>' +
            '<p id="modal-desc">Essa ação não pode ser desfeita.</p>' +
            '<div class="modal-botoes">' +
            '<button type="button" class="btn btn-claro" data-acao="cancelar">Cancelar</button>' +
            '<button type="button" class="btn btn-perigo" data-acao="confirmar">Sim, excluir</button>' +
            '</div></div>';

        function fechar() {
            document.removeEventListener('keydown', teclado);
            fundo.remove();
            if (anterior && anterior.focus) { anterior.focus(); }
        }
        function teclado(ev) {
            if (ev.key === 'Escape') { fechar(); }
        }

        fundo.addEventListener('click', function (ev) {
            if (ev.target === fundo) { fechar(); }
            var acao = ev.target.getAttribute && ev.target.getAttribute('data-acao');
            if (acao === 'cancelar') { fechar(); }
            if (acao === 'confirmar') { fechar(); aoConfirmar(); }
        });
        document.addEventListener('keydown', teclado);
        document.body.appendChild(fundo);
        fundo.querySelector('[data-acao="cancelar"]').focus();
    }

    document.querySelectorAll('form[data-confirmar]').forEach(function (form) {
        form.addEventListener('submit', function (ev) {
            ev.preventDefault();
            confirmarExclusao(function () { form.submit(); });
        });
    });
})();
