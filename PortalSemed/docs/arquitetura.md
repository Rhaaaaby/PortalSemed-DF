# Arquitetura do Projeto — Portal SEMED

## 1. Visão geral

O **Portal da Educação — SEMED** é uma aplicação web desenvolvida para disponibilizar informações e serviços relacionados à educação municipal, incluindo notícias, documentos, calendário escolar, serviços e funcionalidades administrativas.

A aplicação utiliza uma arquitetura baseada na separação de responsabilidades entre:

* **Frontend**
* **Backend**
* **Banco de dados**
* **API**
* **Infraestrutura**
* **Arquivos estáticos**
* **Testes**

A estrutura atual segue uma organização inspirada no padrão **MVC (Model-View-Controller)**, com um **Front Controller** responsável pelo recebimento e direcionamento das requisições.

---

# 2. Arquitetura geral

O fluxo principal da aplicação pode ser representado da seguinte forma:

```text
                        USUÁRIO
                           │
                           ▼
                    ┌─────────────┐
                    │   Nginx     │
                    │  Web Server │
                    └──────┬──────┘
                           │
                           ▼
                 ┌──────────────────┐
                 │ public/index.php │
                 │  Front Controller │
                 └────────┬─────────┘
                          │
              ┌───────────┼────────────┐
              │           │            │
              ▼           ▼            ▼
        Rotas Web      Rotas API    Arquivos
              │           │        estáticos
              ▼           ▼
        Controllers   API Router
              │
              ▼
           Models
              │
              ▼
         Banco de Dados
              │
              ▼
           Views
              │
              ▼
           Usuário
```

O objetivo dessa arquitetura é evitar que cada página tenha sua própria lógica de processamento.

Em vez disso, as requisições passam por um ponto central e são encaminhadas para o componente responsável por tratá-las.

---

# 3. Estrutura de diretórios

A estrutura atual do projeto é:

```text
portalSemed/
│
├── .env
├── .env.example
├── .env.production
├── composer.json
├── composer.lock
├── docker-compose.yml
│
├── app/
│   ├── bootstrap.php
│   │
│   ├── config/
│   │   └── database.php
│   │
│   ├── Controllers/
│   │   ├── NoticiaController.php
│   │   └── UserController.php
│   │
│   ├── Database/
│   │   └── Connection.php
│   │
│   ├── Models/
│   │   ├── Noticia.php
│   │   └── User.php
│   │
│   └── Views/
│       └── noticias/
│           ├── create.php
│           ├── edit.php
│           ├── index.php
│           └── view.php
│
├── database/
│   └── schema.sql
│
├── docker/
│   ├── nginx/
│   │   └── conf.d/
│   │       └── default.conf
│   │
│   └── php/
│       └── Dockerfile
│
├── docs/
│
├── public/
│   ├── .htaccess
│   ├── health.php
│   ├── index.php
│   │
│   ├── api/
│   │   └── router.php
│   │
│   └── assets/
│       ├── components/
│       │   ├── footer.html
│       │   └── header.html
│       │
│       ├── css/
│       │   ├── calendario.css
│       │   ├── documentos.css
│       │   ├── faleconosco.css
│       │   ├── noticias.css
│       │   ├── style.css
│       │   └── transparencia.css
│       │
│       ├── images/
│       │   ├── logo.png
│       │   └── uploads/
│       │
│       ├── js/
│       │   ├── auth.js
│       │   ├── config.js
│       │   ├── controller.js
│       │   ├── include-components.js
│       │   └── model.js
│       │
│       └── pages/
│           ├── cadastro.html
│           ├── calendario.html
│           ├── documentos.html
│           ├── faleconosco.html
│           ├── index.html
│           ├── login.html
│           ├── noticias.html
│           ├── perfil-admin.html
│           ├── perfil-funcionario.html
│           ├── recuperar-senha.html
│           ├── servicos.html
│           └── transparencia.html
│
├── tests/
│
└── vendor/
```

---

# 4. Diretório `app/`

O diretório `app/` contém o núcleo da aplicação.

Ele não deve ser tratado como uma pasta de arquivos públicos. Seu conteúdo representa a lógica interna do sistema.

