#!/bin/bash
set -e

umask 0002

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
