# Portal SEMED — Guia de Utilização do Docker

## 1. Objetivo

Este documento explica como utilizar os containers Docker do projeto **Portal SEMED** no dia a dia.

O objetivo é facilitar tarefas como:

* iniciar o projeto;
* parar os containers;
* reiniciar serviços;
* visualizar logs;
* acessar o terminal de um container;
* executar comandos PHP;
* acessar o PostgreSQL;
* reconstruir imagens;
* recriar o banco de dados;
* verificar o status dos serviços;
* solucionar problemas comuns.

Este documento parte do princípio de que o ambiente Docker já foi instalado e configurado conforme o documento:

```text
docs/INSTALL.md
```

---

# 2. Estrutura dos containers

O projeto utiliza três containers principais:

```text
┌─────────────────────────────┐
│            NGINX            │
│                             │
│ Container:                  │
│ portalsemed-web             │
│                             │
│ Portas:                     │
│ 80                          │
│ 443                         │
└──────────────┬──────────────┘
               │
               │ HTTP / FastCGI
               ▼
┌─────────────────────────────┐
│          PHP-FPM            │
│                             │
│ Container:                  │
│ portalsemed-app             │
│                             │
│ Porta interna:              │
│ 9000                        │
└──────────────┬──────────────┘
               │
               │ PostgreSQL
               ▼
┌─────────────────────────────┐
│         POSTGRESQL          │
│                             │
│ Container:                  │
│ portalsemed-db              │
│                             │
│ Porta:                      │
│ 5432                        │
└─────────────────────────────┘
```

Existe também um serviço opcional:

```text
certbot
```

Esse serviço é utilizado para certificados SSL/TLS.

---

# 3. Comando principal

A maior parte dos comandos do projeto começa com:

```bash
docker compose
```

É importante executar os comandos dentro da pasta raiz do projeto.

Exemplo:

```bash
cd portalSemed
```

Depois disso, os comandos Docker podem ser executados normalmente.

---

# 4. Iniciando o projeto

Para iniciar todos os serviços:

```bash
docker compose up
```

Esse comando mantém o terminal ocupado mostrando os logs dos containers.

Para iniciar os containers em segundo plano:

```bash
docker compose up -d
```

O parâmetro:

```text
-d
```

significa **detached mode**, ou seja, os containers continuam funcionando em segundo plano.

---

# 5. Construindo novamente as imagens

Se houver alterações no:

```text
docker/php/Dockerfile
```

ou em alguma configuração que afete a construção da imagem, utilize:

```bash
docker compose up -d --build
```

Esse comando:

1. reconstrói as imagens necessárias;
2. recria os containers quando necessário;
3. inicia os serviços em segundo plano.

---

# 6. Verificando o status dos containers

Para verificar se os containers estão funcionando:

```bash
docker compose ps
```

A saída deve apresentar serviços semelhantes a:

```text
NAME                 STATUS
portalsemed-db       running
portalsemed-app      running
portalsemed-web      running
```

Também é possível utilizar:

```bash
docker ps
```

Esse comando mostra todos os containers Docker ativos no computador.

---

# 7. Parando o projeto

Para parar e remover os containers:

```bash
docker compose down
```

Esse comando:

* para os containers;
* remove os containers;
* mantém os volumes.

Portanto, os dados do PostgreSQL permanecem armazenados.

---

# 8. Parando temporariamente os containers

Se a intenção for apenas parar os containers sem removê-los:

```bash
docker compose stop
```

Para iniciar novamente:

```bash
docker compose start
```

Exemplo:

```bash
docker compose stop
docker compose start
```

---

# 9. Reiniciando o projeto

Para reiniciar os containers:

```bash
docker compose restart
```

Também é possível reiniciar apenas um serviço.

Exemplo:

```bash
docker compose restart app
```

Para reiniciar o Nginx:

```bash
docker compose restart nginx
```

Para reiniciar o PostgreSQL:

```bash
docker compose restart postgres
```

---

# 10. Visualizando logs

Os logs são fundamentais para identificar erros.

Para visualizar os logs de todos os serviços:

```bash
docker compose logs
```

Para acompanhar os logs em tempo real:

