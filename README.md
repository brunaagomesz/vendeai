# VendeAí 🦫🛒

> **Encontrou, gostou, VendeAí!**

Marketplace acadêmico com cadastro de usuários, login, catálogo de produtos e
**cadastro de produtos por vendedores** (no estilo Mercado Livre / Shopee, em
versão simplificada). O mascote **Capi** (capivara) orienta quem está começando
a comprar ou a vender.

Projeto desenvolvido com **HTML, CSS, JavaScript, PHP (PDO) e MySQL**,
rodando localmente com **VS Code + XAMPP**.

## Funcionalidades

- Cadastro com validação no servidor e senha com `password_hash()`
- Login/logout com sessão (ID regenerado após login, cookie `HttpOnly`/`SameSite`)
- Proteção CSRF em todos os formulários
- Catálogo com busca, filtro por categoria, ordenação e paginação
- Página de detalhe do produto
- Painel do vendedor (**Minha conta**): anunciar, editar, pausar e excluir produtos
- Upload de foto (JPG/PNG/WEBP até 2 MB, validado por tipo real do arquivo)
- **Capi**: assistente flutuante com mensagens por situação e dicas para
  empreendedores, controlado por JavaScript (sem IA)

Fora do escopo desta versão (conforme o plano técnico): pagamento online,
transportadoras, chat com IA e gestão completa de vendedores.

## Como executar

1. Instale o [XAMPP](https://www.apachefriends.org/) e inicie **Apache** e **MySQL**.
2. Copie esta pasta para `C:\xampp\htdocs\vendeai`.
3. Abra `http://localhost/phpmyadmin`, vá na aba **SQL** e execute, nesta ordem:
   - `database/schema.sql` (cria o banco `vendeai_db` e as tabelas)
   - `database/seed.sql` (opcional: produtos de exemplo)
4. Se o usuário `root` do MySQL tiver senha, ajuste `$password` em `config/database.php`.
5. Acesse `http://localhost/vendeai/`.

> Abra sempre pelo `http://localhost/...`. Não abra arquivos `.php` direto do disco.
> Se a pasta tiver outro nome, altere `BASE_URL` em `includes/functions.php`.

### Roteiro de teste

1. Criar conta em `cadastro.php` e conferir a tabela `usuarios` (senha em hash).
2. Tentar cadastrar o mesmo e-mail de novo: deve aparecer "Este e-mail já está cadastrado."
3. Fazer login com senha errada (recusa) e depois correta (cria sessão).
4. Em **Vender**, anunciar um produto com foto e vê-lo no catálogo.
5. Testar busca, categoria e ordenação; editar, pausar e excluir o produto.

## Estrutura

```
vendeai/
├── index.php  cadastro.php  login.php  logout.php
├── config/database.php
├── includes/  header.php  footer.php  capi.php  functions.php  produto-card.php
├── pages/     catalogo.php  produto.php  produto-form.php
│              produto-excluir.php  minha-conta.php
├── assets/    css/style.css  js/script.js  img/capi/  img/produtos/
└── database/  schema.sql  seed.sql
```

## Identidade visual

| Elemento | Valor |
|---|---|
| Azul principal | `#2457C5` |
| Amarelo | `#FFD447` |
| Cinza claro | `#F5F7FA` |
| Grafite | `#263238` |
| Fontes | Poppins + Inter (Arial como alternativa) |

As ilustrações do Capi (`assets/img/capi/*.svg`) são uma versão inicial em vetor
(feliz, curioso, pensativo e comemorando). Podem ser trocadas pela arte 3D final
mantendo os mesmos nomes de arquivo.

## Segurança (resumo)

Consultas preparadas (PDO), `htmlspecialchars()` na saída, validação no servidor,
CSRF, regeneração de sessão no login e erros técnicos registrados em log (nunca
exibidos ao visitante). Antes de publicar na internet: HTTPS, credenciais próprias
do banco, `display_errors` desligado e revisão das configurações de produção.

## Trabalho em grupo

Uma branch por funcionalidade, commits pequenos com mensagens claras, revisão
antes de integrar na `main`. Não envie senhas nem dados pessoais reais ao repositório.
