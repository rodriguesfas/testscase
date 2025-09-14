# 🥋 CodeDojo

Sistema completo para maratona de programação com banco de dados PostgreSQL e suporte a múltiplas linguagens.

## 📁 Estrutura do Projeto

```
codedojo/
├── 🐳 docker-compose.yml     # Configuração Docker (PostgreSQL + pgAdmin + PHP)
├── 🐳 Dockerfile             # Container PHP com ferramentas de desenvolvimento
├── ⚙️ Makefile               # Comandos Make para gerenciar Docker facilmente
├── 📊 database/
│   └── init/
│       ├── 01_create_schema.sql   # Schema do banco de dados
│       └── 02_sample_data.sql     # Dados iniciais
├── 🏃 run.php                # Sistema original (arquivo-based)
├── 🏃 run_db.php             # Sistema novo (database-based)
├── 📋 admin.php              # Interface administrativa
├── 🔄 migrate_data.php       # Migração de dados existentes
├── ⚙️ setup.sh               # Script de configuração automática
├── 📝 README.md              # Esta documentação
├── 📁 templates/             # Casos de teste (formato original)
├── 💻 postes.py              # Código exemplo Python
├── 💻 postes.js              # Código exemplo JavaScript
└── 💻 postes.cpp             # Código exemplo C++
```

## 🚀 Setup Rápido

### 1. Usando Makefile (Recomendado)

```bash
# Ver todos os comandos disponíveis
make help

# Setup inicial completo
make setup

# Ou comandos individuais
make up        # Iniciar containers
make migrate   # Migrar dados
make admin     # Interface administrativa
```

### 2. Configuração com Script

```bash
# Execute o script de setup
./setup.sh
```

### 3. Configuração Manual

```bash
# Iniciar containers
docker-compose up -d --build

# Aguardar PostgreSQL (30 segundos)
sleep 30

# Migrar dados existentes
php migrate_data.php
```

## 🎯 Como Usar

### 🏃 Executar Testes

```bash
# Via Makefile (recomendado)
make test-py          # Testar Python
make test-js          # Testar JavaScript  
make test-cpp         # Testar C++
make test-all         # Testar todas as linguagens
make test-file FILE=meu_codigo.py AUTHOR="Meu Nome"

# Via comando direto
php run.php postes.py              # Sistema original
php run_db.php postes.py "João"    # Sistema com banco
```

### 📋 Interface Administrativa

```bash
# Via Makefile
make admin            # Interface administrativa

# Via comando direto
php admin.php

# Opções disponíveis:
# 1. Listar desafios
# 2. Ver detalhes do desafio  
# 3. Criar novo desafio
# 4. Listar submissões
# 5. Estatísticas
```

### 🌐 pgAdmin (Interface Web)

- **URL**: http://localhost:8080
- **Email**: admin@codedojo.com
- **Password**: admin123

## 🗄️ Banco de Dados

### Conexão PostgreSQL

- **Host**: localhost:5432
- **Database**: codedojo
- **User**: codedojo_user
- **Password**: codedojo_pass

### Schema

```sql
-- Tabelas principais
challenges      # Desafios/problemas
test_cases      # Casos de teste
submissions     # Códigos submetidos
test_results    # Resultados dos testes
executions      # Resumo das execuções
```

## 🔧 Linguagens Suportadas

| Linguagem   | Extensão | Compilador/Interpretador |
|-------------|----------|--------------------------|
| Python      | `.py`    | python3                  |
| C++         | `.cpp`   | g++                      |
| C           | `.c`     | gcc                      |
| Java        | `.java`  | javac + java             |
| JavaScript  | `.js`    | node                     |
| TypeScript  | `.ts`    | tsc + node               |
| Go          | `.go`    | go run                   |
| Rust        | `.rs`    | rustc                    |
| PHP         | `.php`   | php                      |

## 🎮 Exemplos de Uso

### Criar Novo Desafio

```bash
php admin.php
# Escolha opção 3
# Siga as instruções interativas
```

### Submeter Código

```bash
# Exemplo: submeter solução em Python
php run_db.php meu_codigo.py "Meu Nome"

# O sistema irá:
# 1. Detectar a linguagem automaticamente
# 2. Salvar o código no banco
# 3. Executar todos os casos de teste
# 4. Mostrar resultados detalhados
# 5. Salvar estatísticas no banco
```

