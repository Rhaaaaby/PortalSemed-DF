# Publicação segura do Portal SEMED

Esta configuração de produção é separada do `docker-compose.yml` de
desenvolvimento. Ela publica somente o proxy HTTPS, mantém o PostgreSQL sem
porta pública, usa arquivos de segredo fora do Git e persiste o banco, os
uploads e os dados de certificados.

## Antes de publicar

- Use um servidor, domínio e conta de e-mail autorizados pela SEMED/prefeitura.
  O portal armazena CPF e e-mail; não use infraestrutura pessoal para dados
  reais sem aprovação institucional.
- Aponte o registro DNS `A` de `APP_DOMAIN` para o IP público do servidor.
  Se houver IPv6, configure também o registro `AAAA` corretamente.
- Libere no firewall somente SSH e as portas TCP 80/443 (UDP 443 é opcional,
  para HTTP/3). **Não** libere a porta 5432.
- Instale Docker Engine e Docker Compose v2 no servidor.
- Garanta que o SMTP esteja autorizado para uso institucional. Não envie
  credenciais por chat nem as salve no repositório.
- Se arquivos `.env` já foram enviados ao Git remoto, troque todas as senhas e
  tokens que possam ter estado neles. Removê-los do índice não apaga versões
  antigas; a limpeza do histórico exige coordenação com quem compartilha o
  repositório.

## Configuração inicial no servidor

Clone o repositório e entre na pasta `PortalSemed`. Copie o exemplo de ambiente
e preencha-o no servidor:

```sh
cp .env.production.example .env.production
chmod 600 .env.production
```

Edite `.env.production`: defina o domínio real em `APP_DOMAIN`, a URL HTTPS em
`APP_URL`, e os parâmetros de SMTP/remetente aprovados. Os caminhos dos arquivos
de segredo devem permanecer locais ao servidor e fora do Git.

Crie os arquivos de senha. Gere senhas fortes e distintas para os usuários
administrativo e da aplicação; a senha SMTP deve vir do provedor de e-mail:

```sh
mkdir -p .secrets
chmod 700 .secrets
umask 077
openssl rand -hex 32 > .secrets/db_password
openssl rand -hex 32 > .secrets/postgres_password
```

Salve a senha SMTP, sem aspas nem espaços extras, em `.secrets/smtp_password`.
Confirme que `DB_PASSWORD_FILE`, `DB_ADMIN_PASSWORD_FILE` e
`SMTP_PASSWORD_FILE` em `.env.production` apontam para esses arquivos. A
aplicação usa uma conta PostgreSQL sem privilégios de superusuário; a conta
administrativa é usada apenas para inicialização e manutenção. Não imprima o
conteúdo das senhas em logs ou terminais compartilhados.

Valide a configuração e inicie os serviços:

```sh
docker compose --env-file .env.production -f docker-compose.production.yml config --quiet
docker compose --env-file .env.production -f docker-compose.production.yml up -d --build
docker compose --env-file .env.production -f docker-compose.production.yml ps
```

O Caddy solicita e renova automaticamente o certificado TLS quando o domínio
está acessível e as portas necessárias estão liberadas. A aplicação só deve ser
considerada pronta após confirmar HTTPS, login, envio de e-mail e upload de
imagem.

## Banco de dados e primeiro administrador

Em uma instalação nova, o PostgreSQL executa `database/schema.sql` ao criar o
volume vazio e o serviço `db-init` configura a conta de aplicação com os
privilégios necessários. Esses passos de inicialização não são executados
novamente em um volume já existente.
Para atualizar uma instalação existente, faça e verifique um backup, depois
aplique cada migration pendente de `database/migrations/` uma única vez,
seguindo a ordem dos nomes; não reaplique migrations já executadas.

Crie um arquivo temporário `.secrets/first-admin.env` com `FIRST_ADMIN_NAME`,
`FIRST_ADMIN_CPF`, `FIRST_ADMIN_EMAIL` e `FIRST_ADMIN_PASSWORD` (mínimo de 12
caracteres), sem compartilhar nem versionar o arquivo. Restrinja as permissões e
execute:

```sh
chmod 600 .secrets/first-admin.env
docker compose --env-file .env.production -f docker-compose.production.yml run --rm --no-deps --env-from-file .secrets/first-admin.env app php app/scripts/create-first-admin.php
rm .secrets/first-admin.env
```

Confirme que o administrador consegue entrar e, em seguida, remova o arquivo
temporário. A criação só funciona antes de existir um administrador.

## Backups e operação

Faça backups automatizados e criptografados do PostgreSQL e dos uploads, guarde
uma cópia fora do servidor e teste periodicamente uma restauração em ambiente
isolado. Um exemplo manual de dump compactado:

```sh
mkdir -p backups
docker compose --env-file .env.production -f docker-compose.production.yml exec -T db sh -c 'pg_dump -U "$POSTGRES_USER" "$POSTGRES_DB"' | gzip > "backups/portalsemed-$(date +%Y%m%d-%H%M%S).sql.gz"
```

Proteja também o volume `portal_uploads` e os volumes `caddy_data`/`caddy_config`.
O `docker compose down` preserva os volumes; **não** use `down -v`, pois isso
apaga os dados persistentes. Atualize imagens em janela de manutenção, faça
backup antes e verifique os logs e os fluxos principais após cada atualização:

```sh
docker compose --env-file .env.production -f docker-compose.production.yml logs --tail=100
```

Esta configuração prepara os containers, mas não cria servidor, DNS, regras de
firewall, política de backup institucional, monitoramento ou aprovação legal.
Esses itens devem ser providenciados e validados pela equipe responsável antes
da publicação oficial.
