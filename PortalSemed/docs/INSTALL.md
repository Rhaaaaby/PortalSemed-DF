# Portal SEMED — Guia de Instalação

## 1. Sobre este documento

Este documento descreve como configurar e executar o Portal SEMED em ambiente de desenvolvimento utilizando Docker.

A infraestrutura do projeto é composta por:

- PHP 8.5-FPM
- PostgreSQL 16
- Nginx
- Docker
- Docker Compose
- Composer
- Certbot (opcional, para HTTPS)

A aplicação utiliza uma arquitetura baseada em PHP, com o Nginx como servidor web e o PostgreSQL como banco de dados.

---

## 2. Pré-requisitos

Antes de iniciar a instalação, é necessário possuir:

- Git
- Docker
- Docker Compose
- Composer

Para desenvolvimento local, recomenda-se também:

- Visual Studio Code
- Navegador moderno
- Postman ou Insomnia
- Terminal

O PHP instalado diretamente no sistema operacional não é obrigatório para executar a aplicação através do Docker, pois o projeto possui seu próprio container PHP.

---

## 3. Arquitetura dos containers

O projeto utiliza quatro serviços Docker:

```text
postgres
   │
   │ PostgreSQL
   ▼
app
   │
   │ PHP-FPM
   ▼
nginx
   │
   │ HTTP/HTTPS
   ▼
Usuário
````

Existe também um serviço opcional:

```text
certbot
```

utilizado para gerenciamento de certificados SSL/TLS.

---

## 4. Serviços Docker

### 4.1 PostgreSQL

Imagem utilizada:

```text
postgres:16-alpine
```

Container:

```text
portalsemed-db
```

Porta:

```text
5432
```

O banco recebe suas configurações através das variáveis:

```text
DB_NAME
DB_USER
DB_PASS
```

O arquivo:

```text
database/schema.sql
```

é montado automaticamente no diretório de inicialização do PostgreSQL:

```text
/docker-entrypoint-initdb.d/01-schema.sql
```

Isso permite que o banco seja inicializado automaticamente na primeira criação do volume.

---

### 4.2 PHP

O serviço PHP é construído a partir de:

```text
docker/php/Dockerfile
```

Imagem base:

```text
php:8.5-fpm-alpine
```

Extensões instaladas:

```text
pdo
pdo_pgsql
pgsql
```

O diretório de trabalho do container é:

```text
/var/www/portalsemed
```

O projeto inteiro é montado no container através de:

```text
./:/var/www/portalsemed
```

---

### 4.3 Nginx

Imagem utilizada:

```text
nginx:alpine
```

Container:

```text
portalsemed-web
```

Portas:

```text
80:80
443:443
```

O diretório público da aplicação é:

```text
/var/www/portalsemed/public
```

O Nginx encaminha requisições PHP para:

```text
app:9000
```

---

### 4.4 Certbot

O Certbot é opcional.

Ele é utilizado para gerenciamento de certificados SSL/TLS.

O serviço utiliza o profile:

```text
ssl
```

Portanto, ele não é iniciado por padrão.

Para iniciar o serviço:

```bash
docker compose --profile ssl up -d certbot
```

A configuração de certificados fica em:

```text
docker/certbot/conf
```

e os arquivos utilizados para validação ficam em:

```text
docker/certbot/www
```

---

# 5. Clonando o projeto

Clone o repositório:

```bash
git clone <URL_DO_REPOSITORIO>
```

Entre no diretório do projeto:

```bash
cd portalSemed
```

---

# 6. Configuração do ambiente

O projeto utiliza variáveis de ambiente.

Existe um arquivo de referência:

```text
.env.example
```

Crie o arquivo `.env` a partir dele.

### Windows PowerShell

```powershell
Copy-Item .env.example .env
```

### Windows CMD

```cmd
copy .env.example .env
```

### Linux/macOS

```bash
cp .env.example .env
```

Depois, abra o arquivo:

```text
.env
```

e configure os valores necessários.

As principais variáveis utilizadas pelo Docker Compose são:

```text
DB_NAME
DB_USER
DB_PASS
JWT_SECRET
```

Os valores devem corresponder às configurações utilizadas pelo Docker Compose.

> **Importante:** o arquivo `.env` pode conter informações sensíveis e não deve ser versionado no Git.

---

# 7. Banco de dados

O PostgreSQL utiliza as seguintes configurações dentro da rede Docker:

```text
DB_HOST=postgres
DB_PORT=5432
```

O hostname do banco dentro do Docker é:

```text
postgres
```

Não deve ser utilizado:

```text
localhost
```

para a comunicação entre o container PHP e o PostgreSQL.

Isso ocorre porque os serviços se comunicam através da rede Docker:

```text
portalsemed-network
```

---

# 8. Inicialização do banco

Ao criar o container PostgreSQL pela primeira vez, o arquivo:

```text
database/schema.sql
```

é executado automaticamente.

O banco contém atualmente as tabelas:

```text
users
posts
files
documents
```

### Importante

O script de inicialização do PostgreSQL é executado automaticamente apenas quando o diretório de dados é inicializado.

Como existe um volume persistente:

```text
postgres_data
```

alterações posteriores no `schema.sql` não serão necessariamente aplicadas automaticamente ao banco existente.

Para recriar o banco durante o desenvolvimento:

```bash
docker compose down -v
docker compose up -d
```

> **ATENÇÃO:** o comando `docker compose down -v` remove os volumes associados ao Compose. Isso significa que os dados atuais do banco serão apagados.
>
> Nunca utilize esse comando em produção sem ter certeza de que existe um backup.

---

# 9. Instalação das dependências PHP

As dependências PHP são gerenciadas pelo Composer.

Execute:

```bash
composer install
```

O projeto possui:

```text
composer.json
composer.lock
```

O arquivo `composer.lock` deve ser preservado para garantir versões consistentes das dependências.

---

# 10. Construção e inicialização

Depois de configurar o `.env`, execute:

```bash
docker compose up -d --build
```

Esse comando:

1. Constrói a imagem PHP.
2. Cria a rede Docker.
3. Cria o PostgreSQL.
4. Inicializa o banco.
5. Inicia o PHP-FPM.
6. Inicia o Nginx.

---

# 11. Verificando os containers

Execute:

```bash
docker compose ps
```

Os principais serviços esperados são:

```text
portalsemed-db
portalsemed-app
portalsemed-web
```

O PostgreSQL deve aparecer como saudável devido ao healthcheck configurado no Docker Compose.

---

# 12. Verificando logs

Para visualizar os logs de todos os serviços:

```bash
docker compose logs
```

Para acompanhar os logs em tempo real:

```bash
docker compose logs -f
```

Somente PostgreSQL:

```bash
docker compose logs -f postgres
```

Somente PHP:

```bash
docker compose logs -f app
```

Somente Nginx:

```bash
docker compose logs -f nginx
```

---

# 13. Acessando o portal

Com os containers funcionando, o portal deve estar disponível em:

```text
http://localhost
```

O Nginx utiliza:

```text
public/
```

como raiz pública da aplicação.

Portanto, arquivos internos como:

```text
app/
database/
tests/
vendor/
```

não devem ser diretamente expostos pelo servidor web.

---

# 14. Arquitetura de requisição

Uma requisição normal segue este fluxo:

```text
Navegador
    │
    ▼
