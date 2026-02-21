# Motor CLI e Geração de Código (Stubs)

A transição do modelo MVC padrão para o Domain-Driven Design (DDD) e a arquitetura de Monolito Modular introduz uma complexidade estrutural inerente. Para garantir que os programadores mantenham a velocidade de entrega sem comprometer a consistência arquitetural, o módulo `Shared` atua como um motor de geração de código personalizado.

## O Desafio do *Boilerplate*
Ferramentas nativas (como `php artisan make:model` ou `make:controller`) geram ficheiros nas pastas padrão do Laravel (`app/Models`, `app/Http/Controllers`), quebrando o isolamento dos módulos. Além disso, a arquitetura exige a criação de múltiplas classes para um único fluxo (Entity, Interface, Repository, UseCase, DTO, Controller).

## Funcionamento do Motor de Stubs
O repositório contém comandos Artisan customizados (localizados em `modules/Shared/Console/Commands/`) que operam em conjunto com *Stubs* (`modules/Shared/Console/Stubs/`). Um stub é um ficheiro de template que contém a estrutura fundamental de um padrão de projeto.

Ao executar um comando de criação, o motor:
1. Analisa os parâmetros de entrada (ex: nome do módulo e entidade).
2. Carrega o stub correspondente (ex: `CreateEntityUseCase.stub`).
3. Substitui as variáveis de contexto e as definições de tipagem (`{{ namespace }}`, `{{ class }}`).
4. Compila e guarda o ficheiro final no subdiretório exato exigido pela Clean Architecture.

## Principais Comandos Customizados

* `module:create {NomeDoModulo}`
  Responsável pelo *scaffolding* completo. Este comando inicializa um *Bounded Context* virgem. Ele cria instantaneamente a hierarquia das camadas `Domain`, `Application`, `Infrastructure` e `Interface`, já configurando os *Service Providers* necessários para o registo do módulo na aplicação.

* `module:make-migration {name} --module={Modulo}`
  Garante que a evolução do esquema da base de dados respeite o encapsulamento. A *migration* gerada é alocada em `modules/{Modulo}/Infrastructure/Persistence/Migrations/`, isolando as tabelas pertinentes àquele domínio específico.

* `module:test {NomeDoModulo}`
  Permite a execução direcionada da suíte de testes. Em vez de percorrer todo o ecossistema, o comando executa exclusivamente os testes unitários e de integração (Features) pertencentes ao módulo informado, facilitando o ciclo de desenvolvimento orientado a testes (TDD).

## Garantia de Consistência
A presença destas ferramentas reduz a curva de aprendizagem de novos programadores no projeto e impõe, de forma automatizada, a adesão a princípios como Inversão de Dependência (DIP) e Separação de Responsabilidades (SRP), uma vez que as classes geradas já nascem com as injeções de repositórios e DTOs pré-configuradas.