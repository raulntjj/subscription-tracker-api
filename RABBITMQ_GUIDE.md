# Guia de Integração RabbitMQ - Subscription Tracker

## 📦 Visão Geral

O módulo Subscription está totalmente integrado com RabbitMQ para processamento assíncrono de jobs, especialmente o `CheckBillingJob` que processa faturamentos diários.

## 🏗️ Arquitetura

```
┌─────────────┐         ┌──────────────┐         ┌─────────────┐
│   Laravel   │ ──────> │   RabbitMQ   │ ──────> │   Worker    │
│ Application │  envia  │   Exchange   │  consome│  (artisan)  │
└─────────────┘   job   └──────────────┘   job   └─────────────┘
                              │
                              │ routing
                              v
                        ┌──────────────┐
                        │ Queue: billing│
                        └──────────────┘
```

## 🚀 Instalação e Configuração

### 1. Pacote RabbitMQ

O pacote já está instalado:

```bash
docker exec boilerplate-backend composer show vladimir-yuldashev/laravel-queue-rabbitmq
```

### 2. Configuração do Docker

O RabbitMQ está configurado no `docker-compose.yml`:

```yaml
rabbitmq:
  image: rabbitmq:3.13-management-alpine
  ports:
    - "5672:5672"        # AMQP protocol
    - "15672:15672"      # Management UI
  environment:
    RABBITMQ_DEFAULT_USER: admin
    RABBITMQ_DEFAULT_PASS: secret
```

### 3. Variáveis de Ambiente

Adicione ao seu `.env`:

```bash
QUEUE_CONNECTION=rabbitmq

RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=admin
RABBITMQ_PASSWORD=secret
RABBITMQ_VHOST=/
RABBITMQ_QUEUE=default
RABBITMQ_EXCHANGE_NAME=laravel
RABBITMQ_EXCHANGE_TYPE=direct
```

## 📋 Comandos Docker

### Gerenciar RabbitMQ Container

```bash
# Iniciar RabbitMQ
cd infrastructure/development
docker compose up -d rabbitmq

# Ver logs
docker logs -f boilerplate-rabbitmq

# Parar RabbitMQ
docker compose stop rabbitmq

# Remover e recriar
docker compose down rabbitmq
docker compose up -d rabbitmq

# Status do RabbitMQ
docker exec boilerplate-rabbitmq rabbitmqctl status

# Listar filas
docker exec boilerplate-rabbitmq rabbitmqctl list_queues
```

### Acessar RabbitMQ Management UI

Acesse no navegador:

```
http://localhost:15672
```

**Credenciais:**
- Usuário: `admin`
- Senha: `secret`

## 🔄 Uso com CheckBillingJob

### 1. Despachar Job para RabbitMQ

```bash
# Via comando (assíncrono)
docker exec boilerplate-backend php artisan subscription:check-billing

# O job será enviado para a fila 'billing' no RabbitMQ
```

### 2. Processar Jobs (Worker)

```bash
# Worker básico
docker exec -d boilerplate-backend php artisan queue:work rabbitmq --queue=billing

# Worker com configurações avançadas
docker exec -d boilerplate-backend php artisan queue:work rabbitmq \
  --queue=billing \
  --tries=3 \
  --timeout=120 \
  --sleep=3 \
  --max-jobs=100 \
  --max-time=3600

# Múltiplos workers (escala horizontal)
for i in {1..3}; do
  docker exec -d boilerplate-backend php artisan queue:work rabbitmq \
    --queue=billing \
    --name=billing-worker-$i
done
```

### 3. Monitorar Workers

```bash
# Ver processos do worker
docker exec boilerplate-backend ps aux | grep "queue:work"

# Ver logs do Laravel
docker exec boilerplate-backend tail -f storage/logs/laravel.log

# Matar todos os workers
docker exec boilerplate-backend pkill -f "queue:work"
```

### 4. Limpar Filas

```bash
# Limpar fila específica
docker exec boilerplate-rabbitmq rabbitmqctl purge_queue billing

# Deletar fila
docker exec boilerplate-rabbitmq rabbitmqctl delete_queue billing
```

