# Guia de Publicação - Portal SEMED

## Pré-requisitos

- PHP 8.0+
- PostgreSQL 12+
- Servidor web (Apache ou Nginx)
- Composer instalado
- SSL/TLS ativo (HTTPS)

## Passos para Publicação

### 1. Preparação do Servidor

```bash
# Clonar repositório
git clone https://seu-repo.git /var/www/portalsemed

# Entrar na pasta do projeto
cd /var/www/portalsemed/PortalSemed

# Instalar dependências
composer install --no-dev --optimize-autoloader

# Dar permissões adequadas
chmod 755 public
chmod 644 public/index.php
chmod 755 app
```

### 2. Configuração do Banco de Dados

```bash
# Conectar ao PostgreSQL
psql -U postgres -h localhost

# Criar banco de dados
CREATE DATABASE portalSemed ENCODING 'UTF8';

# Criar usuário (seguro)
CREATE USER semed_user WITH PASSWORD 'sua_senha_forte_aleatoria_aqui';

# Dar permissões
GRANT ALL PRIVILEGES ON DATABASE portalSemed TO semed_user;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO semed_user;

# Executar schema
\c portalSemed
\i /path/to/database/schema.sql
```

### 3. Configuração de Variáveis de Ambiente

```bash
# Copiar e editar .env
cp .env.production .env

# Editar arquivo com valores reais
nano .env
```

**Valores importantes a mudar em `.env`:**

- `DB_HOST`: IP ou hostname do PostgreSQL
- `DB_USER`: usuário PostgreSQL criado
- `DB_PASS`: senha do usuário PostgreSQL
- `JWT_SECRET`: chave criptográfica (mínimo 32 caracteres aleatórios)

### 4. Configuração do Apache

#### Habilitando rewrite_module

```bash
# Ubuntu/Debian
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod deflate

# Reiniciar Apache
sudo systemctl restart apache2
```

#### VirtualHost

```apache
<VirtualHost *:443>
    ServerName seu-dominio.com
    ServerAlias www.seu-dominio.com
    DocumentRoot /var/www/portalsemed/PortalSemed/public

    <Directory /var/www/portalsemed/PortalSemed/public>
        AllowOverride All
        Require all granted
        
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^ index.php [QSA,L]
        </IfModule>
    </Directory>

    # Logs
    ErrorLog ${APACHE_LOG_DIR}/portalsemed-error.log
    CustomLog ${APACHE_LOG_DIR}/portalsemed-access.log combined

    # SSL (obtido via Let's Encrypt)
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/seu-dominio.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/seu-dominio.com/privkey.pem
</VirtualHost>

# Redirecionar HTTP para HTTPS
<VirtualHost *:80>
    ServerName seu-dominio.com
    ServerAlias www.seu-dominio.com
    Redirect permanent / https://seu-dominio.com/
</VirtualHost>
```

### 5. Configuração do Nginx (Alternativa)

```nginx
server {
    listen 443 ssl http2;
    server_name seu-dominio.com www.seu-dominio.com;

    root /var/www/portalsemed/PortalSemed/public;
    index index.php;

    # SSL
    ssl_certificate /etc/letsencrypt/live/seu-dominio.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/seu-dominio.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Headers de segurança
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Gzip
    gzip on;
    gzip_types text/plain text/css text/javascript application/json;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.5-fpm.sock;
    }

    # Bloquear acesso a arquivos sensíveis
    location ~ /\. {
        deny all;
    }

    location ~ /(\.env|composer\.) {
        deny all;
    }

    # Cache estático
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}

# HTTP to HTTPS redirect
server {
    listen 80;
    server_name seu-dominio.com www.seu-dominio.com;
    return 301 https://$server_name$request_uri;
}
```

### 6. Segurança

```bash
# Remover arquivos desnecessários
rm -rf tests/ *.md

# Definir permissões corretas
chmod 750 app/
chmod 755 public/
chmod 644 public/.htaccess
chmod 640 .env

# Gerar JWT_SECRET seguro
openssl rand -base64 32

# Proteger .env
chown www-data:www-data .env
chmod 600 .env
```

### 7. Testar Conectividade

```bash
# Verificar PHP
php -v

# Verificar PostgreSQL
psql -U semed_user -d portalSemed -h localhost -c "SELECT 1;"

# Verificar Composer
composer check-platform-reqs
```

### 8. Iniciar Aplicação

Acessar `https://seu-dominio.com` no navegador.

## Monitoramento Contínuo

### Logs

- Apache: `/var/log/apache2/portalsemed-*.log`
- Nginx: `/var/log/nginx/*.log`
- PHP: `/var/log/php-fpm.log`
- Aplicação: verificar `error_log` do PHP

### Backup

```bash
# Backup do banco
pg_dump -U semed_user portalSemed > backup_$(date +%Y%m%d).sql

# Backup de arquivos
tar czf portalsemed_backup_$(date +%Y%m%d).tar.gz /var/www/portalsemed/
```

## Solução de Problemas

### "Erro ao conectar ao banco de dados"
- Verificar credenciais em `.env`
- Confirmar se PostgreSQL está rodando
- Testar conexão: `psql -U user -h host -d database`

### "404 ao acessar páginas"
- Verificar se `mod_rewrite` está habilitado (Apache)
- Verificar se `.htaccess` existe e tem permissões corretas
- Verificar `AllowOverride All` na configuração do VirtualHost

### "JWT_SECRET não definido"
- Verificar arquivo `.env`
- Regenerar JWT com `openssl rand -base64 32`

### Performance baixa
- Habilitar gzip compression
- Configurar cache HTTP
- Ativar OPcache do PHP
- Usar CDN para recursos estáticos
