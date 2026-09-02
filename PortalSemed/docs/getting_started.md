# Portal SEMED — Guia de Acesso e Configuração Inicial

## 1. Objetivo

Este documento explica como acessar o projeto **Portal SEMED** desde o início.

O objetivo é orientar um novo desenvolvedor para:

1. criar ou acessar uma conta no GitHub;
2. acessar o repositório do projeto;
3. clonar o código para o computador;
4. configurar o ambiente local;
5. instalar as dependências;
6. configurar as variáveis de ambiente;
7. iniciar os containers Docker;
8. acessar o projeto no navegador;
9. realizar alterações;
10. enviar alterações para o repositório.

Ao final deste processo, o desenvolvedor deverá possuir uma cópia funcional do Portal SEMED executando localmente.

---

# 2. Tecnologias utilizadas

O Portal SEMED utiliza, entre outras, as seguintes tecnologias:

* Git;
* GitHub;
* PHP;
* Composer;
* Docker;
* Docker Compose;
* PostgreSQL;
* Nginx;
* JavaScript;
* HTML;
* CSS.

A infraestrutura principal do projeto é:

```text
GitHub
   │
   ▼
Clone do repositório
   │
   ▼
Computador do desenvolvedor
   │
   ├── Código-fonte
   ├── Docker
   ├── PHP-FPM
   ├── PostgreSQL
   └── Nginx
          │
          ▼
       Navegador
```

---

# 3. Pré-requisitos

Antes de acessar o projeto, instale as seguintes ferramentas.

## 3.1 Git

O Git é utilizado para baixar o projeto e controlar as alterações no código.

Verifique se está instalado:

```bash
git --version
```

Se o comando retornar uma versão, o Git está instalado corretamente.

---

## 3.2 Conta no GitHub

É necessário possuir uma conta no GitHub e ter acesso ao repositório do Portal SEMED.

Após receber acesso ao projeto, o repositório poderá ser acessado através da organização ou usuário responsável pelo código.

Caso o repositório seja privado, é necessário que o responsável pelo projeto adicione o desenvolvedor como colaborador.

---

## 3.3 Docker

O Docker é utilizado para executar a infraestrutura do projeto.

Verifique a instalação:

```bash
docker --version
```

Também verifique o Docker Compose:

```bash
docker compose version
```

O projeto utiliza o comando moderno:

```bash
docker compose
```

e não necessariamente:

```bash
docker-compose
```

---

## 3.4 Composer

O Composer é utilizado para gerenciar as dependências PHP.

Verifique a instalação:

```bash
composer --version
```

---

# 4. Acessando o repositório

Após receber acesso ao projeto, abra o repositório no GitHub.

O endereço do repositório deve ser fornecido pelo responsável pelo projeto.

Exemplo:

```text
https://github.com/USUARIO/portalSemed.git
```

Copie a URL do repositório utilizando a opção:

```text
Code → HTTPS
```

ou SSH, caso a chave SSH já esteja configurada.

---

# 5. Clonando o projeto

Abra o terminal na pasta onde deseja armazenar o projeto.

Execute:

```bash
git clone URL_DO_REPOSITORIO
```

Exemplo:

```bash
git clone https://github.com/USUARIO/portalSemed.git
```

Após o download, entre na pasta:

```bash
cd portalSemed
```

Para verificar os arquivos:

### Windows

```cmd
dir
```

### Linux/macOS

```bash
ls
```

A estrutura deverá conter arquivos semelhantes a:

```text
.env.example
composer.json
composer.lock
docker-compose.yml

app/
database/
docker/
docs/
public/
tests/
```

---

# 6. Verificando o repositório Git

Depois de entrar na pasta do projeto, execute:

```bash
git status
```

O resultado esperado é semelhante a:

```text
On branch main
nothing to commit, working tree clean
```

Isso significa que o repositório foi clonado corretamente e não existem alterações locais.

Para verificar a origem do projeto:

```bash
git remote -v
```

O resultado deve mostrar o repositório remoto chamado:

```text
origin
```

---

# 7. Configurando o arquivo .env

O projeto não deve utilizar diretamente arquivos contendo credenciais reais que estejam armazenados no repositório.

Existe um arquivo de exemplo:

```text
.env.example
```

Crie o arquivo `.env`.

## Windows PowerShell

