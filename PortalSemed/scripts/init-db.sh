#!/bin/bash

# Script de inicialização do banco de dados
# Uso: bash scripts/init-db.sh

set -e

echo "🔧 Inicializando banco de dados Portal SEMED..."

# Verificar se arquivo .env existe
if [ ! -f .env ]; then
    echo "❌ Arquivo .env não encontrado"
    exit 1
fi

# Carregar variáveis de ambiente
export $(cat .env | grep -v '^#' | xargs)

# Validar variáveis
if [ -z "$DB_HOST" ] || [ -z "$DB_USER" ] || [ -z "$DB_NAME" ]; then
    echo "❌ Variáveis de banco de dados não configuradas no .env"
    exit 1
fi

echo "📦 Conectando ao PostgreSQL..."
echo "Host: $DB_HOST"
echo "Database: $DB_NAME"
echo "User: $DB_USER"

# Executar schema
psql -h "$DB_HOST" -U "$DB_USER" -d "$DB_NAME" -f database/schema.sql

echo "✅ Banco de dados inicializado com sucesso!"
echo ""
echo "Próximos passos:"
echo "1. Instalar dependências: composer install"
echo "2. Testar: php public/health.php"
echo "3. Iniciar servidor: php -S localhost:8000 router.php"
