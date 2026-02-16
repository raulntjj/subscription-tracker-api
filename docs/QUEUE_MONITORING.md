# Observabilidade de Filas com Redis

Sistema customizado de monitoramento de filas RabbitMQ usando Redis para armazenamento efêmero.

## Arquitetura

### Componentes

1. **QueueServiceProvider** (`modules/Shared/Infrastructure/Providers/QueueServiceProvider.php`)
   - Registra listeners para eventos de fila do Laravel
   - Monitora jobs em processamento, concluídos e falhados
   - Armazena dados no Redis (conexão 'sessions')

2. **QueueMonitorController** (`modules/Shared/Interface/Http/Controllers/QueueMonitorController.php`)
   - API REST para consultar status das filas
   - Endpoints para visualizar jobs ativos, concluídos e falhados

3. **QueueMonitorCleanCommand** (`modules/Shared/Console/Commands/QueueMonitorCleanCommand.php`)
   - Comando Artisan para limpar logs antigos
   - Opções para limpeza seletiva

## Estrutura de Dados no Redis

### Conexão
- **Redis Connection**: `sessions` (porta 6380)
- **Namespace**: `queue_monitor:`

### Estruturas

#### 1. Hash de Job Individual
```
Key: queue_monitor:{job_id}
Type: Hash
Fields:
  - job: Nome completo da classe do job
  - queue: Nome da fila (default, billing, webhooks)
  - status: processing | completed | failed
  - attempts: Número de tentativas
  - started_at: Timestamp de início
  - finished_at: Timestamp de término (se concluído)
  - failed_at: Timestamp de falha (se falhado)
  - payload: JSON do payload do job
  - error_message: Mensagem de erro (se falhado)
  - error_trace: Stack trace completo (se falhado)
TTL:
  - Jobs concluídos: 3600s (1 hora)
  - Jobs falhados: 86400s (24 horas)
```

#### 2. Sets de Categorização
```
Key: queue_monitor:active
Type: Set
Content: IDs dos jobs atualmente em processamento

Key: queue_monitor:completed
Type: Set
Content: IDs dos jobs concluídos (limpo ao expirar)

Key: queue_monitor:failed
Type: Set
Content: IDs dos jobs falhados (limpo manualmente ou ao expirar)
```

## API Endpoints

### 1. Status Geral
```http
GET /queue-monitor
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "statistics": {
      "active_count": 2,
      "completed_count": 15,
      "failed_count": 1,
      "total_monitored": 18
    },
    "active_jobs": [...],
    "recent_completed": [...],
    "recent_failed": [...]
  },
  "timestamp": "2026-02-16 19:30:00"
}
```

### 2. Jobs Ativos
```http
GET /queue-monitor/active
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "count": 2,
    "jobs": [
      {
        "job_id": "abc123",
        "job_class": "Modules\\Subscription\\Application\\Jobs\\CheckBillingJob",
        "queue": "billing",
        "status": "processing",
        "attempts": 1,
        "started_at": "2026-02-16 19:29:45",
        "finished_at": null
      }
    ]
  },
  "timestamp": "2026-02-16 19:30:00"
}
```

### 3. Jobs Falhados
```http
GET /queue-monitor/failed
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "count": 1,
    "jobs": [
      {
        "job_id": "def456",
        "job_class": "Modules\\Subscription\\Application\\Jobs\\DispatchWebhookJob",
        "queue": "webhooks",
        "status": "failed",
        "attempts": 3,
        "started_at": "2026-02-16 19:25:00",
        "failed_at": "2026-02-16 19:25:30",
        "error": {
          "message": "Connection timeout",
          "trace": "..."
        },
        "duration_seconds": 30
      }
    ]
  },
  "timestamp": "2026-02-16 19:30:00"
}
```

### 4. Job Específico
```http
GET /queue-monitor/{jobId}
```

**Resposta:**
```json
{
  "success": true,
  "data": {
    "job_id": "abc123",
    "job_class": "Modules\\Subscription\\Application\\Jobs\\CheckBillingJob",
    "queue": "billing",
    "status": "completed",
    "attempts": 1,
    "started_at": "2026-02-16 19:29:45",
    "finished_at": "2026-02-16 19:29:50",
    "duration_seconds": 5
  },
  "timestamp": "2026-02-16 19:30:00"
}
```

### 5. Limpar Logs
```http
DELETE /queue-monitor/clear
```

**Resposta:**
```json
{
  "success": true,
  "message": "Limpeza concluída. 15 jobs removidos.",
  "timestamp": "2026-02-16 19:30:00"
}
```

## Comandos Artisan

### Limpar Logs
```bash
# Limpeza interativa (pede confirmação)
php artisan queue-monitor:clean

# Limpeza forçada (sem confirmação)
php artisan queue-monitor:clean --force

# Limpar apenas jobs falhados
php artisan queue-monitor:clean --failed-only

# Limpar apenas jobs concluídos
php artisan queue-monitor:clean --completed-only
```

## Integração com Horizon

O sistema funciona perfeitamente com Laravel Horizon, pois:
1. Os eventos de fila são disparados independentemente do driver
2. Horizon gerencia os workers, este sistema monitora os jobs
3. Complementam-se: Horizon mostra workers, este sistema mostra jobs individuais

## Monitoramento em Tempo Real

### Redis Commander (Porta 8081)
Acesse `http://localhost:8081` para visualizar os dados diretamente no Redis:
- Navegue até a conexão `redis-sessions`
- Visualize as chaves `queue_monitor:*`
- Inspecione hashes individuais e sets de categorização

### Grafana (Porta 3001)
Crie dashboards customizados consultando:
- Contadores de jobs ativos/concluídos/falhados
- Duração média de processamento
- Taxa de falhas por fila
- Tendências ao longo do tempo

## Vantagens desta Solução

1. **Performance**: Redis é extremamente rápido para leitura/escrita
2. **Escalabilidade**: Dados efêmeros não sobrecarregam o MySQL
3. **Simplicidade**: Sem dependências externas além do Redis
4. **Flexibilidade**: Fácil customização e extensão
5. **Compatibilidade**: Funciona com qualquer driver de fila do Laravel
6. **TTL Automático**: Logs expiram automaticamente, mantendo Redis limpo
7. **Modularidade**: Isolado no módulo Shared, não polui código de domínio

## Manutenção

### Ajustar TTLs
Edite `QueueServiceProvider.php`:
```php
// Jobs concluídos (atualmente 1 hora)
$connection->expire("queue_monitor:$id", 3600);

// Jobs falhados (atualmente 24 horas)
$connection->expire("queue_monitor:$id", 86400);
```

### Adicionar Novos Campos
Edite os listeners em `QueueServiceProvider.php` para incluir mais informações no hash do job.

### Cronjob para Limpeza Automática
Adicione ao `routes/console.php`:
```php
Schedule::command('queue-monitor:clean --completed-only --force')
    ->daily()
    ->at('03:00');
```

## Troubleshooting

### Jobs não aparecem no monitoramento
1. Verifique se o `QueueServiceProvider` está registrado em `bootstrap/providers.php`
2. Limpe cache: `php artisan config:clear`
3. Verifique conexão com Redis: `php artisan tinker` → `Redis::connection('sessions')->ping()`

### Redis está cheio
Execute limpeza manual: `php artisan queue-monitor:clean --force`

### Dados inconsistentes
Os sets podem ficar dessincronizados se o Redis for reiniciado. Execute:
```bash
php artisan queue-monitor:clean --force
```

Isso limpará todos os dados e o sistema recomeçará a monitorar corretamente.
