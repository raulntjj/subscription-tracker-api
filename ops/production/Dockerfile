# STAGE 1: Base - Imagem base com FrankenPHP
FROM dunglas/frankenphp:latest-php8.2 AS base

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

# STAGE 2: Dependencies - Instalar dependências
FROM base AS dependencies

WORKDIR /var/www/html

# Copiar apenas arquivos de dependências
COPY composer.json composer.lock ./

# Instalar dependências de produção
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

# STAGE 3: Builder - Build da aplicação
FROM base AS builder

WORKDIR /var/www/html

# Copiar dependências instaladas
COPY --from=dependencies /var/www/html/vendor ./vendor

# Copiar código fonte
COPY . .

# Gerar autoload otimizado
RUN composer dump-autoload --optimize --classmap-authoritative

# Limpar arquivos desnecessários
RUN rm -rf \
    tests \
    .env.example \
    .editorconfig \
    .gitattributes \
    .gitignore \
    phpunit.xml \
    README.md

# STAGE 4: Production - Imagem final otimizada
FROM base AS production

ENV APP_ENV=production
ENV APP_DEBUG=false

# Criar usuário appuser
RUN groupadd -g 1000 appuser && \
    useradd -u 1000 -g appuser -m -s /bin/bash appuser

WORKDIR /var/www/html

# Copiar aplicação buildada
COPY --from=builder --chown=appuser:appuser /var/www/html ./

# Copiar configurações do supervisor
COPY --chown=root:root ops/production/supervisor/*.conf /etc/supervisor/conf.d/

# Copiar script de entrypoint
COPY --chown=appuser:appuser ops/production/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Criar estrutura de diretórios necessária
RUN mkdir -p storage/logs/workers \
    storage/framework/{cache,sessions,views} \
    bootstrap/cache

# Configurar permissões
RUN chown -R appuser:appuser /var/www/html && \
    chmod -R 775 storage bootstrap/cache && \
    chmod 2775 storage/logs

# Expor porta
EXPOSE 8000

# Script de inicialização
CMD ["/usr/local/bin/entrypoint.sh"]