## 🎯 Cenários de Uso

### Desenvolvimento Local

```bash
# 1. Subir containers
cd infrastructure/development
docker compose up -d

# 2. Configurar ambiente
docker exec boilerplate-backend cp .env.example .env
# Edite .env e configure QUEUE_CONNECTION=rabbitmq

# 3. Iniciar worker
docker exec -d boilerplate-backend php artisan queue:work rabbitmq --queue=billing

# 4. Despachar job
docker exec boilerplate-backend php artisan subscription:check-billing

# 5. Monitorar no Management UI
# Acesse http://localhost:15672
```

### Produção

```bash
# 1. Usar Supervisor para manter workers vivos
# Criar arquivo: /etc/supervisor/conf.d/laravel-worker.conf

[program:laravel-billing-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work rabbitmq --queue=billing --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=3
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/worker.log
stopwaitsecs=3600

# 2. Recarregar supervisor
supervisorctl reread
supervisorctl update
supervisorctl start laravel-billing-worker:*
```

### Scheduled Jobs (Cron)

Adicione ao `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    // Executa CheckBillingJob todo dia às 00:00
    $schedule->command('subscription:check-billing')
        ->daily()
        ->at('00:00')
        ->timezone('America/Sao_Paulo')
        ->runInBackground();
    
    // Monitora falhas de jobs
    $schedule->command('queue:failed')
        ->hourly();
    
    // Limpa jobs antigos
    $schedule->command('queue:prune-failed --hours=48')
        ->daily();
}
```

Execute o scheduler:

```bash
# Desenvolvimento (foreground)
docker exec boilerplate-backend php artisan schedule:work

# Produção (adicione ao crontab)
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

## 🔍 Debugging e Troubleshooting

### Ver Jobs na Fila

```bash
# Via RabbitMQ CLI
docker exec boilerplate-rabbitmq rabbitmqctl list_queues name messages consumers

# Via Laravel
docker exec boilerplate-backend php artisan queue:monitor rabbitmq:billing
```

### Jobs Falhados

```bash
# Listar jobs falhados
docker exec boilerplate-backend php artisan queue:failed

# Reprocessar job específico
docker exec boilerplate-backend php artisan queue:retry {id}

# Reprocessar todos
docker exec boilerplate-backend php artisan queue:retry all

# Limpar jobs falhados
docker exec boilerplate-backend php artisan queue:flush
```

### Logs Detalhados

```bash
# Logs do RabbitMQ
docker logs -f boilerplate-rabbitmq

# Logs do Worker
docker exec boilerplate-backend php artisan queue:work rabbitmq --queue=billing --verbose

# Logs estruturados do Laravel
docker exec boilerplate-backend tail -f storage/logs/laravel.log | grep "CheckBillingJob"
```

### Problemas Comuns

#### Worker não consome jobs

```bash
# Verificar conexão
docker exec boilerplate-backend php artisan queue:work rabbitmq --once

# Verificar configuração
docker exec boilerplate-backend php artisan config:cache
docker exec boilerplate-backend php artisan queue:restart
```

#### Fila com muitos jobs

```bash
# Ver estatísticas
docker exec boilerplate-rabbitmq rabbitmqctl list_queues

# Aumentar workers
for i in {1..5}; do
  docker exec -d boilerplate-backend php artisan queue:work rabbitmq --queue=billing
done
```

#### Jobs expirando

Ajuste o timeout no `.env`:

```bash
RABBITMQ_QUEUE_RETRY_AFTER=180
```

## 📊 Monitoramento

### RabbitMQ Management UI

Acesse `http://localhost:15672` e monitore:

- **Queues**: Taxa de mensagens, consumidores ativos
- **Connections**: Conexões ativas dos workers
- **Exchanges**: Roteamento de mensagens
- **Channels**: Canais de comunicação

### Laravel Horizon (Opcional)

Para monitoramento avançado, instale o Laravel Horizon:

```bash
docker exec boilerplate-backend composer require laravel/horizon
docker exec boilerplate-backend php artisan horizon:install
docker exec boilerplate-backend php artisan migrate
```

Acesse: `http://localhost:8001/horizon`

## 🎛️ Configurações Avançadas

### Múltiplas Filas

Configure filas por prioridade:

```php
// config/queue.php
'rabbitmq' => [
    'driver' => 'rabbitmq',
    // ... configurações base
    'options' => [
        'queue' => [
            'job_type' => [
                'high' => ['billing'],
                'default' => ['default'],
                'low' => ['cleanup'],
            ],
        ],
    ],
],
```

```bash
# Worker priorizando filas
docker exec -d boilerplate-backend php artisan queue:work rabbitmq \
  --queue=billing,default,cleanup
```

### Dead Letter Queue

Configure fila para jobs mortos:

```php
'options' => [
    'queue' => [
        'arguments' => [
            'x-dead-letter-exchange' => 'laravel-dlx',
            'x-dead-letter-routing-key' => 'failed',
        ],
    ],
],
```

### Performance

```bash
# Aumentar prefetch count (jobs por vez)
RABBITMQ_PREFETCH_COUNT=10

# Persistent messages
RABBITMQ_EXCHANGE_DURABLE=true
RABBITMQ_QUEUE_DURABLE=true
```

## 📝 Exemplos de Código

### Despachar Job Programaticamente

```php
use Modules\Subscription\Application\Jobs\CheckBillingJob;

// Dispatch imediato
CheckBillingJob::dispatch();

// Dispatch com delay
CheckBillingJob::dispatch()->delay(now()->addMinutes(10));

// Dispatch para fila específica
CheckBillingJob::dispatch()->onQueue('billing');

// Dispatch com retry automático
CheckBillingJob::dispatch()
    ->onQueue('billing')
    ->tries(5)
    ->backoff([60, 120, 300]);
```

### Listener de Job

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    JobProcessing::class => [
        function (JobProcessing $event) {
            Log::info('Job started', [
                'job' => $event->job->resolveName(),
            ]);
        },
    ],
    JobProcessed::class => [
        function (JobProcessed $event) {
            Log::info('Job completed', [
                'job' => $event->job->resolveName(),
            ]);
        },
    ],
];
```

## 🔐 Segurança

### Autenticação

Configure usuário e senha fortes no `.env`:

```bash
RABBITMQ_USER=production_user
RABBITMQ_PASSWORD=strong_random_password_here
```

### Vhost Isolation

Use vhosts diferentes por ambiente:

```bash
RABBITMQ_VHOST=/production
RABBITMQ_VHOST=/staging
RABBITMQ_VHOST=/development
```

### TLS/SSL

Para produção, habilite SSL:

```bash
RABBITMQ_SSL=true
RABBITMQ_SSL_VERIFY=true
RABBITMQ_SSL_CAFILE=/path/to/ca.pem
RABBITMQ_SSL_CERTFILE=/path/to/cert.pem
RABBITMQ_SSL_KEYFILE=/path/to/key.pem
```

## 📚 Recursos Adicionais

- [RabbitMQ Documentation](https://www.rabbitmq.com/documentation.html)
- [Laravel Queues Documentation](https://laravel.com/docs/queues)
- [RabbitMQ Management Plugin](https://www.rabbitmq.com/management.html)
- [Package Documentation](https://github.com/vyuldashev/laravel-queue-rabbitmq)

## ✅ Checklist de Produção

- [ ] RabbitMQ em cluster para alta disponibilidade
- [ ] Supervisor configurado para manter workers vivos
- [ ] Monitoramento com alertas (Prometheus/Grafana)
- [ ] Backup de mensagens persistentes
- [ ] Dead letter queue configurada
- [ ] Rate limiting implementado
- [ ] SSL/TLS habilitado
- [ ] Logs centralizados (ELK Stack)
- [ ] Health checks configurados
- [ ] Disaster recovery plan documentado

---

**Status**: ✅ RabbitMQ Totalmente Integrado e Pronto para Produção