```bash
docker compose logs -f
```

Para sair da visualização, pressione:

```text
Ctrl + C
```

Isso não para os containers.

---

# 11. Logs do PHP

Para visualizar apenas os logs da aplicação PHP:

```bash
docker compose logs app
```

Para acompanhar em tempo real:

```bash
docker compose logs -f app
```

Esse comando é especialmente útil para investigar:

* erros PHP;
* exceções;
* falhas de conexão com o banco;
* problemas de carregamento da aplicação.

---

# 12. Logs do PostgreSQL

Para visualizar os logs do banco:

```bash
docker compose logs postgres
```

Em tempo real:

```bash
docker compose logs -f postgres
```

Utilize esse comando quando houver problemas relacionados a:

* conexão;
* usuário;
* senha;
* banco inexistente;
* execução do `schema.sql`.

---

# 13. Logs do Nginx

Para visualizar os logs do servidor web:

```bash
docker compose logs nginx
```

Em tempo real:

```bash
docker compose logs -f nginx
```

Esse comando é útil para investigar:

* erro 404;
* erro 403;
* erro 502;
* problemas de FastCGI;
* problemas de roteamento.

---

# 14. Acessando o container PHP

Para abrir um terminal dentro do container da aplicação:

```bash
docker compose exec app sh
```

O prompt passará a representar o ambiente interno do container.

Exemplo:

```text
/var/www/portalsemed #
```

Dentro do container é possível executar comandos como:

```bash
php -v
```

Para verificar a versão do PHP.

Também é possível verificar extensões:

```bash
php -m
```

Para sair do container:

```bash
exit
```

---

# 15. Executando comandos PHP

Não é necessário entrar no container para executar um único comando.

Por exemplo:

```bash
docker compose exec app php -v
```

Para executar um arquivo PHP:

```bash
docker compose exec app php caminho/do/arquivo.php
```

Exemplo:

```bash
docker compose exec app php tests/test_api.php
```

O caminho deve ser relativo ao diretório do projeto dentro do container.

---

# 16. Acessando o PostgreSQL

Para abrir o terminal do PostgreSQL:

```bash
docker compose exec postgres psql -U $DB_USER -d $DB_NAME
```

Caso as variáveis não sejam reconhecidas pelo terminal do sistema, é possível informar manualmente o usuário e o banco:

```bash
docker compose exec postgres psql -U usuario -d nome_do_banco
```

Exemplo genérico:

```bash
docker compose exec postgres psql -U postgres -d portalsemed
```

---

# 17. Comandos básicos do PostgreSQL

Depois de acessar o PostgreSQL, alguns comandos úteis são:

Listar bancos:

```sql
\l
```

Conectar a um banco:

```sql
\c nome_do_banco
```

Listar tabelas:

```sql
\dt
```

Ver estrutura de uma tabela:

```sql
\d users
```

Consultar usuários:

```sql
SELECT * FROM users;
```

Consultar posts:

```sql
SELECT * FROM posts;
```

Sair do PostgreSQL:

```sql
\q
```

---

# 18. Executando comandos SQL diretamente

Também é possível executar um comando SQL sem entrar no terminal interativo.

Exemplo:

```bash
docker compose exec postgres psql -U postgres -d portalsemed -c "SELECT * FROM users;"
```

Isso é útil para testes rápidos e verificações.

---

# 19. Reiniciando apenas um serviço

Durante o desenvolvimento, nem sempre é necessário reiniciar tudo.

Para reiniciar apenas o PHP:

```bash
docker compose restart app
```

Para reiniciar apenas o Nginx:

```bash
docker compose restart nginx
```

Para reiniciar apenas o banco:

```bash
docker compose restart postgres
```

Na maioria dos casos, alterações comuns em arquivos PHP não exigem reinicialização do container, pois o código do projeto está montado como volume.

---

# 20. Alterações no código

O projeto utiliza o seguinte volume:

```text
./:/var/www/portalsemed
```

Isso significa que os arquivos do projeto local são compartilhados com o container PHP.

Portanto, alterações em arquivos como:

```text
app/Controllers/
app/Models/
app/Views/
public/
```