```powershell
Copy-Item .env.example .env
```

## Windows CMD

```cmd
copy .env.example .env
```

## Linux/macOS

```bash
cp .env.example .env
```

Depois, abra o arquivo:

```text
.env
```

e configure as variáveis necessárias.

As principais variáveis utilizadas pelo projeto incluem:

```text
DB_NAME
DB_USER
DB_PASS
JWT_SECRET
```

Exemplo estrutural:

```env
DB_NAME=nome_do_banco
DB_USER=usuario
DB_PASS=senha_segura
JWT_SECRET=chave_secreta
```

Os valores reais devem seguir a configuração definida pela equipe do projeto.

> **Importante:** o arquivo `.env` não deve ser enviado para o GitHub caso contenha credenciais ou informações sensíveis.

---

# 8. Instalando as dependências PHP

Na raiz do projeto, execute:

```bash
composer install
```

Esse comando utiliza:

```text
composer.json
composer.lock
```

para instalar as dependências necessárias.

A pasta:

```text
vendor/
```

será criada ou atualizada.

Não altere manualmente os arquivos internos do `vendor`, pois eles são gerenciados pelo Composer.

---

# 9. Estrutura Docker do projeto

O projeto utiliza o arquivo:

```text
docker-compose.yml
```

Os principais serviços são:

```text
postgres
app
nginx
```

A estrutura de comunicação é:

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
PostgreSQL
```

Os containers possuem os seguintes nomes:

```text
portalsemed-web
portalsemed-app
portalsemed-db
```

---

# 10. Iniciando o projeto

Na raiz do projeto, execute:

```bash
docker compose up -d --build
```

Esse comando irá:

1. construir a imagem da aplicação PHP;
2. criar a rede Docker;
3. iniciar o PostgreSQL;
4. inicializar o banco de dados;
5. iniciar o PHP-FPM;
6. iniciar o Nginx.

O parâmetro:

```text
-d
```

executa os containers em segundo plano.

O parâmetro:

```text
--build
```

força a construção das imagens necessárias.

---

# 11. Verificando os containers

Após iniciar o projeto, execute:

```bash
docker compose ps
```

Os serviços principais devem aparecer em execução.

Os containers esperados são:

```text
portalsemed-db
portalsemed-app
portalsemed-web
```

O PostgreSQL possui um sistema de verificação de saúde.

Aguarde até que o banco esteja disponível antes de considerar o ambiente totalmente iniciado.

---

# 12. Acessando o projeto

Com os containers funcionando, abra o navegador.

Acesse:

```text
http://localhost
```

O Nginx será responsável por receber a requisição.

O fluxo será:

```text
Navegador
    │
    ▼
Nginx
    │
    ▼
public/index.php
    │
    ▼
Controllers
    │
    ▼
Models
    │
    ▼