### Ver Resultados

```bash
php admin.php
# Escolha opção 4 para ver submissões recentes
# Escolha opção 5 para ver estatísticas gerais
```

## 📊 Funcionalidades

### ✅ Sistema Original (run.php)
- ✅ Detecção automática de linguagem
- ✅ Compilação e execução multi-linguagem
- ✅ Teste contra casos em `templates/`
- ✅ Saída formatada com estatísticas

### 🆕 Sistema com Banco (run_db.php)
- ✅ Todas as funcionalidades do sistema original
- ✅ Armazenamento persistente de submissões
- ✅ Histórico completo de execuções
- ✅ Métricas de performance (tempo de execução)
- ✅ Gerenciamento de múltiplos desafios
- ✅ Interface administrativa

### 🎛️ Interface Admin (admin.php)
- ✅ Listagem de desafios
- ✅ Criação de novos desafios
- ✅ Visualização de submissões
- ✅ Estatísticas gerais
- ✅ Detalhes de execução

## 🔄 Migração de Dados

O script `migrate_data.php` converte automaticamente:

- **Estrutura `templates/`** → **Tabela `test_cases`**
- **Arquivos `.py/.js/.cpp`** → **Tabela `submissions`**
- **Metadados** → **Tabela `challenges`**

## 🐳 Docker Services

```yaml
services:
  postgres:    # Banco PostgreSQL
  pgadmin:     # Interface web do PostgreSQL  
  php:         # Container PHP com todas as linguagens
```

## 🛠️ Comandos Úteis

### 📋 Makefile Commands

```bash
# === PRINCIPAIS ===
make up              # 🚀 Iniciar containers
make down            # 🛑 Parar containers  
make restart         # 🔄 Reiniciar containers
make rebuild         # 🏗️ Rebuild completo
make setup           # ⚙️ Setup inicial

# === TESTES ===
make test-py         # 🐍 Testar Python
make test-js         # 🟨 Testar JavaScript
make test-cpp        # ⚡ Testar C++
make test-all        # 🧪 Testar tudo

# === ADMINISTRAÇÃO ===
make admin           # 📋 Interface admin
make migrate         # 🔄 Migrar dados
make stats           # 📊 Estatísticas

# === LOGS ===
make logs            # 📋 Logs gerais
make logs-postgres   # 🐘 Logs PostgreSQL
make status          # 📊 Status containers

# === ACESSO ===
make shell-php       # 💻 Shell PHP
make shell-postgres  # 🐘 Shell PostgreSQL

# === BACKUP ===
make backup          # 💾 Backup banco
make restore BACKUP=arquivo.sql  # 📥 Restaurar

# === LIMPEZA ===
make clean           # 🧹 Limpar Docker
make reset-db        # 🗄️ Reset banco
```

### 🐳 Docker Direto

```bash
# Ver logs do sistema
docker-compose logs

# Acessar container PHP
docker exec -it codedojo_php bash

# Parar sistema
docker-compose down

# Rebuild completo
docker-compose down -v && docker-compose up -d --build
```

## 🎯 Casos de Uso

### 👨‍🏫 Para Professores
- Criar desafios de programação
- Acompanhar submissões dos alunos
- Visualizar estatísticas de performance
- Gerenciar casos de teste

### 👨‍💻 Para Estudantes
- Submeter soluções em múltiplas linguagens
- Ver feedback detalhado dos testes
- Acompanhar histórico de submissões

### 🏆 Para Competições
- Sistema robusto de avaliação
- Suporte a múltiplas linguagens
- Métricas de tempo de execução
- Armazenamento persistente

## 🔮 Próximos Passos

- [ ] Quero ter uma landingpage para nosso site CodeDoJo em index.php
- [ ] Quero que usuários possam se cadastrar na plataforma, fazer login e recuperar senha.  
- [ ] Interface web completa
- [ ] Sistema de ranking
- [ ] Autenticação de usuários
- [ ] API REST
- [ ] Notificações em tempo real
- [ ] Análise de código (similaridade)

---

**Desenvolvido com ❤️ em PHP + PostgreSQL + Docker**