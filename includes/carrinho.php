<?php
// Carrinho de compras (guardado na sessão) e cálculo de frete.
// Os preços e o estoque SEMPRE vêm do banco; a sessão guarda só "id => quantidade".
require_once __DIR__ . '/functions.php';

// ---- Regras do carrinho e do frete (ajuste aqui) ----
const CARRINHO_MAX_POR_ITEM = 99;      // limite de unidades de um mesmo produto
const FRETE_GRATIS_ACIMA = 199.00;     // frete Econômico grátis quando o subtotal passa deste valor
const FRETE_ADICIONAL_POR_UNIDADE = 2.50; // acréscimo a partir da 2ª unidade no pedido

// Tabela de frete pela região do CEP (1º dígito).
// [nome da região, Econômico R$, Econômico dias, Expresso R$, Expresso dias]
function frete_regioes(): array
{
    return [
        0 => ['São Paulo (capital e região)',                       14.90, 4, 29.90, 2],
        1 => ['São Paulo (interior e litoral)',                     16.90, 5, 32.90, 2],
        2 => ['Rio de Janeiro e Espírito Santo',                    18.90, 6, 34.90, 3],
        3 => ['Minas Gerais',                                       19.90, 6, 36.90, 3],
        4 => ['Bahia e Sergipe',                                    24.90, 8, 42.90, 4],
        5 => ['Pernambuco, Alagoas, Paraíba e Rio Grande do Norte', 26.90, 9, 44.90, 4],
        6 => ['Ceará, Piauí, Maranhão e Região Norte',              29.90, 11, 49.90, 5],
        7 => ['Centro-Oeste (DF, GO, TO, MT, MS e RO)',             27.90, 9, 46.90, 4],
        8 => ['Paraná e Santa Catarina',                            21.90, 7, 38.90, 3],
        9 => ['Rio Grande do Sul',                                  23.90, 8, 40.90, 4],
    ];
}

// ---- CEP e frete ----

// Devolve o CEP só com 8 números, ou null se for inválido.
function cep_normalizar(?string $cep): ?string
{
    $numeros = preg_replace('/\D+/', '', (string) $cep);
    if (strlen($numeros) !== 8 || $numeros === '00000000') {
        return null;
    }
    return $numeros;
}

function cep_formatado(string $cep): string
{
    return substr($cep, 0, 5) . '-' . substr($cep, 5);
}

// Opções de frete para um CEP já normalizado.
function frete_opcoes(string $cep, float $subtotal, int $unidades): array
{
    $r = frete_regioes()[(int) $cep[0]];
    $extra = max(0, $unidades - 1) * FRETE_ADICIONAL_POR_UNIDADE;

    $economico = round($r[1] + $extra, 2);
    $gratis = $subtotal >= FRETE_GRATIS_ACIMA;

    return [
        'economico' => [
            'id' => 'economico', 'nome' => 'Econômico', 'regiao' => $r[0],
            'valor' => $gratis ? 0.0 : $economico, 'gratis' => $gratis, 'dias' => $r[2],
        ],
        'expresso' => [
            'id' => 'expresso', 'nome' => 'Expresso', 'regiao' => $r[0],
            'valor' => round($r[3] + $extra, 2), 'gratis' => false, 'dias' => $r[4],
        ],
    ];
}

// ---- Carrinho na sessão ----

// Lista limpa "id => quantidade" (ignora qualquer lixo que esteja na sessão).
function carrinho_bruto(): array
{
    $limpo = [];
    foreach (($_SESSION['carrinho'] ?? []) as $id => $qtd) {
        $id = (int) $id;
        $qtd = (int) $qtd;
        if ($id > 0 && $qtd > 0) {
            $limpo[$id] = min($qtd, CARRINHO_MAX_POR_ITEM);
        }
    }
    return $limpo;
}

// Total de unidades (usado no número do ícone do carrinho).
function carrinho_quantidade(): int
{
    return array_sum(carrinho_bruto());
}

function carrinho_definir(int $id, int $qtd): void
{
    $c = carrinho_bruto();
    if ($qtd > 0) {
        $c[$id] = min($qtd, CARRINHO_MAX_POR_ITEM);
    } else {
        unset($c[$id]);
    }
    $_SESSION['carrinho'] = $c;
}

function carrinho_esvaziar(): void
{
    unset($_SESSION['carrinho']);
}

// Monta os itens com dados atuais do banco. Produtos apagados, pausados ou sem
// estoque saem do carrinho; quantidades acima do estoque são ajustadas.
// Devolve ['itens' => [...], 'subtotal' => float, 'unidades' => int, 'avisos' => [...]]
function carrinho_detalhado(PDO $pdo): array
{
    $bruto = carrinho_bruto();
    $resultado = ['itens' => [], 'subtotal' => 0.0, 'unidades' => 0, 'avisos' => []];
    if (!$bruto) {
        return $resultado;
    }

    $ids = array_keys($bruto);
    $marcas = implode(',', array_fill(0, count($ids), '?'));
    // A categoria vem junto para mostrar o ícone dela quando o produto não tem foto
    $stmt = $pdo->prepare(
        "SELECT p.id, p.nome, p.preco, p.imagem, p.estoque, c.nome AS categoria_nome
           FROM produtos p
      LEFT JOIN categorias c ON c.id = p.categoria_id
          WHERE p.ativo = 1 AND p.id IN ($marcas)"
    );
    $stmt->execute($ids);
    $produtos = [];
    foreach ($stmt->fetchAll() as $p) {
        $produtos[(int) $p['id']] = $p;
    }

    $novo = [];
    foreach ($bruto as $id => $qtd) {
        if (!isset($produtos[$id]) || (int) $produtos[$id]['estoque'] < 1) {
            $nome = $produtos[$id]['nome'] ?? 'Um produto';
            $resultado['avisos'][] = $nome . ' não está mais disponível e saiu do carrinho.';
            continue;
        }
        $p = $produtos[$id];
        $estoque = (int) $p['estoque'];
        if ($qtd > $estoque) {
            $resultado['avisos'][] = 'Ajustamos "' . $p['nome'] . '" para ' . $estoque . ' (todo o estoque).';
            $qtd = $estoque;
        }
        $total = round((float) $p['preco'] * $qtd, 2);
        $resultado['itens'][] = [
            'id' => $id, 'nome' => $p['nome'], 'preco' => (float) $p['preco'],
            'imagem' => $p['imagem'], 'categoria_nome' => $p['categoria_nome'], 'estoque' => $estoque, 'qtd' => $qtd, 'total' => $total,
        ];
        $resultado['subtotal'] += $total;
        $resultado['unidades'] += $qtd;
        $novo[$id] = $qtd;
    }
    $resultado['subtotal'] = round($resultado['subtotal'], 2);

    if ($novo !== $bruto) {
        $_SESSION['carrinho'] = $novo;
    }
    return $resultado;
}
