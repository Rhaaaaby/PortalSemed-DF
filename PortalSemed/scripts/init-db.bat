@echo off
REM Script de inicialização do banco de dados para Windows
REM Uso: init-db.bat

if not exist .env (
    echo Arquivo .env nao encontrado
    exit /b 1
)

echo Inicializando banco de dados Portal SEMED...

for /f "tokens=1,2 delims==" %%a in (.env) do (
    if "%%a"=="DB_HOST" set DB_HOST=%%b
    if "%%a"=="DB_USER" set DB_USER=%%b
    if "%%a"=="DB_NAME" set DB_NAME=%%b
)

if "%DB_HOST%"=="" (
    echo Variaveis de banco de dados nao configuradas
    exit /b 1
)

echo Conectando a PostgreSQL...
echo Host: %DB_HOST%
echo Database: %DB_NAME%
echo User: %DB_USER%

psql -h %DB_HOST% -U %DB_USER% -d %DB_NAME% -f database\schema.sql

if %errorlevel% equ 0 (
    echo Banco de dados inicializado com sucesso!
    echo.
    echo Proximos passos:
    echo 1. Instalar dependencias: composer install
    echo 2. Testar: php public/health.php
    echo 3. Iniciar servidor: php -S localhost:8000 router.php
) else (
    echo Erro ao inicializar banco de dados
    exit /b 1
)