Nginx :80
    │
    ▼
public/index.php
    │
    ▼
Aplicação PHP
    │
    ▼
Controller
    │
    ▼
Model
    │
    ▼
PostgreSQL
```

Para requisições PHP, o Nginx utiliza:

```text
app:9000
```

como servidor FastCGI.

---

# 15. API

As rotas da API estão localizadas em:

```text
public/api/router.php
```

O acesso às rotas depende do roteamento definido em:

```text
public/index.php
```

Os testes automatizados relacionados à API estão localizados em:

```text
tests/
```

---

# 16. Uploads

Os arquivos enviados pelo sistema são armazenados dentro da área pública:

```text
public/assets/images/uploads/
```

Durante o desenvolvimento, é necessário garantir que o processo PHP possua permissão para gravar nesse diretório.

O diretório faz parte do volume:

```text
./:/var/www/portalsemed
```

Portanto, os arquivos criados pelo container também estarão disponíveis no projeto local.

---

# 17. Desenvolvimento

Como o projeto inteiro é montado através de:

```text
./:/var/www/portalsemed
```

alterações realizadas nos arquivos locais ficam disponíveis dentro do container.

Em geral, não é necessário reconstruir a imagem a cada alteração de código PHP.

O rebuild é necessário quando houver alterações na configuração da imagem, principalmente em:

```text
docker/php/Dockerfile
```

Nesse caso:

```bash
docker compose up -d --build
```

---

# 18. Parando a aplicação

Para parar os containers:

```bash
docker compose down
```

Esse comando remove os containers, mas preserva o volume do PostgreSQL.

Para iniciar novamente:

```bash
docker compose up -d
```

---

# 19. Reinicialização completa

Durante o desenvolvimento, pode ser necessário recriar completamente o ambiente:

```bash
docker compose down -v
docker compose up -d --build
```

### ATENÇÃO

Esse procedimento remove o volume:

```text
postgres_data
```

e consequentemente os dados armazenados no banco.

Utilize esse procedimento somente quando for desejado recriar o banco do zero.

---

# 20. HTTPS

O projeto possui suporte preparado para Certbot.

O serviço está definido com:

```text
profiles:
  - ssl
