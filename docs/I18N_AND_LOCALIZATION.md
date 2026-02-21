Aqui está a proposta para a micro-documentação referente à internacionalização da API, mantendo o rigor técnico, o tom profissional e sem a utilização de elementos informais.

Pode guardar este conteúdo no ficheiro `docs/I18N_AND_LOCALIZATION.md` (e, se desejar, adicionar o link correspondente no `README.md` principal).

---

# Internacionalização e Suporte Multilinguagem (i18n)

A aplicação foi projetada com suporte nativo à internacionalização (i18n), permitindo que os clientes consumidores da API (aplicações web, dispositivos móveis ou serviços de terceiros) determinem dinamicamente o idioma em que desejam receber as respostas, exceções e validações do sistema.

Atualmente, o sistema suporta de forma nativa os idiomas Inglês (`en`) e Português do Brasil (`pt-BR`).

## O Mecanismo de Ação (HTTP Headers)

A negociação de conteúdo para o idioma não depende de rotas específicas ou parâmetros na URL. O mecanismo baseia-se estritamente no protocolo HTTP, utilizando o cabeçalho padrão `Accept-Language`.

O fluxo ocorre da seguinte forma:

1. O cliente efetua um pedido HTTP injetando o cabeçalho `Accept-Language: pt-BR` (ou `en`).
2. O pedido é intercetado na camada de Interface pelo `SetLocaleMiddleware` (localizado no módulo `Shared`).
3. O middleware valida o idioma solicitado contra os idiomas suportados pela aplicação.
4. Sendo válido, o *locale* do framework é alterado em tempo de execução exclusivamente para o ciclo de vida daquela transação.

Devido à natureza persistente em memória do servidor **Swoole**, esta alteração de *locale* é tratada de forma isolada por requisição, garantindo que não ocorra vazamento de estado (State Leakage) entre pedidos concorrentes de utilizadores distintos.

## Segregação Arquitetural dos Dicionários

Em conformidade com as diretrizes do **Domain-Driven Design (DDD)** e da arquitetura de Monolito Modular, a aplicação não centraliza os ficheiros de idioma num diretório global (como o diretório `lang/` padrão do framework).

Os ficheiros de tradução estão rigorosamente encapsulados na camada de Infraestrutura de cada *Bounded Context*. A estrutura de diretórios segue o padrão:

```text
modules/{NomeDoModulo}/Infrastructure/Lang/
├── en/
│   ├── exception.php
│   ├── message.php
│   └── validation.php
└── pt-BR/
    ├── exception.php
    ├── message.php
    └── validation.php

```

Esta abordagem garante **Alta Coesão**. O módulo `Subscription`, por exemplo, detém controlo absoluto sobre o seu próprio vocabulário e regras de tradução. Se o módulo for extraído para um microsserviço no futuro, toda a sua estrutura de internacionalização será migrada juntamente com ele, sem dependências de um núcleo partilhado.

## Âmbito da Tradução

O motor de internacionalização abrange todas as camadas de saída de dados da API:

1. **Mensagens de Validação (Payloads):**
Erros gerados durante a validação de dados de entrada nos *Controllers* antes da criação dos *Data Transfer Objects (DTOs)*. As regras de obrigatoriedade, tipagem e formato são traduzidas para o idioma do cliente.
2. **Exceções de Domínio (Exceptions):**
Quando uma regra de negócio é violada na camada de *Application* ou *Domain* (por exemplo, ao tentar cancelar uma subscrição já inativa), a exceção lançada possui uma chave de tradução. O *Handler* global de exceções interceta o erro e traduz a mensagem antes de devolver a resposta HTTP com o respetivo *Status Code*.
3. **Respostas Padronizadas (Api Responses):**
A classe `ApiResponse`, responsável por formatar e unificar a estrutura de saída da API (retornando propriedades uniformes como `success`, `message` e `data`), resolve as chaves de tradução injetadas pelos *Controllers* para informar o utilizador sobre o sucesso ou falha da operação no idioma adequado.