ficam disponíveis imediatamente dentro do container.

Normalmente não é necessário executar:

```bash
docker compose build
```

a cada alteração de código.

O rebuild deve ser utilizado principalmente quando houver alterações no:

```text
docker/php/Dockerfile
```

---

# 21. Reconstruindo apenas o PHP

Caso seja necessário reconstruir apenas o serviço da aplicação:

```bash
docker compose build app
```

Depois:

```bash
docker compose up -d app
```

Também pode ser utilizado:

```bash
docker compose up -d --build app
```

---

# 22. Verificando a saúde do banco

O PostgreSQL possui um healthcheck configurado.

Para verificar o status:

```bash
docker compose ps
```

O banco deve aparecer como:

```text
healthy
```

Se estiver:

```text
unhealthy
```

verifique os logs:

```bash
docker compose logs postgres
```

---

# 23. Reinicializando o banco

Para reiniciar apenas o container PostgreSQL:

```bash
docker compose restart postgres
```

Esse comando não remove os dados.

O volume:

```text
postgres_data
```

continua preservado.

---

# 24. Apagando completamente o banco

Durante o desenvolvimento, pode ser necessário recriar o banco do zero.

Execute:

```bash
docker compose down -v
```

Depois:

```bash
docker compose up -d
```

Ou:

```bash
docker compose up -d --build
```

## ATENÇÃO

O comando:

```bash
docker compose down -v
```

remove os volumes do projeto.

Isso significa que os dados do PostgreSQL serão apagados.

O banco será recriado utilizando:

```text
database/schema.sql
```

Esse procedimento deve ser utilizado apenas quando for realmente necessário.

---

# 25. Executando o Certbot

O Certbot utiliza um profile chamado:

```text
ssl
```

Para iniciar o serviço:

```bash
docker compose --profile ssl up -d certbot
```

Esse serviço é opcional e não é necessário para o desenvolvimento local padrão.

---

# 26. Acessando o portal

Com os containers funcionando, o portal deve estar disponível em:

```text
http://localhost
```

O fluxo de acesso é:

```text
Navegador
    │
    ▼
Nginx
    │
    ▼
PHP-FPM
    │
    ▼
Aplicação
    │
    ▼
PostgreSQL
```

---

# 27. Verificando se o PHP está funcionando

Para verificar a versão do PHP dentro do container:

```bash
docker compose exec app php -v
```

Para listar as extensões:

```bash
docker compose exec app php -m
```

As extensões necessárias para PostgreSQL incluem:

```text
PDO
pdo_pgsql
pgsql
```

---

# 28. Verificando a conexão entre containers

Os containers se comunicam através da rede:

```text
portalsemed-network
```

Dentro do container PHP, o banco deve ser acessado utilizando:

```text
DB_HOST=postgres
```

Não deve ser utilizado:

```text
DB_HOST=localhost
```

Isso acontece porque, dentro de um container, `localhost` representa o próprio container.

A comunicação correta ocorre pelo nome do serviço:

```text
app → postgres
```

---

# 29. Verificando redes Docker

Para listar as redes:

```bash
docker network ls
```

Para inspecionar a rede do projeto:

```bash
docker network inspect portalsemed_portalsemed-network
```

O nome exato pode variar dependendo do nome da pasta do projeto.

---

# 30. Limpando containers parados

Para remover containers parados:

```bash
docker container prune
```

## ATENÇÃO

Esse comando remove todos os containers parados do Docker, não apenas os do Portal SEMED.

---

# 31. Limpando imagens não utilizadas

Para remover imagens não utilizadas:

```bash
docker image prune
```

Para uma limpeza mais agressiva:

```bash
docker system prune
```

## ATENÇÃO

Esse comando pode remover:

* containers parados;
* redes não utilizadas;
* imagens não utilizadas;
* cache.

Utilize com cuidado.

---

# 32. Problemas comuns

## 32.1 Porta 80 já está sendo utilizada

Se o Nginx não iniciar, pode existir outro serviço utilizando a porta:

```text
80
```

Verifique os containers:

```bash
docker ps
```

Também pode haver um servidor como Apache, IIS ou outro Nginx utilizando a porta.