```

Isso significa que ele não é iniciado no fluxo padrão.

A configuração de HTTPS deve ser realizada antes da utilização em produção.

A configuração atual do Nginx utiliza:

```text
server_name localhost;
```

indicando que a configuração fornecida atualmente é voltada principalmente para ambiente local.

---

# 21. Solução de problemas

## 21.1 Nginx não inicia

Verifique:

```bash
docker compose logs nginx
```

Confirme se os arquivos de configuração existem:

```text
docker/nginx/nginx.conf
docker/nginx/conf.d/default.conf
```

---

## 21.2 PostgreSQL não inicia

Verifique:

```bash
docker compose logs postgres
```

Confirme as seguintes variáveis no `.env`:

```text
DB_NAME
DB_USER
DB_PASS
```

---

## 21.3 PHP não consegue conectar ao banco

Dentro do Docker, utilize:

```text
DB_HOST=postgres
DB_PORT=5432
```

e não:

```text
DB_HOST=localhost
```

---

## 21.4 Alterações no banco não aparecem

Se o banco já foi inicializado anteriormente, o `schema.sql` não será necessariamente executado novamente.

Durante o desenvolvimento, pode ser necessário recriar o volume:

```bash
docker compose down -v
docker compose up -d
```

---

## 21.5 Erro 404

Verifique:

```text
public/index.php
public/.htaccess
docker/nginx/conf.d/default.conf
```

Confirme também se a requisição está sendo encaminhada para:

```text
/index.php
```

---

## 21.6 Erro 500

Verifique os logs:

```bash
docker compose logs -f app
```

Também verifique:

* configuração do `.env`;
* conexão com o banco;
* dependências do Composer;
* permissões de arquivos.

---

# 22. Checklist de instalação

Após a instalação, verificar:

* [ ] `.env` criado
* [ ] Credenciais do banco configuradas
* [ ] Composer instalado
* [ ] Dependências instaladas
* [ ] Docker funcionando
* [ ] Containers iniciados
* [ ] PostgreSQL saudável
* [ ] Nginx funcionando
* [ ] PHP-FPM funcionando
* [ ] Banco criado
* [ ] Tabelas criadas
* [ ] Portal acessível
* [ ] API acessível
* [ ] Upload funcionando
* [ ] Testes executados

---

# 23. Ambiente de produção

A configuração atual deve ser considerada uma configuração de desenvolvimento até que sejam realizadas as devidas validações de segurança e infraestrutura.

Antes de utilizar o sistema em produção, devem ser revisados:

* Credenciais;
* `JWT_SECRET`;
* HTTPS;
* Permissões de arquivos;
* Exposição de diretórios;
* Configuração do Nginx;
* Tratamento de erros;
* Logs;
* Backups;
* Banco de dados;
* Upload de arquivos;
* Autenticação;
* Autorização.

Em produção, erros internos da aplicação não devem ser exibidos diretamente aos usuários.

---

# 24. Pendências identificadas

Durante a documentação da infraestrutura foi identificado o seguinte ponto que deverá ser verificado:

## 24.1 Arquivo `nginx.conf`

O `docker-compose.yml` monta:

```text
./docker/nginx/nginx.conf
```

no container Nginx.

Entretanto, na estrutura de diretórios apresentada anteriormente, consta:

```text
docker/
└── nginx/
    └── conf.d/
        └── default.conf
```

e o arquivo:

```text
docker/nginx/nginx.conf
```

não apareceu.

É necessário confirmar se esse arquivo existe.

Caso não exista, o volume deverá ser corrigido ou removido do `docker-compose.yml`.

---

# 25. Resumo da infraestrutura

A aplicação utiliza três serviços principais:

```text
┌──────────────────┐
│      Nginx       │
│       :80        │
│       :443       │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│    PHP 8.5-FPM   │
│       :9000      │
└────────┬─────────┘
         │
         ▼
┌──────────────────┐
│   PostgreSQL 16  │
│       :5432      │
└──────────────────┘
```

O Certbot permanece como serviço opcional para SSL/TLS.

---

# 26. Comandos principais

### Instalar dependências

```bash
composer install
```

### Criar ambiente

#### Windows PowerShell

```powershell
Copy-Item .env.example .env
```

#### Windows CMD

```cmd
copy .env.example .env
```

#### Linux/macOS

```bash
cp .env.example .env
```

### Construir e iniciar

```bash
docker compose up -d --build
```

### Verificar containers

```bash
docker compose ps
```

### Ver logs

```bash
docker compose logs -f
```

### Parar aplicação

```bash
docker compose down
```

### Reiniciar

```bash
docker compose up -d
```

### Recriar banco do zero

```bash
docker compose down -v
docker compose up -d --build
```

> **ATENÇÃO:** o último comando apaga os dados armazenados no volume do PostgreSQL.

---

## Status do documento

**Projeto:** Portal SEMED
**Documento:** Guia de Instalação
**Arquivo:** `docs/INSTALL.md`
**Status:** Versão inicial
**Ambiente principal:** Docker
**PHP:** 8.5-FPM
**PostgreSQL:** 16
**Servidor Web:** Nginx