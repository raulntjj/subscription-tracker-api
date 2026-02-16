# Configuração do RabbitMQ - Guia Rápido

## Problema Comum

Se você ver este erro nos logs do RabbitMQ:

```
operation basic.get caused a channel exception not_found: no queue 'default' in vhost '/'
```

Significa que o Laravel está tentando consumir uma fila que não existe no RabbitMQ.

## Solução: Comando `rabbitmq:setup`

Comando que configura automaticamente todas as filas necessárias:

```bash
docker exec subs-tracker-backend php artisan rabbitmq:setup
```

### O que este comando faz:

1. Cria o exchange `laravel` (tipo: direct, durable)
2. Cria a fila `default` (durable, não-exclusiva, não-auto-delete)
3. Cria a fila `billing` (durable, não-exclusiva, não-auto-delete)
4. Cria a fila `webhooks` (durable, não-exclusiva, não-auto-delete)
5. Faz bind de todas as filas ao exchange `laravel`

### Saída esperada:

```
Configurando RabbitMQ...

Criando exchanges...
   Exchange 'laravel' criado

Criando filas...
   Fila 'default' criada
   Fila 'billing' criada
   Fila 'webhooks' criada

Fazendo bind das filas aos exchanges...
   Fila 'default' vinculada ao exchange 'laravel'
   Fila 'billing' vinculada ao exchange 'laravel'
   Fila 'webhooks' vinculada ao exchange 'laravel'

RabbitMQ configurado com sucesso!
```

## Verificar Filas

```bash
# Listar filas
docker exec subs-tracker-rabbitmq rabbitmqctl list_queues name messages consumers

# Saída esperada:
# name      messages  consumers
# billing   0         0
# webhooks  0         0
# default   0         0
```

## Iniciar Workers

Após configurar as filas, inicie os workers:

```bash
# Worker para fila default
docker exec -d subs-tracker-backend php artisan queue:work rabbitmq --queue=default --tries=3

# Worker para fila billing
docker exec -d subs-tracker-backend php artisan queue:work rabbitmq --queue=billing --tries=3

# Worker para fila webhooks
docker exec -d subs-tracker-backend php artisan queue:work rabbitmq --queue=webhooks --tries=3
```

## Quando Executar

Execute `rabbitmq:setup` sempre que:

- Iniciar o projeto pela primeira vez
- Recriar os containers Docker
- Limpar o RabbitMQ
- Adicionar uma nova fila ao sistema

## Filas Disponíveis

| Fila       | Uso                      | Jobs                                   |
|------------|--------------------------|----------------------------------------|
| `default`  | Fila padrão do Laravel   | Jobs sem fila especificada             |
| `billing`  | Processamento de billing | `CheckBillingJob`                      |
| `webhooks` | Envio de webhooks        | `DispatchWebhookJob`, `TestWebhookJob` |

## Troubleshooting

### Erro: Connection refused

```bash
# Verificar se RabbitMQ está rodando
docker ps | grep rabbitmq

# Iniciar RabbitMQ
docker compose up -d rabbitmq
```

### Erro: Already declared

Se a fila já existe, o comando apenas avisa mas continua:

```
Fila 'default': already declared with different parameters
```

Para forçar recriação:

```bash
docker exec subs-tracker-backend php artisan rabbitmq:setup --force
```

### Limpar Todas as Filas

```bash
# Purgar filas (remove mensagens)
docker exec subs-tracker-rabbitmq rabbitmqctl purge_queue default
docker exec subs-tracker-rabbitmq rabbitmqctl purge_queue billing
docker exec subs-tracker-rabbitmq rabbitmqctl purge_queue webhooks

# Ou deletar e recriar
docker exec subs-tracker-rabbitmq rabbitmqctl delete_queue default
docker exec subs-tracker-rabbitmq rabbitmqctl delete_queue billing
docker exec subs-tracker-rabbitmq rabbitmqctl delete_queue webhooks

# Depois executar setup novamente
docker exec subs-tracker-backend php artisan rabbitmq:setup
```

## Management UI

Acesse o RabbitMQ Management UI para visualizar as filas:

```
http://localhost:15672
User: admin
Pass: secret
```

Navegue para: **Queues** → Você verá todas as 3 filas criadas.

---

**Comando criado**: `RabbitMQSetupCommand.php`  
**Localização**: `modules/Shared/Console/Commands/RabbitMQSetupCommand.php`  
**Registrado em**: `SharedServiceProvider.php`
