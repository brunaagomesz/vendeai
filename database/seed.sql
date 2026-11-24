-- VendeAí • Dados de exemplo (rode depois do schema.sql)
USE vendeai_db;

INSERT INTO produtos (categoria_id, nome, descricao, preco, imagem, estoque) VALUES
((SELECT id FROM categorias WHERE nome = 'Eletrônicos'), 'Fone de ouvido Bluetooth', 'Fone sem fio com microfone e até 20 horas de bateria.', 149.90, 'fone.jpg', 25),
((SELECT id FROM categorias WHERE nome = 'Eletrônicos'), 'Carregador portátil 10.000 mAh', 'Power bank compacto com duas saídas USB.', 89.90, 'carregador.jpg', 40),
((SELECT id FROM categorias WHERE nome = 'Moda'), 'Moletom azul unissex', 'Moletom confortável com capuz e bolso canguru.', 129.00, 'moletom.jpg', 15),
((SELECT id FROM categorias WHERE nome = 'Moda'), 'Boné preto ajustável', 'Boné de algodão com fecho ajustável.', 49.90, 'bone.jpg', 30),
((SELECT id FROM categorias WHERE nome = 'Casa'), 'Caneca térmica 450 ml', 'Mantém a bebida quente ou gelada por horas.', 59.90, 'caneca.jpg', 50),
((SELECT id FROM categorias WHERE nome = 'Casa'), 'Luminária de mesa LED', 'Luminária com três níveis de brilho.', 79.90, 'luminaria.jpg', 20),
((SELECT id FROM categorias WHERE nome = 'Papelaria'), 'Caderno universitário 10 matérias', 'Capa dura, 160 folhas pautadas.', 34.90, 'caderno.jpg', 60),
((SELECT id FROM categorias WHERE nome = 'Papelaria'), 'Kit de canetas marca-texto', 'Pacote com 6 cores pastel.', 24.90, 'canetas.jpg', 80);