A divisão principal é:

```text
app/
├── Controllers/
├── Models/
├── Views/
├── Database/
├── config/
└── bootstrap.php
```

---

# 5. `app/bootstrap.php`

O arquivo `bootstrap.php` é responsável pela inicialização da aplicação.

Ele deve concentrar as operações necessárias para preparar o ambiente antes que Controllers, Models ou outras partes da aplicação sejam executadas.

Entre suas responsabilidades podem estar:

* carregamento do Composer;
* carregamento das variáveis de ambiente;
* definição de constantes;
* inicialização de configurações;
* configuração do ambiente da aplicação;
* preparação das dependências.

O `public/index.php` utiliza esse arquivo como parte da inicialização da aplicação.

---

# 6. `app/config/`

Contém configurações internas da aplicação.

Atualmente:

```text
app/config/
└── database.php
```

O arquivo de configuração do banco deve concentrar informações necessárias para estabelecer a conexão com o banco de dados.

Informações sensíveis não devem ser armazenadas diretamente no código-fonte.

Esses valores devem ser obtidos por meio das variáveis de ambiente definidas nos arquivos `.env`.

---

# 7. `app/Controllers/`

Os Controllers representam a camada responsável por receber requisições e coordenar as operações da aplicação.

Atualmente:

```text
app/Controllers/
├── NoticiaController.php
└── UserController.php
```

## 7.1 NoticiaController

O `NoticiaController` controla as operações relacionadas às notícias.

Entre suas responsabilidades estão:

* listar notícias;
* criar notícias;
* visualizar uma notícia;
* editar notícias;
* excluir notícias;
* processar uploads de imagens;
* validar dados básicos recebidos;
* chamar o Model correspondente;
* selecionar a View apropriada;
* redirecionar o usuário após operações concluídas.

O fluxo de uma operação de criação, por exemplo, é:

```text
Usuário
   │
   ▼
/noticias?action=create
   │
   ▼
public/index.php
   │
   ▼
NoticiaController::create()
   │
   ├── GET → apresenta create.php
   │
   └── POST
        │
        ├── processa dados
        ├── processa imagem
        ├── chama Model
        └── redireciona
```

---

# 8. `app/Models/`

Os Models representam a camada de acesso e manipulação dos dados.

Atualmente:

```text
app/Models/
├── Noticia.php
└── User.php
```

O Model de notícias é responsável pelas operações relacionadas aos registros de notícias no banco.

Exemplos de operações:

```text
all()
find()
create()
update()
delete()
```

A ideia é que o Controller **não execute diretamente consultas SQL relacionadas à entidade**.

Em vez disso:

```text
Controller
    ↓
Model
    ↓
Database
```

Isso reduz o acoplamento e facilita a manutenção.

---

# 9. `app/Database/`

Contém componentes relacionados à conexão e comunicação com o banco.

Atualmente:

```text
app/Database/
└── Connection.php
```

O objetivo dessa camada é centralizar a criação e gerenciamento da conexão com o banco.

Assim, os Models não precisam conhecer detalhes de infraestrutura da conexão.

O fluxo esperado é:

```text
Model
   ↓
Connection
   ↓
Banco de Dados
```

---

# 10. `app/Views/`

As Views são responsáveis pela apresentação dos dados ao usuário.

Atualmente:

```text
app/Views/
└── noticias/
    ├── create.php
    ├── edit.php
    ├── index.php
    └── view.php
```

Cada arquivo representa uma tela/operação relacionada às notícias.

### `index.php`

Lista as notícias.

### `create.php`

Apresenta o formulário de criação de uma nova notícia.

### `edit.php`

Apresenta o formulário de edição de uma notícia existente.

### `view.php`

Apresenta os detalhes de uma notícia individual.

As Views devem evitar conter regras complexas de negócio.

A lógica deve permanecer no Controller/Model.

---

# 11. `public/`

O diretório `public/` representa a área pública da aplicação.

É o diretório que deve ser exposto pelo servidor web.

```text
public/
├── index.php
├── .htaccess
├── health.php
├── api/
└── assets/
```

