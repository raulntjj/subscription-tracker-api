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

# Configurar permissões
RUN chown -R appuser:appuser /var/www/html && \
    chmod -R 755 /var/www/html/storage && \
    chmod -R 755 /var/www/html/bootstrap/cache

# Expor porta para FrankenPHP
EXPOSE 8000

# Mudar para usuário não-root
USER appuser

# Comando para iniciar FrankenPHP diretamente (sem octane:start para evitar problemas de permissão)
CMD ["frankenphp", "php-server", "--listen", "0.0.0.0:8000", "--root", "/var/www/html/public"]
