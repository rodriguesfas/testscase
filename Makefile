# 🥋 CodeDojo Makefile
# Comandos para gerenciar o ambiente Docker facilmente

.PHONY: help up down restart rebuild logs shell-php shell-postgres test-py test-js test-cpp admin migrate stats clean status backup restore

# Configurações
COMPOSE_FILE = docker-compose.yml
POSTGRES_CONTAINER = codedojo_postgres
PHP_CONTAINER = codedojo_php
PGADMIN_CONTAINER = codedojo_pgadmin

# Comando padrão
help: ## 📋 Mostra esta ajuda
	@echo "🥋 CodeDojo - Comandos disponíveis:"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'
	@echo ""
	@echo "📊 Status atual:"
	@make --no-print-directory status

# === COMANDOS PRINCIPAIS ===

up: ## 🚀 Iniciar todos os containers
	@echo "🚀 Iniciando CodeDojo..."
	docker-compose up -d --build
	@echo "⏳ Aguardando PostgreSQL..."
	@sleep 10
	@echo "✅ CodeDojo iniciado!"
	@make --no-print-directory urls

down: ## 🛑 Parar todos os containers
	@echo "🛑 Parando CodeDojo..."
	docker-compose down
	@echo "✅ Containers parados!"

restart: ## 🔄 Reiniciar todos os containers
	@echo "🔄 Reiniciando CodeDojo..."
	@make --no-print-directory down
	@make --no-print-directory up

rebuild: ## 🏗️ Rebuild completo (limpa volumes)
	@echo "🏗️ Rebuild completo do CodeDojo..."
	docker-compose down -v
	docker-compose build --no-cache
	docker-compose up -d
	@echo "⏳ Aguardando PostgreSQL..."
	@sleep 15
	@echo "📦 Executando migração..."
	@make --no-print-directory migrate
	@echo "✅ Rebuild concluído!"

# === LOGS E MONITORAMENTO ===

logs: ## 📋 Ver logs de todos os containers
	docker-compose logs -f

logs-postgres: ## 📋 Ver logs do PostgreSQL
	docker-compose logs -f postgres

logs-php: ## 📋 Ver logs do PHP
	docker-compose logs -f php

logs-pgadmin: ## 📋 Ver logs do pgAdmin
	docker-compose logs -f pgadmin

status: ## 📊 Status dos containers
	@echo "📊 Status dos containers:"
	@docker-compose ps

# === ACESSO AOS CONTAINERS ===

shell-php: ## 💻 Acesso shell ao container PHP
	@echo "💻 Acessando container PHP..."
	docker exec -it $(PHP_CONTAINER) bash

shell-postgres: ## 🐘 Acesso shell ao PostgreSQL
	@echo "🐘 Acessando PostgreSQL..."
	docker exec -it $(POSTGRES_CONTAINER) psql -U codedojo_user -d codedojo

shell-postgres-root: ## 🐘 Acesso root ao container PostgreSQL
	@echo "🐘 Acessando container PostgreSQL como root..."
	docker exec -it $(POSTGRES_CONTAINER) bash

# === TESTES ===

test-py: ## 🐍 Testar código Python
	@echo "🐍 Testando postes.py..."
	php run_db.php postes.py "Make Test"

test-js: ## 🟨 Testar código JavaScript
	@echo "🟨 Testando postes.js..."
	php run_db.php postes.js "Make Test"

test-cpp: ## ⚡ Testar código C++
	@echo "⚡ Testando postes.cpp..."
	php run_db.php postes.cpp "Make Test"

test-all: ## 🧪 Testar todos os códigos
	@echo "🧪 Testando todas as linguagens..."
	@make --no-print-directory test-py
	@echo ""
	@make --no-print-directory test-js
	@echo ""
	@make --no-print-directory test-cpp

# === ADMINISTRAÇÃO ===

admin: ## 📋 Interface administrativa
	@echo "📋 Abrindo interface administrativa..."
	php admin.php

migrate: ## 🔄 Migrar dados existentes
	@echo "🔄 Executando migração de dados..."
	php migrate_data.php

stats: ## 📊 Estatísticas do sistema
	@echo "📊 Estatísticas do CodeDojo:"
	@php -r "$$pdo = new PDO('pgsql:host=localhost;port=5432;dbname=codedojo', 'codedojo_user', 'codedojo_pass'); echo 'Desafios: ' . $$pdo->query('SELECT COUNT(*) FROM challenges')->fetchColumn() . \"\n\"; echo 'Submissões: ' . $$pdo->query('SELECT COUNT(*) FROM submissions')->fetchColumn() . \"\n\"; echo 'Testes: ' . $$pdo->query('SELECT COUNT(*) FROM test_cases')->fetchColumn() . \"\n\";" 2>/dev/null || echo "❌ Banco não está disponível"

# === BACKUP E RESTORE ===

backup: ## 💾 Backup do banco de dados
	@echo "💾 Criando backup do banco..."
	@mkdir -p backups
	@docker exec $(POSTGRES_CONTAINER) pg_dump -U codedojo_user codedojo > backups/codedojo_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "✅ Backup criado em backups/"

