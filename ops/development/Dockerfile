# Imagem base com FrankenPHP e PHP 8.2
FROM dunglas/frankenphp:latest-php8.2

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

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Criar usuário appuser
RUN groupadd -g 1000 appuser && \
    useradd -u 1000 -g appuser -m -s /bin/bash appuser

WORKDIR /var/www/html

COPY --chown=appuser:appuser . .

COPY --chown=root:root ops/development/supervisor/*.conf /etc/supervisor/conf.d/

COPY --chown=appuser:appuser ops/development/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

RUN chown -R appuser:appuser /var/www/html && \
    chmod -R 775 /var/www/html/storage && \
    chmod -R 775 /var/www/html/bootstrap/cache && \
    chmod 2775 /var/www/html/storage/logs

EXPOSE 8000

CMD ["/usr/local/bin/entrypoint.sh"]
