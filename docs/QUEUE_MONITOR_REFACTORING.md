# Refatoração do Queue Monitor - Arquitetura em Camadas

## Resumo das Mudanças

A funcionalidade de monitoramento de filas foi refatorada para seguir o padrão de arquitetura em camadas (Clean Architecture) do sistema, separando as responsabilidades entre Domain, Application, Infrastructure e Interface.

---

## Estrutura de Camadas

### 1. **Domain Layer** - Contratos
**Localização:** `modules/Shared/Domain/Contracts/`

#### `QueueMonitorRepositoryInterface.php`
- Define o contrato para operações de monitoramento de filas
- Métodos:
  - `getActiveJobs()`: Retorna jobs ativos
  - `getCompletedJobs(int $limit)`: Retorna jobs concluídos (limitado)
  - `getFailedJobs(int $limit)`: Retorna jobs falhados (limitado)
  - `getAllJobs()`: Retorna todos os jobs
  - `getJobDetails(string $jobId)`: Detalhes de um job específico
  - `getStatistics()`: Estatísticas gerais
  - `clearCompletedAndFailed()`: Limpa logs

---

### 2. **Application Layer** - Use Cases e Queries
**Localização:** `modules/Shared/Application/`

#### Queries (Leitura)
- **`GetAllJobsQuery.php`**: Lista todos os jobs
- **`GetQueueMetricsQuery.php`**: Retorna métricas e estatísticas
- **`GetActiveJobsQuery.php`**: Lista jobs ativos
- **`GetFailedJobsQuery.php`**: Lista jobs falhados
- **`GetJobDetailsQuery.php`**: Detalhes de um job específico

#### Use Cases (Escrita/Ação)
- **`ClearQueueMonitorLogsUseCase.php`**: Limpa logs de jobs concluídos e falhados

---

### 3. **Infrastructure Layer** - Implementação
**Localização:** `modules/Shared/Infrastructure/Queue/`

#### `RedisQueueMonitorRepository.php`
- Implementa `QueueMonitorRepositoryInterface`
- Responsável por toda interação com Redis
- Métodos privados:
  - `getJobsDetails()`: Busca múltiplos jobs
  - `formatJobDetails()`: Formata dados para retorno

**Configurações:**
- Conexão: `sessions` (Redis)
- Prefixo: `queue_monitor:`
- Sets:
  - `queue_monitor:active`
  - `queue_monitor:completed`
  - `queue_monitor:failed`

---

### 4. **Interface Layer** - Controllers e Routes
**Localização:** `modules/Shared/Interface/`

#### `Http/Controllers/QueueMonitorController.php`
Apenas orquestração - delega responsabilidades para Queries e UseCases

**Métodos:**
- `index()`: GET `/` - Lista todos os jobs
- `metrics()`: GET `/metrics` - Estatísticas gerais
- `active()`: GET `/active` - Jobs ativos
- `failed()`: GET `/failed` - Jobs falhados
- `show(string $jobId)`: GET `/{jobId}` - Detalhes de um job
- `clear()`: DELETE `/clear` - Limpa logs

#### `Routes/queue.php`
```php
Route::prefix('queue-monitor')->group(function () {
    Route::get('/', [QueueMonitorController::class, 'index']);
    Route::get('/metrics', [QueueMonitorController::class, 'metrics']);
    Route::get('/active', [QueueMonitorController::class, 'active']);
    Route::get('/failed', [QueueMonitorController::class, 'failed']);
    Route::delete('/clear', [QueueMonitorController::class, 'clear']);
    Route::get('/{jobId}', [QueueMonitorController::class, 'show']);
});
```

---

## Mudanças nas Rotas

### ❌ Antes
```
GET  /queue-monitor          → Métricas e estatísticas
GET  /queue-monitor/active   → Jobs ativos
GET  /queue-monitor/failed   → Jobs falhados
GET  /queue-monitor/{jobId}  → Detalhes de um job
DELETE /queue-monitor/clear  → Limpa logs
```

### ✅ Depois
```
GET  /queue-monitor          → Lista TODOS os jobs
GET  /queue-monitor/metrics  → Métricas e estatísticas (movido)
GET  /queue-monitor/active   → Jobs ativos
GET  /queue-monitor/failed   → Jobs falhados
GET  /queue-monitor/{jobId}  → Detalhes de um job
DELETE /queue-monitor/clear  → Limpa logs
```

---

## Mudanças no Comando Artisan

### `QueueMonitorCleanCommand.php`

**Antes:**
- Acessava Redis diretamente
- Opções: `--failed-only`, `--completed-only`, `--force`

**Depois:**
- Usa `ClearQueueMonitorLogsUseCase`
- Usa `QueueMonitorRepositoryInterface` para estatísticas
- Opção: `--force`
- Sempre limpa jobs concluídos E falhados (simplificado)

```bash
php artisan queue-monitor:clean --force
```

---

## Injeção de Dependência

### ServiceProvider
**Arquivo:** `modules/Shared/Infrastructure/Providers/SharedServiceProvider.php`

