#!/bin/bash
set -e

# Criar arquivos de log com permissões corretas
touch /var/www/html/storage/logs/worker.log
touch /var/www/html/storage/logs/scheduler.log
chown appuser:appuser /var/www/html/storage/logs/worker.log
chown appuser:appuser /var/www/html/storage/logs/scheduler.log
chmod 664 /var/www/html/storage/logs/*.log

# Iniciar supervisor em background
/usr/bin/supervisord -c /etc/supervisor/supervisord.conf &

# Iniciar FrankenPHP
exec frankenphp php-server --listen 0.0.0.0:8000 --root /var/www/html/public