# Command Query Separation (CQS) e Fluxo de Dados

Visando performance, previsibilidade e segurança de tipos, a arquitetura de software da API implementa o padrão Command Query Separation (CQS). Existe uma separação estrita na camada de aplicação entre o código que altera o estado do sistema e o código que o lê.

## Data Transfer Objects (DTOs) como Fronteira
O tráfego de dados entre as extremidades da aplicação não utiliza arrays associativos soltos ou objetos dinâmicos (como o `Request` nativo do framework). Toda requisição validada no Controller é imediatamente convertida em um DTO (ex: `CreateSubscriptionDTO`).
* **Tipagem Forte:** DTOs fazem uso extensivo do PHP 8, empregando propriedades tipadas e `readonly`.
* **Imutabilidade:** Uma vez instanciados na camada de Interface, os dados chegam à camada de Aplicação imutáveis e previsíveis, mitigando efeitos colaterais.

## Operações de Escrita (Commands / Use Cases)
Qualquer operação que crie, atualize ou delete registros é tratada por um `UseCase`.
* **Single Responsibility:** Cada Use Case possui um único método público (geralmente `execute()`), focado em orquestrar uma única transação de negócio (ex: `ActivateWebhookUseCase`).
* **Segurança:** Use Cases injetam repositórios para persistência e não retornam estruturas de banco de dados diretamente, mantendo a integridade do modelo de domínio.

## Operações de Leitura (Queries) e Estratégia de Cache
Consultas ao banco de dados são isoladas em classes terminadas em `Query` (ex: `FindSubscriptionByIdQuery`). 
Embora a aplicação opere sobre uma única base de dados MySQL (não implementando Event Sourcing e repositórios separados que caracterizariam um CQRS completo), as Queries introduzem uma camada essencial de otimização de leitura: **O Cache Distribuído**.

* **Redis Integrado:** Repositórios base e Queries são projetados para interagir com a interface `CacheServiceInterface`.
* **Desempenho:** Respostas complexas ou volumosas são armazenadas em Redis. Isso reduz a carga de I/O no banco relacional e permite que leituras frequentes sejam resolvidas diretamente pela RAM, maximizando o *throughput* provido pelo servidor Swoole.