Uma decisão arquitetural importante é:

> O servidor web deve apontar para `public/`, e não para a raiz do projeto.

Isso reduz a exposição de arquivos internos como:

```text
.env
composer.json
app/
database/
docker/
```

---

# 12. `public/index.php`

Este é o **Front Controller da aplicação**.

É o principal ponto de entrada das requisições HTTP.

Sua responsabilidade é:

1. inicializar a aplicação;
2. identificar a rota solicitada;
3. verificar requisições específicas;
4. encaminhar requisições para Controllers;
5. servir determinados recursos estáticos;
6. encaminhar requisições da API para o router correspondente.

Fluxo:

```text
HTTP Request
     ↓
public/index.php
     ↓
Identificação da rota
     ↓
┌──────────────┬──────────────┐
│              │              │
▼              ▼              ▼
Web           API          Static
│              │              │
▼              ▼              ▼
Controller   API Router    Arquivo
```

---

# 13. Rotas de notícias

As operações atuais de notícias utilizam a lógica de ações:

```text
/noticias?action=create
/noticias?action=view&id=1
/noticias?action=edit&id=1
/noticias?action=delete&id=1
/noticias
```

O `public/index.php` identifica a requisição e instancia:

```php
NoticiaController
```

Depois, determina a ação:

```text
index
create
view
edit
delete
```

O fluxo pode ser representado assim:

```text
/noticias
     ↓
NoticiaController::index()
     ↓
Noticia::all()
     ↓
Views/noticias/index.php
```

Para criação:

```text
/noticias?action=create
     ↓
NoticiaController::create()
     ↓
Views/noticias/create.php
```

---

# 14. `public/api/`

Contém a camada responsável pelas rotas da API.

Atualmente:

```text
public/api/
└── router.php
```

A API possui um fluxo independente das Views tradicionais.

Em termos conceituais:

```text
Cliente/API Request
       ↓
public/index.php
       ↓
public/api/router.php
       ↓
processamento da API
       ↓
JSON Response
```

Essa separação permite que o frontend JavaScript consuma dados sem necessariamente carregar uma View PHP.

---

# 15. `public/assets/`

Contém recursos públicos utilizados pelo frontend.

Está dividido em:

```text
assets/
├── components/
├── css/
├── images/
├── js/
└── pages/
```

---

# 16. `public/assets/components/`

Contém componentes HTML reutilizáveis.

Atualmente:

```text
components/
├── header.html
└── footer.html
```

Esses componentes são utilizados pelo JavaScript para montar partes comuns da interface.

Isso evita duplicação do mesmo cabeçalho e rodapé em diversas páginas.

---

# 17. `public/assets/css/`

Contém os estilos da interface.

Há um arquivo geral:

```text
style.css
```

e arquivos específicos para determinadas páginas ou módulos:

```text
calendario.css
documentos.css
faleconosco.css
noticias.css
transparencia.css
```

A estratégia atual permite que estilos gerais sejam centralizados e estilos específicos permaneçam isolados.

---

# 18. `public/assets/images/`

Contém imagens públicas do sistema.

Atualmente existe:

```text
images/
├── logo.png
└── uploads/
```

---

# 19. `public/assets/images/uploads/`

Essa pasta é destinada exclusivamente às imagens enviadas por usuários através do sistema.

Exemplo:

```text
uploads/
└── 1777902256_Screenshot_55.png
```

Arquivos PHP, HTML, JavaScript ou outros arquivos executáveis **não devem ser armazenados nessa pasta**.

Essa separação é importante tanto para organização quanto para segurança.

O `NoticiaController` utiliza essa localização para armazenar imagens associadas às notícias.

---

# 20. `public/assets/js/`

Contém os scripts JavaScript do frontend.

Atualmente:

```text
js/
├── auth.js
├── config.js
├── controller.js
├── include-components.js
└── model.js
```

Há uma organização conceitual semelhante ao MVC no frontend:

```text
model.js
    ↓
controller.js
    ↓
interface
```

Além disso:

