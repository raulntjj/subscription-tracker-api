# Subscription Tracker API

Uma API RESTful de alta performance desenvolvida em PHP e Laravel, operando sob o servidor de aplicação **Swoole**. O projeto foi concebido utilizando a arquitetura de **Monolito Modular** e é estritamente orientado pelas diretrizes do **Domain-Driven Design (DDD)**.

O foco central desta aplicação é garantir escalabilidade, resiliência e manutenibilidade através da aplicação rigorosa de padrões de engenharia de software, separação clara de responsabilidades e tipagem forte.

## Arquitetura e Padrões de Projeto

A estrutura foge do padrão MVC tradicional para adotar uma divisão modular, onde cada domínio de negócio atua de forma autônoma.

### 1. Monolito Modular e DDD

A aplicação está dividida em *Bounded Contexts* independentes (`User`, `Subscription`, `Shared`). Cada módulo possui quatro camadas fundamentais, garantindo que o domínio não dependa de frameworks ou tecnologias externas:

* **Domain:** Entidades de negócio, Value Objects (ex: `Email`, `Password`), Enums e Contratos (Interfaces). É o núcleo puro da aplicação.
* **Application:** Casos de uso e regras de orquestração.
* **Infrastructure:** Implementações técnicas, acesso a banco de dados, serviços externos e provedores.
* **Interface:** Controladores, rotas HTTP e formatação de respostas.

### 2. Command Query Separation (CQS)

A aplicação aplica o princípio CQS para segregar operações de mutação de estado e operações de leitura.

* **Commands (Use Cases):** Classes de responsabilidade única responsáveis por alterar o estado da aplicação (ex: `CreateSubscriptionUseCase`, `ActivateWebhookUseCase`).
* **Queries:** Classes dedicadas exclusivamente à leitura e formatação de dados (ex: `FindSubscriptionByIdQuery`). Embora a aplicação utilize um único banco de dados relacional (não caracterizando um CQRS completo), as Queries são otimizadas com estratégias de **Cache no Redis**, garantindo tempos de resposta na ordem de milissegundos para dados frequentemente acessados.

### 3. Repository Pattern e Inversão de Dependência (DIP)

O acesso a dados é abstraído através do Repository Pattern. A camada de Domínio define as interfaces (`UserRepositoryInterface`, `SubscriptionRepositoryInterface`), enquanto a camada de Infraestrutura fornece a implementação concreta utilizando o Eloquent ORM. Isso garante que a regra de negócio desconheça a tecnologia de persistência, facilitando testes e futuras substituições de tecnologia.

### 4. Tipagem Forte e Data Transfer Objects (DTOs)

O código faz uso extensivo dos recursos de tipagem estrita do PHP 8.x (`declare(strict_types=1)`). O tráfego de dados entre as camadas HTTP (Controllers) e a camada de Aplicação ocorre exclusivamente através de **DTOs**. Estes objetos são imutáveis e garantem que as estruturas de dados sejam previsíveis, seguras e validadas antes de atingirem a lógica de negócio.

### 5. Suporte Multilinguagem (i18n)

A API possui suporte nativo à internacionalização. O cliente consumidor da API pode alterar dinamicamente o idioma de retorno através do envio do header HTTP `Accept-Language` (suportando nativamente `en` e `pt-BR`). Esta funcionalidade garante que todas as respostas do sistema — incluindo mensagens de sucesso, validações de payload e lançamentos de exceções — sejam traduzidas e formatadas de acordo com a preferência de idioma da requisição.

## Alta Performance com Swoole

Diferente do ciclo de vida tradicional do PHP (PHP-FPM) que recarrega todo o framework a cada requisição HTTP, este projeto é servido via **Laravel Octane com Swoole**.

O Swoole mantém a aplicação (e o framework) em memória de forma residente (persistente). Isso elimina o *overhead* de inicialização (bootstrap), resultando em conexões simultâneas assíncronas, maior throughput e redução drástica no tempo de resposta (latência) da API.

## Processamento Assíncrono e Mensageria

Para garantir que a API principal não seja bloqueada por tarefas custosas, o sistema conta com uma infraestrutura robusta de mensageria:

* **RabbitMQ:** Atua como o *Message Broker* principal, roteando eventos de domínio e executando Webhooks em background.
* **Supervisor:** Garante a resiliência dos *workers* que consomem as filas, reiniciando-os automaticamente em caso de falhas de segmentação ou timeouts de processamento.
* **Redis:** Utilizado não apenas para cache de banco de dados e sessões, mas para manter métricas em tempo real sobre a saúde das filas através do padrão de monitoramento implementado no módulo Shared.

## Observabilidade e Telemetria

A infraestrutura foi desenhada para estar pronta para ambientes de produção, incluindo ferramentas para análise de saúde da aplicação:

* **Prometheus e Grafana:** A infraestrutura via Docker Compose inclui a stack de telemetria preconfigurada. O Prometheus coleta métricas da aplicação e do servidor, enquanto o Grafana fornece *dashboards* visuais para acompanhar o consumo de memória, taxa de requisições, e saúde do RabbitMQ.
* **Logs Estruturados:** Implementação de padrões de log via interface (`LoggerInterface`), padronizando o rastreamento de erros e execuções de rotinas em background.

### Postman Collection (Addon de Testes e Documentação)

Para facilitar a integração, os testes manuais e o entendimento do fluxo da aplicação, o repositório conta com uma coleção completa do Postman localizada em `docs/postman/subscription_tracker.postman_collection.json`.
Esta coleção já contempla todos os *endpoints* da API estruturados por módulos, contendo exemplos de payload (JSON), autorização pré-configurada via JWT e scripts de automação de variáveis de ambiente, servindo como uma documentação viva e executável da API.

## Ferramentas de Automação Interna (Stubs)

Para manter a consistência arquitetural do DDD sem perder a agilidade no desenvolvimento, o módulo `Shared` atua como um motor de geração de código. Através de comandos Artisan customizados (ex: `php artisan module:create`), o sistema utiliza *Stubs* (templates base) para gerar instantaneamente a estrutura de pastas e as classes necessárias (Entities, UseCases, Repositories, DTOs) em seus devidos locais e com a tipagem correta, poupando o desenvolvedor da criação manual de arquivos repetitivos (boilerplate).

---

## Documentação Técnica Detalhada

Para uma compreensão mais profunda das decisões arquiteturais e técnicas aplicadas neste projeto, consulte as micro-documentações localizadas no diretório `docs/`:

* [Arquitetura e Domain-Driven Design (DDD)](./docs/ARCHITECTURE_AND_DDD.md)
* [Command Query Separation (CQS) e Fluxo de Dados](./docs/CQS_AND_DATA_FLOW.md)
* [Processamento Assíncrono, Mensageria e Webhooks](./docs/ASYNC_MESSAGING_AND_WEBHOOKS.md)
* [Swoole e Alta Performance](./docs/SWOOLE_AND_PERFORMANCE.md)
* [Motor CLI e Geração de Código (Stubs)](./docs/CLI_ENGINE_AND_STUBS.md)

---

## Requisitos e Execução Local

Todo o ambiente é containerizado e padronizado via Docker.

1. Clone o repositório e configure as variáveis de ambiente:

```bash
git clone https://github.com/raulntjj/subscription-tracker-api.git
cd subscription-tracker-api
cp .env.example .env

```

2. Inicialize a infraestrutura e a aplicação via Docker Compose:

```bash
cd ops/development
docker-compose up -d --build

```

3. Instale as dependências e execute as migrações:

```bash
docker exec -it <subs-tracker-backend> composer install
docker exec -it <subs-tracker-backend> php artisan migrate

# Ou utilize o script personalizado na raíz do projeto
./backend-exec composer install
./backend-exec php artisan migrate

```

4. Para inicializar a stack de Observabilidade (Prometheus + Grafana):

```bash
docker-compose -f docker-compose.observability.yml up -d

```

### Qualidade de Código e Testes

O projeto segue rigorosamente o padrão de estilo **PSR-12**, garantido por analisadores estáticos (`Laravel Pint`). O ecossistema de testes abrange cenários Unitários (isolando Use Cases, Entities e Value Objects) e de Feature (fluxos de HTTP completos e integração de banco de dados).

```bash
# Executar a suíte de testes completa
docker exec -it <subs-tracker-backend> php artisan module:test User
docker exec -it <subs-tracker-backend> php artisan module:test Subscription

# Ou utilize o script personalizado na raíz do projeto
./backend-exec php artisan module:test User
./backend-exec php artisan module:test Subscription

```