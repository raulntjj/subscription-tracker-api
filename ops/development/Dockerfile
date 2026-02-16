# Imagem base com FrankenPHP e PHP 8.2
FROM dunglas/frankenphp:latest-php8.2

# Argumentos para UID/GID do usuário
ARG USER_ID=1000
ARG GROUP_ID=1000

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    supervisor \
    gosu \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensões PHP necessárias
RUN install-php-extensions \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    opcache \
    redis \
    intl \
    sockets

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Criar usuário não-root
RUN groupadd -g ${GROUP_ID} appuser && \
    useradd -u ${USER_ID} -g appuser -m -s /bin/bash appuser

# Configurar diretório de trabalho
WORKDIR /var/www/html

# Copiar arquivos da aplicação
COPY --chown=appuser:appuser . .

# Copiar configurações do supervisor
COPY --chown=root:root ops/development/supervisor/*.conf /etc/supervisor/conf.d/

# Criar script de entrypoint
COPY --chown=appuser:appuser ops/development/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Configurar permissões com sticky bit para herdar grupo
RUN chown -R appuser:appuser /var/www/html && \
    chmod -R 775 /var/www/html/storage && \
    chmod -R 775 /var/www/html/bootstrap/cache && \
    chmod 2775 /var/www/html/storage/logs

# Expor porta para FrankenPHP
EXPOSE 8000

# Comando para iniciar tudo via entrypoint
CMD ["/usr/local/bin/entrypoint.sh"]