```php
use Modules\Shared\Domain\Contracts\QueueMonitorRepositoryInterface;
use Modules\Shared\Infrastructure\Queue\RedisQueueMonitorRepository;

public function register(): void
{
    // ...
    
    // Registra o QueueMonitorRepository como singleton
    $this->app->singleton(
        QueueMonitorRepositoryInterface::class, 
        RedisQueueMonitorRepository::class
    );
}
```

---

## Benefícios da Refatoração

### ✅ Separação de Responsabilidades
- **Controller**: Apenas orquestração e validação
- **Application**: Lógica de negócio
- **Infrastructure**: Acesso a dados (Redis)
- **Domain**: Contratos e regras

### ✅ Testabilidade
- Queries e UseCases podem ser testados isoladamente
- Repository pode ser mockado facilmente
- Controller depende apenas de abstrações

### ✅ Manutenibilidade
- Lógica de acesso ao Redis centralizada
- Mudanças no Redis afetam apenas o Repository
- Fácil adicionar novos endpoints

### ✅ Reutilização
- Queries podem ser usadas em outros contextos
- Repository pode ter múltiplas implementações
- UseCases podem ser chamados de Jobs, Commands, etc.

### ✅ Consistência
- Usa `ApiResponse` padrão do sistema
- Usa `Loggable` trait para logs
- Segue o padrão de outros módulos (User, Subscription)

---

## Exemplos de Uso

### GET /queue-monitor
```json
{
  "success": true,
  "data": {
    "total": 15,
    "jobs": [
      {
        "job_id": "abc123",
        "job_class": "App\\Jobs\\ProcessSubscription",
        "queue": "default",
        "status": "completed",
        "attempts": 1,
        "started_at": "2024-01-01 10:00:00",
        "finished_at": "2024-01-01 10:00:05",
        "duration_seconds": 5
      }
    ]
  },
  "message": "Jobs recuperados com sucesso."
}
```

### GET /queue-monitor/metrics
```json
{
  "success": true,
  "data": {
    "statistics": {
      "active_count": 2,
      "completed_count": 10,
      "failed_count": 3,
      "total_monitored": 15
    },
    "active_jobs": [...],
    "recent_completed": [...],
    "recent_failed": [...]
  },
  "message": "Métricas recuperadas com sucesso."
}
```

### GET /queue-monitor/{jobId}
```json
{
  "success": true,
  "data": {
    "job_id": "abc123",
    "job_class": "App\\Jobs\\ProcessSubscription",
    "queue": "default",
    "status": "failed",
    "attempts": 3,
    "started_at": "2024-01-01 10:00:00",
    "failed_at": "2024-01-01 10:00:10",
    "error": {
      "message": "Database connection failed",
      "trace": "..."
    }
  },
  "message": "Detalhes do job recuperados com sucesso."
}
```

### DELETE /queue-monitor/clear
```json
{
  "success": true,
  "data": {
    "deleted_count": 13
  },
  "message": "Limpeza concluída. 13 jobs removidos."
}
```

---

## Arquivos Criados

```
modules/Shared/
├── Application/
│   ├── Queries/
│   │   ├── GetAllJobsQuery.php
│   │   ├── GetQueueMetricsQuery.php
│   │   ├── GetActiveJobsQuery.php
│   │   ├── GetFailedJobsQuery.php
│   │   └── GetJobDetailsQuery.php
│   └── UseCases/
│       └── ClearQueueMonitorLogsUseCase.php
├── Domain/
│   └── Contracts/
│       └── QueueMonitorRepositoryInterface.php
└── Infrastructure/
    └── Queue/
        └── RedisQueueMonitorRepository.php
```

## Arquivos Modificados

```
modules/Shared/
├── Console/Commands/
│   └── QueueMonitorCleanCommand.php (refatorado)
├── Infrastructure/Providers/
│   └── SharedServiceProvider.php (adicionado binding)
└── Interface/
    ├── Http/Controllers/
    │   └── QueueMonitorController.php (refatorado)
    └── Routes/
        └── queue.php (rota /metrics adicionada)
```

---

## Compatibilidade

### ✅ Mantém Compatibilidade
- `/queue-monitor/active`
- `/queue-monitor/failed`
- `/queue-monitor/{jobId}`
- `/queue-monitor/clear`

### ⚠️ Breaking Changes
- **`GET /queue-monitor`**: Agora retorna TODOS os jobs (antes eram métricas)
- **`GET /queue-monitor/metrics`**: Nova rota para métricas (substituindo a antiga `/`)

### 🔄 Migração de Clientes
Se algum cliente usava `GET /queue-monitor` para métricas:
```diff
- GET /api/queue-monitor
+ GET /api/queue-monitor/metrics
```

---

## Próximos Passos (Opcional)

1. **Adicionar paginação** em `getAllJobs()`
2. **Adicionar filtros** (por status, data, queue)
3. **Adicionar cache** para métricas
4. **Criar testes unitários** para Queries e UseCases
5. **Adicionar eventos** (JobCleanedEvent)
