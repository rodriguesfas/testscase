-- Dados iniciais para o CodeDojo
-- Populando com o desafio "postes" existente

-- Inserir o desafio postes
INSERT INTO challenges (name, title, description, difficulty) VALUES 
('postes', 'Postes de Iluminação', 
'Um município está trocando a iluminação pública por postes de LED. Para isso, precisa avaliar a altura dos postes existentes:
- Postes com altura menor que 50cm devem ser substituídos
- Postes com altura entre 50cm e 84cm devem ser consertados  
- Postes com altura maior ou igual a 85cm estão em bom estado

Dado um conjunto de alturas de postes, determine quantos devem ser substituídos e quantos devem ser consertados.

Entrada:
- Primeira linha: número N de postes
- Segunda linha: N inteiros representando as alturas dos postes

Saída:
- Dois inteiros: quantidade de postes a substituir e quantidade a consertar', 
'easy');

-- Verificar se a inserção foi bem-sucedida e obter o ID
DO $$
DECLARE
    challenge_postes_id INTEGER;
BEGIN
    SELECT id INTO challenge_postes_id FROM challenges WHERE name = 'postes';
    
    IF challenge_postes_id IS NOT NULL THEN
        RAISE NOTICE 'Desafio postes criado com ID: %', challenge_postes_id;
    ELSE
        RAISE EXCEPTION 'Falha ao criar desafio postes';
    END IF;
END $$;