PostgreSQL
```

---

# 13. Verificando problemas durante a inicialização

Se o projeto não funcionar, verifique os logs.

Todos os serviços:

```bash
docker compose logs -f
```

Aplicação PHP:

```bash
docker compose logs -f app
```

PostgreSQL:

```bash
docker compose logs -f postgres
```

Nginx:

```bash
docker compose logs -f nginx
```

Para sair da visualização dos logs:

```text
Ctrl + C
```

Esse comando não encerra os containers.

---

# 14. Atualizando o projeto

Antes de começar a trabalhar, é recomendado verificar se existem alterações remotas.

Primeiro:

```bash
git status
```

Depois:

```bash
git pull origin main
```

Caso o projeto utilize outra branch principal, substitua:

```text
main
```

pelo nome correto.

Exemplo:

```bash
git pull origin develop
```

---

# 15. Criando uma nova branch

Não é recomendado desenvolver diretamente na branch principal.

Para criar uma nova branch:

```bash
git checkout -b nome-da-branch
```

Exemplo:

```bash
git checkout -b feature/noticias
```

Uma convenção recomendada é:

```text
feature/nome-da-funcionalidade
fix/nome-do-problema
docs/nome-da-documentacao
refactor/nome-da-refatoracao
```

Exemplos:

```text
feature/login
feature/cadastro-usuario
fix/erro-upload
docs/docker-guide
refactor/noticia-controller
```

Para verificar a branch atual:

```bash
git branch
```

A branch atual será indicada com:

```text
*
```

---

# 16. Realizando alterações

Após criar uma branch, realize as alterações necessárias no projeto.

Antes de enviar qualquer alteração, verifique:

```bash
git status
```

O Git mostrará os arquivos modificados.

Exemplo:

```text
modified: app/Controllers/UserController.php
modified: public/assets/js/auth.js
```

---

# 17. Adicionando arquivos ao Git

Para adicionar um arquivo específico:

```bash
git add caminho/do/arquivo
```

Exemplo:

```bash
git add app/Controllers/UserController.php
```

Para adicionar todas as alterações:

```bash
git add .
```

Antes de utilizar `git add .`, recomenda-se verificar:

```bash
git status
```

Assim, arquivos indesejados não serão enviados acidentalmente.

---

# 18. Criando um commit

Depois de adicionar os arquivos:

```bash
git commit -m "Descrição da alteração"
```

Exemplo:

```bash
git commit -m "Adiciona validação no cadastro de usuários"
```

Uma boa mensagem de commit deve explicar claramente a alteração.

Exemplos:

```text
Adiciona autenticação JWT
Corrige erro no upload de imagens
Atualiza documentação do Docker
Cria tela de gerenciamento de notícias
Remove arquivos legados
```

Evite mensagens como:

```text
teste
alterações
arrumei
aaa
funciona agora
```

O Git já registra o código. A mensagem do commit deve registrar a intenção.

---

# 19. Enviando alterações para o GitHub

Após criar o commit, envie a branch:

```bash
git push origin nome-da-branch
```

Exemplo:

```bash
git push origin feature/noticias
```

Caso seja a primeira vez enviando a branch:

```bash
git push -u origin feature/noticias
```

Depois disso, normalmente será possível utilizar apenas:

```bash
git push
```

---

# 20. Criando um Pull Request

Depois de enviar a branch para o GitHub:

1. abra o repositório;
2. localize a branch enviada;
3. selecione a opção para criar um Pull Request;
4. compare a branch de desenvolvimento com a branch de destino;
5. escreva uma descrição clara;
6. envie o Pull Request.

A descrição deve informar:

* o que foi alterado;
* por que foi alterado;
* quais partes do sistema foram afetadas;
* como a funcionalidade pode ser testada.

Exemplo:

```text
## Alterações

- Adicionada validação de CPF.
- Corrigido tratamento de erro no login.
- Atualizados os testes relacionados à autenticação.

## Como testar

1. Iniciar os containers.
2. Acessar a página de login.
3. Testar CPF válido e inválido.
4. Verificar a resposta da API.
```

---

# 21. Atualizando uma branch existente

Antes de continuar o desenvolvimento, atualize a branch principal:

```bash
git checkout main
```

Depois:

```bash
git pull origin main
```

Volte para sua branch:

```bash
git checkout nome-da-sua-branch
```

Depois, integre as alterações.

Uma opção é utilizar merge:

```bash
git merge main
```

Outra opção, caso seja adotada pela equipe, é utilizar rebase:

```bash
git rebase main
```

A estratégia utilizada deve ser definida pela equipe para evitar históricos confusos.

---

# 22. Resolvendo conflitos

Um conflito ocorre quando o Git não consegue decidir automaticamente qual alteração deve permanecer.

Ao ocorrer um conflito:

1. abra o arquivo indicado;
2. identifique os marcadores de conflito;
3. escolha ou combine as alterações necessárias;
4. remova os marcadores;
5. salve o arquivo.

Os marcadores geralmente possuem esta estrutura:

```text
<<<<<<< HEAD
Alteração local
=======
Alteração recebida
>>>>>>> main
```

Depois de resolver:

```bash
git add .
```

Se estiver utilizando merge:

```bash
git commit
```

Se estiver utilizando rebase:

```bash
git rebase --continue
```

Conflitos não são monstros. São apenas o Git olhando para duas pessoas que mexeram na mesma coisa e dizendo: “decidam entre vocês”.

---

# 23. Fluxo diário recomendado

Antes de começar:

```bash
git checkout main
git pull origin main
git checkout nome-da-sua-branch
```

Atualize sua branch:

```bash
git merge main
```

Inicie o ambiente:

```bash
docker compose up -d
```

Verifique:

```bash
docker compose ps
```

Desenvolva e teste.

Verifique as alterações:

```bash
git status
```

Adicione os arquivos:

```bash
git add .
```

Crie o commit:

```bash
git commit -m "Descrição clara da alteração"
```

Envie para o GitHub:

```bash
git push
```

Depois, crie ou atualize o Pull Request.

---

# 24. Fluxo completo desde o zero

Um novo desenvolvedor pode seguir este fluxo:

```text
1. Receber acesso ao repositório
            │
            ▼
