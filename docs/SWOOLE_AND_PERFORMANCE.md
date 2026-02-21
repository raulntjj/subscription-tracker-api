# Swoole e Alta Performance

A aplicação foi projetada para operar com o servidor de aplicação **Swoole** (através do Laravel Octane), alterando drasticamente a forma como o PHP lida com o processamento de requisições HTTP e maximizando o *throughput* (vazão) da API.

## O Paradigma Tradicional (PHP-FPM) vs. Swoole
No ecossistema tradicional do PHP-FPM, o ciclo de vida da aplicação é efêmero. A cada nova requisição HTTP, o servidor (Nginx/Apache) inicia um novo processo PHP, que por sua vez carrega todo o framework na memória, resolve as dependências do contêiner, lê ficheiros de configuração, processa a requisição e, ao final, destrói tudo. Esse processo de *bootstrap* gera um *overhead* significativo.

Com o **Swoole**, a API adota uma arquitetura residente em memória (Persistent Application). O framework Laravel, juntamente com todas as suas configurações e contêineres de injeção de dependência base, é carregado na RAM (Random Access Memory) apenas uma vez durante a inicialização do servidor. 

## Vantagens e Ganhos Arquiteturais
* **Eliminação do *Overhead* de Bootstrap:** Como a aplicação já está "quente" na memória, o tempo de resposta inicial (TTFB - Time to First Byte) cai de dezenas de milissegundos para frações de milissegundos.
* **I/O Assíncrono e Concorrência:** O Swoole permite que a aplicação lide com múltiplas conexões concorrentes bloqueando minimamente os processos (através de *coroutines*), garantindo que operações de rede e consultas à base de dados não estrangulem o servidor sob alta carga.

## Cuidados com o Ciclo de Vida em Memória (State Leaks)
A persistência em memória exige padrões estritos de programação que foram aplicados neste projeto:
* **Gestão de Estado:** Variáveis estáticas e *Singletons* mantêm o seu estado entre as requisições. O código foi desenhado para garantir que instâncias resolvidas pelo contêiner de Injeção de Dependência (IoC) não vazem dados (State Leakage) de uma requisição de um utilizador para outra.
* **Isolamento via DTOs e Use Cases:** A imutabilidade dos DTOs e o isolamento das regras de negócio em *Use Cases* de ciclo de vida curto garantem que o estado da aplicação seja limpo e seguro a cada nova transação HTTP.