---

## 32.2 Erro de conexão com PostgreSQL

Verifique:

```bash
docker compose logs postgres
```

Depois, confirme se o banco está saudável:

```bash
docker compose ps
```

Também verifique o arquivo:

```text
.env
```

---

## 32.3 Erro 502 Bad Gateway

Um erro:

```text
502 Bad Gateway
```

normalmente indica que o Nginx não conseguiu se comunicar corretamente com o PHP-FPM.

Verifique:

```bash
docker compose ps
```

Depois:

```bash
docker compose logs app
```

E:

```bash
docker compose logs nginx
```

---

## 32.4 Alteração no Dockerfile não teve efeito

Reconstrua a imagem:

```bash
docker compose up -d --build
```

Se necessário:

```bash
docker compose down
docker compose up -d --build
```

---

## 32.5 O banco não aplicou o schema.sql

O PostgreSQL executa os scripts de inicialização apenas na primeira criação do volume.

Se o volume já existir, alterações posteriores em:

```text
database/schema.sql
```

não serão aplicadas automaticamente.

Durante o desenvolvimento, para recriar o banco:

```bash
docker compose down -v
docker compose up -d
```

---

# 33. Comandos mais utilizados

## Iniciar o projeto

```bash
docker compose up -d
```

## Iniciar reconstruindo as imagens

```bash
docker compose up -d --build
```

## Ver containers

```bash
docker compose ps
```

## Ver todos os logs

```bash
docker compose logs -f
```

## Ver logs do PHP

```bash
docker compose logs -f app
```

## Ver logs do banco

```bash
docker compose logs -f postgres
```

## Ver logs do Nginx

```bash
docker compose logs -f nginx
```

## Entrar no container PHP

```bash
docker compose exec app sh
```

## Entrar no PostgreSQL

```bash
docker compose exec postgres psql -U postgres -d portalsemed
```

## Reiniciar o PHP

```bash
docker compose restart app
```

## Reiniciar o Nginx

```bash
docker compose restart nginx
```

## Reiniciar o banco

```bash
docker compose restart postgres
```

## Parar o projeto

```bash
docker compose down
```

## Apagar containers e volumes

```bash
docker compose down -v
```

---

# 34. Fluxo recomendado de desenvolvimento

Para iniciar o trabalho:

```bash
docker compose up -d
```

Verificar os containers:

```bash
docker compose ps
```

Caso algo não esteja funcionando:

```bash
docker compose logs -f
```

Para verificar especificamente o PHP:

```bash
docker compose logs -f app
```

Ao finalizar o trabalho:

```bash
docker compose down
```

Normalmente não é necessário reconstruir o projeto a cada alteração de código.

Utilize:

```bash
docker compose up -d --build
```

somente quando houver alterações relacionadas à construção dos containers.

---

# 35. Regra prática

Durante o desenvolvimento, a sequência mais comum será:

```text
1. docker compose up -d
2. desenvolver
3. docker compose logs -f app
4. testar
5. docker compose down
```

O Docker deve ser utilizado como a infraestrutura do projeto, não como uma entidade mística que só pode ser acordada durante eclipses. Se algo der errado, os logs quase sempre sabem mais do que parecem.

---

## Resumo

O ambiente Docker do Portal SEMED é composto principalmente por:

```text
Nginx
   ↓
PHP-FPM
   ↓
PostgreSQL
```

Os comandos mais importantes para o desenvolvimento são:

```bash
docker compose up -d
docker compose ps
docker compose logs -f
docker compose exec app sh
docker compose down
```

Para reconstruir imagens:

```bash
docker compose up -d --build
```

Para recriar completamente o banco de desenvolvimento:

```bash
docker compose down -v
docker compose up -d --build
```

> **Atenção:** esse último procedimento remove os dados persistidos no PostgreSQL.

---

## Status do documento

**Projeto:** Portal SEMED
**Documento:** Guia de Utilização do Docker
**Arquivo:** `docs/DOCKER.md`
**Ambiente:** Desenvolvimento e manutenção local
**Containers principais:** Nginx, PHP-FPM e PostgreSQL