#!/bin/bash
# Script de configuração do CodeDojo com PostgreSQL

echo "🚀 Configurando CodeDojo com PostgreSQL..."
echo "==============================================="

# Verificar se Docker está instalado
if ! command -v docker &> /dev/null; then
    echo "❌ Docker não está instalado. Instale Docker primeiro."
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose não está instalado. Instale Docker Compose primeiro."
    exit 1
fi

echo "✅ Docker e Docker Compose encontrados"

# Parar containers existentes
echo "🛑 Parando containers existentes (se houver)..."
docker-compose down 2>/dev/null || true

# Construir e iniciar containers
echo "🏗️  Construindo e iniciando containers..."
docker-compose up -d --build

# Aguardar PostgreSQL ficar pronto
echo "⏳ Aguardando PostgreSQL ficar pronto..."
sleep 10

# Verificar se PostgreSQL está rodando
echo "🔍 Verificando conexão com PostgreSQL..."
docker exec codedojo_postgres pg_isready -U codedojo_user -d codedojo

if [ $? -eq 0 ]; then
    echo "✅ PostgreSQL está rodando"
else
    echo "❌ PostgreSQL não está respondendo. Verifique os logs:"
    docker-compose logs postgres
    exit 1
fi

# Executar migração de dados
echo "📦 Migrando dados existentes para o banco..."
php migrate_data.php

if [ $? -eq 0 ]; then
    echo "✅ Migração concluída com sucesso"
else
    echo "⚠️  Erro na migração. Continuando..."
fi

echo ""
echo "🎉 Setup concluído com sucesso!"
echo "==============================================="
echo ""
echo "📋 INFORMAÇÕES IMPORTANTES:"
echo ""
echo "🐘 PostgreSQL:"
echo "   Host: localhost:5432"
echo "   Database: codedojo"
echo "   User: codedojo_user"
echo "   Password: codedojo_pass"
echo ""
echo "🌐 pgAdmin (interface web):"
echo "   URL: http://localhost:8080"
echo "   Email: admin@codedojo.com"
echo "   Password: admin123"
echo ""
echo "🔧 COMANDOS DISPONÍVEIS:"
echo ""
echo "# Executar testes (versão banco de dados):"
echo "php run_db.php postes.py"
echo ""
echo "# Interface administrativa:"
echo "php admin.php"
echo ""
echo "# Parar sistema:"
echo "docker-compose down"
echo ""
echo "# Ver logs:"
echo "docker-compose logs"
echo ""
echo "Pronto para usar! 🚀"
