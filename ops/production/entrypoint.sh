#!/bin/bash
set -e

umask 0002

echo "Checking environment configuration..."

if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "  APP_KEY not set, generating automatically..."
    APP_KEY="base64:$(openssl rand -base64 32)"
    export APP_KEY
    echo "APP_KEY generated"
fi

if [ -z "$JWT_SECRET" ] || [ "$JWT_SECRET" = "" ]; then
    echo "  JWT_SECRET not set, generating automatically..."
    JWT_SECRET=$(openssl rand -base64 64 | tr -d '\n')
    export JWT_SECRET
    echo "JWT_SECRET generated"
fi

echo "Environment configuration is valid"
echo ""

# Verifica se o autoload do composer existe para evitar reinstalar dependências desnecessariamente
if [ -f /var/www/html/vendor/autoload.php ]; then
    echo "Vendor autoload found, skipping composer install"
else
    echo "Vendor autoload not found, running composer install..."
    composer install --no-interaction --prefer-dist
fi

# Criar estrutura de diretórios necessária
# Adicionei o path dos workers aqui
mkdir -p /var/www/html/storage/logs/workers \
         /var/www/html/storage/framework/{cache,sessions,views} \
         /var/www/html/bootstrap/cache

# Aplicar permissões recursivas
chown -R appuser:appuser /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Garantir que arquivos de log existentes e novos tenham as permissões corretas
# Usei um loop para evitar repetição de código
LOG_FILES=("webhook.log" "billing.log" "default.log" "scheduler.log" "server.log")

for LOG in "${LOG_FILES[@]}"; do
    FILE="/var/www/html/storage/logs/workers/$LOG"
    touch "$FILE"
    chown appuser:appuser "$FILE"
    chmod 664 "$FILE"
done

# Cache de configuração (em produção)
gosu appuser php artisan config:cache
gosu appuser php artisan route:cache
gosu appuser php artisan view:cache

rm -f /var/run/supervisor.sock
exec /usr/bin/supervisord --nodaemon -c /etc/supervisor/supervisord.conf