restore: ## 📥 Restaurar backup (usar: make restore BACKUP=arquivo.sql)
	@if [ -z "$(BACKUP)" ]; then \
		echo "❌ Especifique o arquivo: make restore BACKUP=arquivo.sql"; \
		exit 1; \
	fi
	@echo "📥 Restaurando backup $(BACKUP)..."
	@cat $(BACKUP) | docker exec -i $(POSTGRES_CONTAINER) psql -U codedojo_user -d codedojo
	@echo "✅ Backup restaurado!"

# === LIMPEZA ===

clean: ## 🧹 Limpar containers e volumes não utilizados
	@echo "🧹 Limpando Docker..."
	docker system prune -f
	docker volume prune -f
	@echo "✅ Limpeza concluída!"

clean-all: ## 🧹 Limpeza completa (CUIDADO: remove tudo)
	@echo "⚠️  CUIDADO: Isso vai remover TODOS os dados!"
	@read -p "Tem certeza? (y/N): " confirm && [ "$$confirm" = "y" ]
	docker-compose down -v
	docker system prune -af
	docker volume prune -f
	@echo "✅ Limpeza completa realizada!"

# === DESENVOLVIMENTO ===

dev: ## 🔧 Modo desenvolvimento (com rebuild e migração)
	@echo "🔧 Configurando ambiente de desenvolvimento..."
	@make --no-print-directory rebuild
	@make --no-print-directory urls
	@echo "🎉 Ambiente pronto para desenvolvimento!"

setup: ## ⚙️ Setup inicial completo
	@echo "⚙️ Setup inicial do CodeDojo..."
	@make --no-print-directory up
	@make --no-print-directory migrate
	@make --no-print-directory urls
	@echo "🎉 Setup concluído com sucesso!"

urls: ## 🌐 Mostrar URLs importantes
	@echo ""
	@echo "🌐 URLs importantes:"
	@echo "   PostgreSQL: localhost:5432"
	@echo "   pgAdmin:    http://localhost:8080"
	@echo "               (admin@codedojo.com / admin123)"
	@echo ""
	@echo "🔧 Comandos úteis:"
	@echo "   make admin       # Interface administrativa"
	@echo "   make test-all    # Testar todos os códigos"
	@echo "   make logs        # Ver logs"
	@echo ""

# === COMANDOS AVANÇADOS ===

reset-db: ## 🗄️ Reset completo do banco (CUIDADO: apaga dados)
	@echo "⚠️  CUIDADO: Isso vai apagar todos os dados do banco!"
	@read -p "Tem certeza? (y/N): " confirm && [ "$$confirm" = "y" ]
	docker-compose stop postgres
	docker volume rm codedojo_postgres_data 2>/dev/null || true
	docker-compose up -d postgres
	@echo "⏳ Aguardando PostgreSQL..."
	@sleep 15
	@make --no-print-directory migrate
	@echo "✅ Banco resetado!"

check: ## 🔍 Verificar se tudo está funcionando
	@echo "🔍 Verificando CodeDojo..."
	@echo -n "Docker: "
	@docker --version > /dev/null 2>&1 && echo "✅" || echo "❌"
	@echo -n "Docker Compose: "
	@docker-compose --version > /dev/null 2>&1 && echo "✅" || echo "❌"
	@echo -n "Containers ativos: "
	@if [ $$(docker-compose ps -q | wc -l) -eq 3 ]; then echo "✅ (3/3)"; else echo "❌ ($$(docker-compose ps -q | wc -l)/3)"; fi
	@echo -n "PostgreSQL: "
	@docker exec $(POSTGRES_CONTAINER) pg_isready -U codedojo_user -d codedojo > /dev/null 2>&1 && echo "✅" || echo "❌"
	@echo -n "PHP: "
	@php --version > /dev/null 2>&1 && echo "✅" || echo "❌"

# === COMANDOS DE TESTE ESPECÍFICOS ===

test-file: ## 📝 Testar arquivo específico (usar: make test-file FILE=arquivo.py AUTHOR="Nome")
	@if [ -z "$(FILE)" ]; then \
		echo "❌ Especifique o arquivo: make test-file FILE=arquivo.py"; \
		exit 1; \
	fi
	@echo "📝 Testando $(FILE)..."
	php run_db.php $(FILE) "$(or $(AUTHOR),Make Test)"

create-challenge: ## ➕ Criar novo desafio interativo
	@echo "➕ Criando novo desafio..."
	@make --no-print-directory admin

# === INFORMAÇÕES ===

info: ## ℹ️ Informações do sistema
	@echo "ℹ️ Informações do CodeDojo:"
	@echo "   Versão Docker: $$(docker --version)"
	@echo "   Versão Compose: $$(docker-compose --version)"
	@echo "   Diretório: $$(pwd)"
	@echo "   Arquivos principais: $$(ls -1 *.php *.yml *.sh 2>/dev/null | wc -l) encontrados"
	@make --no-print-directory status

# Comando padrão quando não especificado
.DEFAULT_GOAL := help
