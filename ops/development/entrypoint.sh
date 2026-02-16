#!/bin/bash
set -e

# Configurar umask para que novos arquivos tenham permissões 664 (rw-rw-r--)
umask 0002

# Iniciar supervisor em background
/usr/bin/supervisord -c /etc/supervisor/supervisord.conf &

# Iniciar FrankenPHP como appuser
exec gosu appuser frankenphp php-server --listen 0.0.0.0:8000 --root /var/www/html/public