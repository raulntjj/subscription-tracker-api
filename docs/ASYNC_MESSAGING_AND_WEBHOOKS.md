# Processamento Assíncrono, Mensageria e Webhooks

Para evitar o bloqueio do Event Loop do servidor Swoole e garantir tempos de resposta de milissegundos nas requisições HTTP, todas as tarefas de processamento pesado ou sujeitas a latência de rede externa são delegadas para uma infraestrutura de mensageria assíncrona.

## Arquitetura Orientada a Eventos
A aplicação faz uso de Eventos de Domínio para desacoplar ações. Por exemplo, quando o `CheckBillingJob` processa uma cobrança bem-sucedida, ele não chama serviços de terceiros diretamente. Ele apenas emite o evento `SubscriptionRenewed`.
Listeners escutam este evento e enfileiram *Jobs* secundários.

## RabbitMQ como Message Broker
O Laravel está configurado para utilizar o RabbitMQ como driver de fila, substituindo o driver nativo de banco de dados ou Redis.
* **Roteamento:** Filas dedicadas são criadas dinamicamente para priorizar cargas de trabalho diferentes (ex: envio de e-mails vs processamento de webhooks).
* **Resiliência:** O uso de *acknowledgments* garante que uma mensagem só seja removida da fila após o sucesso do processamento do *Job*.

## Disparo de Webhooks (Módulo Subscription)
O ecossistema de Webhooks demonstra a força da arquitetura assíncrona.
1. Uma alteração de estado em uma assinatura engatilha o evento correspondente.
2. O sistema verifica a existência de `WebhookConfigs` ativos atrelados ao cliente.
3. O `DispatchWebhookJob` é enviado para o RabbitMQ com um DTO serializado contendo o payload e a URL de destino.
4. *Workers* isolados consomem o RabbitMQ, realizam a chamada HTTP (cURL/Guzzle) ao serviço de terceiros, tratando *timeouts* e retentativas configuráveis, sem onerar a API principal.

## Monitoramento e Orquestração
* **Supervisor:** Toda a execução de *workers* (`php artisan queue:work`) é gerida pelo daemon do Supervisor (`ops/development/supervisor/queues.conf`), que garante a manutenção contínua dos processos em segundo plano.
* **Telemetria via Redis:** O módulo Shared possui uma implementação `RedisQueueMonitorRepository` que coleta estatísticas detalhadas sobre o tráfego nas filas (Jobs pendentes, processados e falhos). Estes dados são extraídos pelo Prometheus e expostos nos dashboards do Grafana para análise preditiva de sobrecarga do sistema.