* `auth.js` concentra funcionalidades relacionadas à autenticação;
* `config.js` concentra configurações do frontend;
* `include-components.js` carrega componentes reutilizáveis.

---

# 21. `public/assets/pages/`

Contém páginas HTML estáticas do portal.

Exemplos:

```text
index.html
login.html
cadastro.html
noticias.html
calendario.html
documentos.html
servicos.html
```

Essas páginas fazem parte da camada visual do portal.

É importante diferenciá-las das Views PHP:

```text
public/assets/pages/
    → páginas estáticas/frontend

app/Views/
    → Views processadas pelo backend
```

Essa distinção deve ser preservada.

---

# 22. Homepage

A homepage pública está localizada em:

```text
public/assets/pages/index.html
```

Ela representa a página inicial visual do portal.

O `public/index.php` possui lógica para encaminhar a requisição raiz para essa página.

Fluxo:

```text
GET /
 ↓
public/index.php
 ↓
public/assets/pages/index.html
```

Portanto, a homepage não deve ser confundida com:

```text
app/Views/noticias/index.php
```

que representa a listagem de notícias.

---

# 23. `database/`

Contém recursos relacionados à estrutura do banco de dados.

Atualmente:

```text
database/
└── schema.sql
```

O `schema.sql` representa a estrutura esperada do banco.

Ele deve ser utilizado como referência para:

* tabelas;
* campos;
* tipos;
* chaves primárias;
* chaves estrangeiras;
* relacionamentos;
* índices;
* restrições.

---

# 24. `docker/`

Contém arquivos relacionados à infraestrutura de execução da aplicação.

```text
docker/
├── nginx/
│   └── conf.d/
│       └── default.conf
│
└── php/
    └── Dockerfile
```

## Nginx

O Nginx funciona como servidor web/reverse proxy da aplicação.

## PHP

O `Dockerfile` define o ambiente necessário para execução do backend PHP.

O ambiente completo é orquestrado pelo:

```text
docker-compose.yml
```

---

# 25. Composer

O projeto utiliza Composer para gerenciamento de dependências PHP.

Arquivos:

```text
composer.json
composer.lock
```

As dependências instaladas estão disponíveis em:

```text
vendor/
```

O diretório `vendor/` é gerenciado pelo Composer e não deve ser alterado manualmente.

---

# 26. Variáveis de ambiente

O projeto possui:

```text
.env
.env.example
.env.production
```

A finalidade é separar configurações sensíveis e específicas de cada ambiente.

Exemplos de informações que normalmente devem ser configuradas por ambiente:

* credenciais do banco;
* host;
* porta;
* nome do banco;
* ambiente da aplicação;
* chaves secretas;
* configurações de API.

O arquivo `.env.example` deve servir como modelo sem conter credenciais reais.

---

# 27. Segurança de arquivos

A arquitetura estabelece uma separação importante entre arquivos públicos e internos.

### Internos

```text
app/
database/
docker/
.env
composer.json
```

### Públicos

```text
public/
```

O servidor web deve utilizar:

```text
public/
```

como document root.

Isso impede que arquivos internos sejam diretamente acessados pela internet.

---

# 28. Fluxo completo de uma requisição

Considere uma requisição:

```text
GET /noticias
```

O fluxo esperado é:

```text
Usuário
   │
   ▼
Nginx
   │
   ▼
public/index.php
   │
   ▼
Identificação da rota
   │
   ▼
NoticiaController
   │
   ▼
Noticia Model
   │
   ▼
Banco de Dados
   │
   ▼
NoticiaController
   │
   ▼
Views/noticias/index.php
   │
   ▼
HTML
   │
   ▼
Usuário
```

---

# 29. Fluxo de criação de notícia

Para:

```text
GET /noticias?action=create
```

temos:

```text
Usuário
 ↓
public/index.php
 ↓
NoticiaController::create()
 ↓
Views/noticias/create.php
```

Após o envio:

```text
POST /noticias?action=create
 ↓
NoticiaController::create()
 ↓
validação/processamento
 ↓
upload da imagem
 ↓
Noticia::create()
 ↓
Banco de Dados
 ↓
redirect /noticias
```

---

# 30. Fluxo de upload