2. Clonar o projeto
            │
            ▼
3. Entrar na pasta
            │
            ▼
4. Criar o arquivo .env
            │
            ▼
5. Configurar variáveis de ambiente
            │
            ▼
6. Executar composer install
            │
            ▼
7. Executar docker compose up -d --build
            │
            ▼
8. Verificar docker compose ps
            │
            ▼
9. Acessar http://localhost
            │
            ▼
10. Criar uma branch
            │
            ▼
11. Desenvolver e testar
            │
            ▼
12. git add
            │
            ▼
13. git commit
            │
            ▼
14. git push
            │
            ▼
15. Criar Pull Request
```

---

# 25. Comandos essenciais

## Clonar o projeto

```bash
git clone URL_DO_REPOSITORIO
```

## Entrar na pasta

```bash
cd portalSemed
```

## Verificar alterações

```bash
git status
```

## Atualizar o projeto

```bash
git pull origin main
```

## Criar branch

```bash
git checkout -b feature/nova-funcionalidade
```

## Adicionar alterações

```bash
git add .
```

## Criar commit

```bash
git commit -m "Descrição da alteração"
```

## Enviar alterações

```bash
git push
```

## Instalar dependências

```bash
composer install
```

## Iniciar o projeto

```bash
docker compose up -d --build
```

## Ver containers

```bash
docker compose ps
```

## Ver logs

```bash
docker compose logs -f
```

## Parar o projeto

```bash
docker compose down
```

---

# 26. Cuidados importantes

Nunca envie para o GitHub:

```text
.env
```

ou qualquer arquivo contendo:

* senhas;
* tokens;
* chaves JWT;
* credenciais do banco;
* certificados privados;
* informações sensíveis.

Antes de executar:

```bash
git add .
```

sempre verifique:

```bash
git status
```

Também não é recomendado modificar diretamente:

```text
vendor/
```

As dependências devem ser gerenciadas pelo Composer.

---

# 27. Checklist para um novo desenvolvedor

* [ ] Possui conta no GitHub.
* [ ] Possui acesso ao repositório.
* [ ] Git instalado.
* [ ] Docker instalado.
* [ ] Docker Compose funcionando.
* [ ] Composer instalado.
* [ ] Projeto clonado.
* [ ] Arquivo `.env` criado.
* [ ] Variáveis configuradas.
* [ ] Dependências instaladas.
* [ ] Containers iniciados.
* [ ] PostgreSQL funcionando.
* [ ] PHP funcionando.
* [ ] Nginx funcionando.
* [ ] Portal acessível em `http://localhost`.
* [ ] Branch de desenvolvimento criada.
* [ ] Alterações testadas.
* [ ] Commit criado.
* [ ] Alterações enviadas ao GitHub.
* [ ] Pull Request criado quando aplicável.

---

# 28. Resumo

O processo básico para acessar o Portal SEMED é:

```bash
git clone URL_DO_REPOSITORIO
cd portalSemed
```

Criar o ambiente:

```bash
cp .env.example .env
```

Configurar o `.env`.

Instalar dependências:

```bash
composer install
```

Iniciar o ambiente:

```bash
docker compose up -d --build
```

Verificar os containers:

```bash
docker compose ps
```

E acessar:

```text
http://localhost
```

A partir desse ponto, o desenvolvedor deve criar sua própria branch para realizar alterações:

```bash
git checkout -b feature/nova-funcionalidade
```

Após concluir o trabalho:

```bash
git add .
git commit -m "Descrição clara da alteração"
git push -u origin feature/nova-funcionalidade
```

---

## Status do documento

**Projeto:** Portal SEMED
**Documento:** Guia de Acesso e Configuração Inicial
**Arquivo:** `docs/GETTING_STARTED.md`
**Público:** Novos desenvolvedores e colaboradores do projeto
**Abrangência:** GitHub, Git, configuração local, Composer, Docker e fluxo inicial de desenvolvimento