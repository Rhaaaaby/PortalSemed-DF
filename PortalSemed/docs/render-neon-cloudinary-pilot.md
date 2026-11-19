# Piloto: Render, Neon e Cloudinary

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

## 2. Preparar o armazenamento de imagens no Cloudinary

1. Use o plano Free e confira os limites atuais na
   [página de preços do Cloudinary](https://cloudinary.com/pricing).
2. No Cloudinary Console, abra [Settings > API Keys](https://console.cloudinary.com/app/settings/api-keys)
   e copie o valor **API environment variable** (`cloudinary://...`).
3. Configure essa variável como segredo no Render. Nunca a inclua no Git ou em
   mensagens; ela contém o API secret.

As imagens enviadas ao portal serão entregues publicamente pelo Cloudinary.
Use somente imagens apropriadas para publicação e dados fictícios no piloto.

## 3. Criar o serviço web no Render

1. Crie um **Web Service** conectado ao repositório e branch do piloto.
2. Selecione **Docker**, defina `PortalSemed` como diretório raiz (Root
   Directory) e use `Dockerfile.render` como Dockerfile. Assim, a pasta
   `PortalSemed` será o contexto de build.
3. Não adicione disco persistente ao serviço. Os uploads irão para o Cloudinary.
4. Configure estas variáveis no painel do Render:

| Variável | Valor |
| --- | --- |
| `APP_ENV` | `production` |
| `APP_URL` | URL HTTPS fornecida pelo Render |
| `DATABASE_URL` | URL pooled do Neon com `sslmode=require` |
| `IMAGE_STORAGE_DRIVER` | `cloudinary` |
| `CLOUDINARY_URL` | API environment variable do Cloudinary |
| `SMTP_HOST` | Host SMTP autorizado |
| `SMTP_PORT` | Porta SMTP autorizada pelo provedor, normalmente `587` |
| `SMTP_USER` | Usuário SMTP |
| `SMTP_PASSWORD` | Senha/app password SMTP |
| `SMTP_SECURE` | `tls` |
| `MAIL_FROM_EMAIL` | Remetente autorizado |
| `MAIL_FROM_NAME` | `Portal SEMED` |

No plano Free do Render, conexões de saída nas portas `25`, `465` e `587` são
bloqueadas; a porta `25` é bloqueada em todos os serviços. Se usar o plano Free,
prefira uma API de e-mail via HTTPS ou uma porta SMTP alternativa, como `2525`,
somente se o provedor oferecer suporte a ela.

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
  para o Cloudinary.

O Render gratuito pode levar algum tempo para acordar após inatividade. Antes
de qualquer uso institucional, obtenha aprovação, hospedagem e domínio
institucionais, políticas de privacidade e backups.
