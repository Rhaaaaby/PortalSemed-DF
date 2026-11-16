# Piloto gratuito: Render, Neon e Cloudflare R2

Este guia prepara uma instância de teste do Portal SEMED. O plano gratuito do
Render pode suspender a aplicação quando ela fica sem uso; o banco e as imagens
ficam em serviços separados. Isso não substitui hospedagem institucional nem é
adequado para dados pessoais reais sem autorização da prefeitura.

## 1. Preparar o banco no Neon

1. Crie um projeto PostgreSQL e um usuário de aplicação no Neon.
2. Na página de conexão, copie a URL **pooled** (host com `-pooler`) para o
   serviço web do Render. Ela deve usar TLS (`sslmode=require`).
3. Crie um banco vazio para o piloto. Não importe os dados locais nem dados de
   usuários reais.
4. No SQL Editor do Neon, execute o conteúdo de `database/schema.sql`.

Se o banco já tiver tabelas e dados, não execute o schema inicial sobre ele.
Faça backup e aplique, uma única vez, as migrations pendentes em
`database/migrations/`, em ordem.

O pooler do Neon usa modo de transação; esta aplicação usa conexões PDO
curtas por requisição e não depende de estado de sessão no PostgreSQL.
Consulte também a [documentação oficial de pooling do Neon](https://neon.com/docs/connect/connection-pooling).

## 2. Preparar o bucket de imagens no Cloudflare R2

1. Crie um bucket dedicado ao piloto.
2. Crie credenciais de API limitadas a esse bucket, com permissão de leitura e
   gravação de objetos.
3. Habilite acesso público de leitura para o bucket de teste e copie a URL
   pública base atribuída pelo R2 (`r2.dev`) ou o domínio público configurado.
4. Guarde o Account ID, bucket, Access Key ID e Secret Access Key em local
   seguro. Nunca os inclua no Git ou em mensagens.

`r2.dev` é apropriado somente para este piloto. Revise as opções de acesso
público e limites atuais diretamente no painel da Cloudflare.

## 3. Criar o serviço web no Render

1. Crie um **Web Service** conectado ao repositório e branch do piloto.
2. Selecione **Docker**, defina `PortalSemed` como diretório raiz (Root
   Directory) e use `Dockerfile.render` como Dockerfile. Assim, a pasta
   `PortalSemed` será o contexto de build.
3. Não adicione disco persistente ao serviço. Os uploads devem ir para o R2.
4. Configure estas variáveis no painel do Render:

| Variável | Valor |
| --- | --- |
| `APP_ENV` | `production` |
| `APP_URL` | URL HTTPS fornecida pelo Render |
| `DATABASE_URL` | URL pooled do Neon com `sslmode=require` |
| `IMAGE_STORAGE_DRIVER` | `r2` |
| `R2_ACCOUNT_ID` | Account ID da Cloudflare |
| `R2_BUCKET` | Nome do bucket |
| `R2_ACCESS_KEY_ID` | Access Key ID criada para o bucket |
| `R2_SECRET_ACCESS_KEY` | Secret Access Key criada para o bucket |
| `R2_PUBLIC_BASE_URL` | URL HTTPS pública base do bucket, sem `/` no final |
| `SMTP_HOST` | Host SMTP autorizado |
| `SMTP_PORT` | Porta SMTP, normalmente `587` |
| `SMTP_USER` | Usuário SMTP |
| `SMTP_PASSWORD` | Senha/app password SMTP |
| `SMTP_SECURE` | `tls` |
| `MAIL_FROM_EMAIL` | Remetente autorizado |
| `MAIL_FROM_NAME` | `Portal SEMED` |

O Render fornece `PORT` automaticamente. O container escuta essa porta; não
defina uma porta fixa para o serviço. Insira todos os segredos diretamente no
painel do provedor, não no repositório nem no chat.

## 4. Criar o primeiro administrador

Depois que a aplicação e o banco estiverem disponíveis, crie o primeiro usuário
administrador em um ambiente administrativo seguro que tenha acesso ao banco.
Passe temporariamente `FIRST_ADMIN_NAME`, `FIRST_ADMIN_CPF`, `FIRST_ADMIN_EMAIL`
e `FIRST_ADMIN_PASSWORD` ao comando:

```sh
php app/scripts/create-first-admin.php
```

Se executar o comando fora do Render, configure também a `DATABASE_URL` do Neon
e use uma instalação PHP com `pdo_pgsql`. Não inclua as variáveis no Dockerfile,
no Git ou em logs; remova-as imediatamente após a criação. Use dados fictícios
no piloto.

## 5. Verificar o piloto

Confirme no endereço HTTPS do Render:

- carregamento da página inicial e das notícias;
- login e logout;
- envio de e-mail de recuperação, se o SMTP estiver autorizado;
- upload de logo, carrossel e imagem de notícia;
- acesso às imagens após um novo deploy, confirmando que os endereços apontam
  para o R2.

O Render gratuito pode levar algum tempo para acordar após inatividade. Antes
de qualquer uso institucional, obtenha aprovação, hospedagem e domínio
institucionais, políticas de privacidade e backups.
