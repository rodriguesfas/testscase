-- CodeDojo Database Schema
-- Sistema para gerenciar desafios de maratona de programação

-- Tabela de desafios/problemas
CREATE TABLE challenges (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    title VARCHAR(500) NOT NULL,
    description TEXT,
    difficulty VARCHAR(20) CHECK (difficulty IN ('easy', 'medium', 'hard')) DEFAULT 'medium',
    time_limit INTEGER DEFAULT 1000, -- em milissegundos
    memory_limit INTEGER DEFAULT 256, -- em MB
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de casos de teste
CREATE TABLE test_cases (
    id SERIAL PRIMARY KEY,
    challenge_id INTEGER NOT NULL REFERENCES challenges(id) ON DELETE CASCADE,
    folder_number INTEGER NOT NULL,
    test_number INTEGER NOT NULL,
    input_data TEXT NOT NULL,
    expected_output TEXT NOT NULL,
    is_sample BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(challenge_id, folder_number, test_number)
);

-- Tabela de submissões de código
CREATE TABLE submissions (
    id SERIAL PRIMARY KEY,
    challenge_id INTEGER NOT NULL REFERENCES challenges(id) ON DELETE CASCADE,
    filename VARCHAR(255) NOT NULL,
    language VARCHAR(50) NOT NULL,
    source_code TEXT NOT NULL,
    author VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de resultados dos testes
CREATE TABLE test_results (
    id SERIAL PRIMARY KEY,
    submission_id INTEGER NOT NULL REFERENCES submissions(id) ON DELETE CASCADE,
    test_case_id INTEGER NOT NULL REFERENCES test_cases(id) ON DELETE CASCADE,
    actual_output TEXT,
    is_correct BOOLEAN NOT NULL DEFAULT FALSE,
    execution_time_ms INTEGER,
    memory_used_mb FLOAT,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(submission_id, test_case_id)
);

-- Tabela de execuções completas (resumo)
CREATE TABLE executions (
    id SERIAL PRIMARY KEY,
    submission_id INTEGER NOT NULL REFERENCES submissions(id) ON DELETE CASCADE,
    total_tests INTEGER NOT NULL DEFAULT 0,
    passed_tests INTEGER NOT NULL DEFAULT 0,
    failed_tests INTEGER NOT NULL DEFAULT 0,
    total_execution_time_ms INTEGER DEFAULT 0,
    status VARCHAR(20) CHECK (status IN ('running', 'completed', 'failed', 'timeout')) DEFAULT 'running',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índices para melhor performance
CREATE INDEX idx_test_cases_challenge_id ON test_cases(challenge_id);
CREATE INDEX idx_submissions_challenge_id ON submissions(challenge_id);
CREATE INDEX idx_test_results_submission_id ON test_results(submission_id);
CREATE INDEX idx_executions_submission_id ON executions(submission_id);
CREATE INDEX idx_challenges_name ON challenges(name);

-- Função para atualizar updated_at automaticamente
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Trigger para atualizar updated_at na tabela challenges
CREATE TRIGGER update_challenges_updated_at 
    BEFORE UPDATE ON challenges 
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Comentários nas tabelas
COMMENT ON TABLE challenges IS 'Armazena os desafios/problemas de programação';
COMMENT ON TABLE test_cases IS 'Casos de teste para cada desafio';
COMMENT ON TABLE submissions IS 'Códigos submetidos pelos usuários';
COMMENT ON TABLE test_results IS 'Resultados da execução de cada caso de teste';
COMMENT ON TABLE executions IS 'Resumo de cada execução completa';
