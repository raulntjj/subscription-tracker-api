# Arquitetura e Domain-Driven Design (DDD)

A aplicação Subscription Tracker adota a arquitetura de Monolito Modular, desenhada para isolar contextos de negócio (Bounded Contexts) e permitir uma futura e indolor transição para microsserviços, caso a volumetria ou complexidade exijam.

## Isolamento de Módulos (Bounded Contexts)
O código está segregado no diretório `modules/`. Atualmente, a aplicação compreende os domínios `User`, `Subscription` e `Shared`. O princípio fundamental desta divisão é a coesão: um módulo não deve acessar o banco de dados de outro módulo diretamente, e a comunicação cruzada deve ocorrer estritamente através de contratos de interface ou eventos de domínio.

## Camadas Arquiteturais
Cada módulo implementa uma variação da Clean Architecture dividida em quatro camadas principais, com regras rígidas de dependência (de fora para dentro):

### 1. Domain (Núcleo)
A camada mais interna e de maior valor da aplicação.
* **Responsabilidade:** Conter as regras de negócio puras, entidades, Value Objects (ex: `Email`, `Password`) e Enums.
* **Restrição Arquitetural:** Não possui dependência de nenhuma outra camada, framework (Laravel), ORM (Eloquent) ou biblioteca externa.
* **Contracts:** Define as interfaces (Portas) que o domínio necessita para funcionar, como o `SubscriptionRepositoryInterface`.

### 2. Application (Orquestração)
Atua como a ponte entre o mundo externo e o domínio.
* **Responsabilidade:** Orquestrar o fluxo de dados, contendo os Casos de Uso (Use Cases) e Queries.
* **Integração:** Consome as interfaces definidas no Domínio e dispara eventos e *Jobs*. Esta camada não sabe *como* os dados são salvos, apenas solicita que sejam salvos através dos contratos injetados.

### 3. Infrastructure (Implementação Técnica)
A camada onde o código interage com o framework e serviços externos.
* **Responsabilidade:** Implementar as interfaces definidas no Domínio. Contém os Repositórios concretos (`SubscriptionRepository`, que utiliza o modelo Eloquent), Migrations isoladas por módulo, Service Providers, integrações com RabbitMQ, Redis e serviços de e-mail.
* **Inversão de Dependência (DIP):** É através dos Service Providers desta camada que o contêiner de injeção de dependência do Laravel mapeia as interfaces do Domínio para as implementações da Infraestrutura.

### 4. Interface (Entrega)
A porta de entrada da aplicação.
* **Responsabilidade:** Receber requisições HTTP, CLI ou de sistemas de mensageria, validá-las, transformá-las em Data Transfer Objects (DTOs) e repassá-las para a camada de Aplicação. Contém Controllers, Middlewares e definição de rotas.