Quando uma notícia possui uma imagem:

```text
Formulário
    ↓
$_FILES['imagem']
    ↓
NoticiaController
    ↓
verificação do upload
    ↓
criação do diretório, se necessário
    ↓
move_uploaded_file()
    ↓
public/assets/images/uploads/
```

O nome do arquivo é alterado durante o processamento para reduzir conflitos entre nomes de arquivos.

---

# 31. Separação de responsabilidades

A arquitetura atual deve respeitar as seguintes responsabilidades:

| Camada             | Responsabilidade             |
| ------------------ | ---------------------------- |
| Nginx              | Servir a aplicação           |
| `public/index.php` | Roteamento principal         |
| Controllers        | Coordenar requisições        |
| Models             | Manipular dados              |
| Database           | Conectar ao banco            |
| Views              | Apresentar dados             |
| API Router         | Processar requisições da API |
| JavaScript         | Interações do frontend       |
| CSS                | Apresentação visual          |
| Database schema    | Estrutura do banco           |

---

# 32. Decisões arquiteturais atuais

As seguintes decisões foram estabelecidas durante a organização do projeto:

### 32.1 Front Controller

O ponto principal de entrada da aplicação é:

```text
public/index.php
```

Não deve existir outro `index.php` na raiz desempenhando função semelhante.

### 32.2 Views organizadas por domínio

As Views de notícias estão agrupadas em:

```text
app/Views/noticias/
```

Isso facilita a expansão futura para outros módulos.

### 32.3 Uploads isolados

Arquivos enviados pelos usuários ficam em:

```text
public/assets/images/uploads/
```

A pasta deve conter somente arquivos permitidos pelo sistema.

### 32.4 Código interno fora do document root

O código da aplicação fica fora da área pública sempre que possível.

### 32.5 Arquivos legados removidos

Arquivos identificados como duplicados ou incompatíveis com a arquitetura atual foram removidos antes do início da fase de testes.

---

# 33. Estado atual da arquitetura

Neste momento, a aplicação apresenta a seguinte organização conceitual:

```text
                 ┌──────────────────┐
                 │      CLIENTE     │
                 └────────┬─────────┘
                          │
                          ▼
                 ┌──────────────────┐
                 │      NGINX       │
                 └────────┬─────────┘
                          │
                          ▼
                 ┌──────────────────┐
                 │ public/index.php │
                 └────────┬─────────┘
                          │
             ┌────────────┼─────────────┐
             │            │             │
             ▼            ▼             ▼
        Controllers      API        Assets/Pages
             │
             ▼
          Models
             │
             ▼
         Database
             │
             ▼
          Views
```

Essa estrutura será utilizada como **baseline arquitetural antes da execução dos testes**.

Qualquer alteração estrutural realizada posteriormente deverá ser registrada na documentação para manter a rastreabilidade do projeto.

---

# 34. Próxima etapa

Após a consolidação desta arquitetura, o projeto entra na fase de **validação e testes**.

A ordem recomendada é:

```text
1. Infraestrutura
        ↓
2. Servidor Web
        ↓
3. Homepage
        ↓
4. Assets
        ↓
5. Banco de Dados
        ↓
6. API
        ↓
7. Autenticação
        ↓
8. Notícias
        ↓
9. Uploads
        ↓
10. Segurança
        ↓
11. Testes de integração
        ↓
12. Testes finais
```

Cada teste deverá registrar:

* funcionalidade testada;
* pré-condições;
* procedimento;
* resultado esperado;
* resultado obtido;
* status;
* erro encontrado, caso exista;
* correção realizada;
* data da execução.

**Status inicial da documentação:** arquitetura consolidada antes da bateria de testes.

---

### Observação importante

Este documento descreve a **arquitetura atual observada no projeto**. Ele não deve ser interpretado como confirmação de que todas as funcionalidades estão funcionando corretamente. Essa confirmação será obtida somente durante a fase de testes.

Isso é importante porque separa duas coisas que costumam virar uma sopa no desenvolvimento:

> **“Está organizado dessa forma” ≠ “Está funcionando dessa forma.”**