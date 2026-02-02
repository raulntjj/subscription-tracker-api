# 🚀 Laravel DDD + CQRS Boilerplate

Boilerplate moderno para aplicações SaaS usando **Laravel Octane** com arquitetura **Modular Monolith** baseada em **Domain-Driven Design (DDD)** e **CQRS (Command Query Responsibility Segregation)**.

## 📋 Sobre o Projeto

Este boilerplate foi desenvolvido para criar aplicações escaláveis e de alta performance, separando **responsabilidades de escrita (Commands)** e **leitura (Queries)**, com cache inteligente usando Redis e estrutura modular que facilita manutenção e testes.

### ✨ Características Principais

- 🏗️ **Modular Monolith**: Módulos independentes com baixo acoplamento
- 🎯 **DDD (Domain-Driven Design)**: Camadas Domain, Application, Infrastructure e Interface
- 🔄 **CQRS Pattern**: Separação entre Commands (write) e Queries (read)
- ⚡ **Laravel Octane**: Alta performance com FrankenPHP
- 🗄️ **Redis Cache**: Cache inteligente com invalidação automática
- 📝 **Structured Logging**: Sistema de logs com 6 canais especializados
- 🐳 **Docker Ready**: Ambiente completo com Docker Compose
- 🧪 **Test-Driven**: Estrutura preparada para testes unitários e de integração

## 🛠️ Stack Tecnológica

- **PHP 8.2+** com strict types e readonly classes
- **Laravel 12** com Octane (FrankenPHP)
- **MySQL 8.0** para persistência
- **Redis 7** para cache e sessões
- **Docker & Docker Compose** para ambiente de desenvolvimento
- **PHPUnit** para testes

## 📦 Estrutura do Projeto

```
modules/
├── Shared/              # Componentes reutilizáveis
│   ├── Domain/         # Contratos e interfaces
│   ├── Application/    # Casos de uso compartilhados
│   ├── Infrastructure/ # Cache, Logging, Persistence
│   └── Interface/      # Respostas HTTP padronizadas
│
└── [Module]/           # Seus módulos de negócio
    ├── Domain/         # Entities, ValueObjects, Contracts
    ├── Application/    # Commands, Queries
    ├── Infrastructure/ # Repositories, Providers
    └── Interface/      # Controllers, Requests, Resources
```

## 🚀 Quick Start

### 1. Configure o ambiente

```bash
cp .env.example .env
```

### 2. Inicie o Docker

```bash
cd infrastructure/development
docker compose up -d
```

### 3. Instale dependências

```bash
docker compose exec backend composer install
```

### 4. Execute as migrations

```bash
docker compose exec backend php artisan migrate
```

### 5. Acesse a aplicação

- **API**: http://localhost:8001
- **Health Check**: http://localhost:8001/api/status
- **Redis Cache**: localhost:6379 (via DataGrip/RedisInsight)
- **Redis Sessions**: localhost:6380 (via DataGrip/RedisInsight)
- **Redis Queue**: localhost:6381 (via DataGrip/RedisInsight)
- **MySQL**: localhost:3306 (via DataGrip)

## 🏗️ Arquitetura

### CQRS Pattern

**Commands (Escrita)**
```php
// Modifica estado, invalida cache
$command = new CreateUserCommand(
    name: 'John Doe',
    email: 'john@example.com',
    password: 'secret'
);
$userId = $useCase->execute($command);
```

**Queries (Leitura)**
```php
// Apenas leitura, usa cache
$query = new FindUserByIdQuery($userId);
$user = $query->execute($query); // Cache-first
```

### Camadas DDD

1. **Domain**: Regras de negócio puras (Entities, ValueObjects)
2. **Application**: Casos de uso (Commands, Queries)
3. **Infrastructure**: Implementação técnica (Repositories, Cache)
4. **Interface**: Pontos de entrada (Controllers, APIs)

## 🎯 Comandos Úteis

### Criar novo módulo

```bash
php artisan module:make Product
```

### Criar migration de módulo

```bash
php artisan module:make-migration Product create_products_table
```

### Executar testes de módulo

```bash
php artisan module:test Product
```

### Verificar rotas disponíveis

```bash
# Rotas Web
php artisan route:list --path=web

# Rotas Mobile
php artisan route:list --path=mobile
```

### Limpar cache

```bash
php artisan cache:clear
php artisan config:clear
```

## 🐳 Serviços Docker

A arquitetura utiliza **containers isolados** para cada serviço, garantindo separação de responsabilidades e escalabilidade:

| Serviço | Porta | Descrição | Propósito |
|---------|-------|-----------|-----------|
| **backend** | 8001 | Laravel Octane (FrankenPHP) | Aplicação principal |
| **mysql-write** | 3306 | MySQL 8.0 | Banco de escrita |
| **redis-cache** | 6379 | Redis 7 | Cache de aplicação (volátil, LRU) |
| **redis-sessions** | 6380 | Redis 7 | Sessões de usuários (persistente, noeviction) |
| **redis-queue** | 6381 | Redis 7 | Filas de jobs (persistente, noeviction) |

### 🎯 Separação de Redis por Propósito

**Redis Cache (6379)**
- Policy: `allkeys-lru` - Remove chaves antigas automaticamente
- Persistência: Desabilitada - dados podem ser perdidos
- MaxMemory: 256MB
- Uso: Cache de queries, dados temporários

**Redis Sessions (6380)**
- Policy: `noeviction` - NUNCA remove sessões
- Persistência: AOF habilitada - sessões são críticas
- MaxMemory: 128MB
- Uso: Sessões de usuários autenticados

**Redis Queue (6381)**
- Policy: `noeviction` - Jobs não podem ser perdidos
- Persistência: AOF habilitada - garantia de processamento
- MaxMemory: 256MB
- Uso: Filas de background jobs

## 📚 Documentação

Documentação completa disponível na pasta `docs/`:

- **[ARCHITECTURE.md](docs/ARCHITECTURE.md)** - Arquitetura DDD + CQRS detalhada
- **[DIAGRAMS.md](docs/DIAGRAMS.md)** - Diagramas de fluxo e estrutura
- **[INFRASTRUCTURE.md](docs/INFRASTRUCTURE.md)** - Configuração Docker e serviços
- **[COMMANDS.md](docs/COMMANDS.md)** - Comandos artisan disponíveis

## 📮 Postman Collection

Collection pronta para importar e testar a API:

- **[Boilerplate.postman_collection.json](docs/postman/Boilerplate.postman_collection.json)** - Collection completa
- **[Local.postman_environment.json](docs/postman/Local.postman_environment.json)** - Variáveis de ambiente
- **[Instruções de uso](docs/postman/README.md)** - Como importar e usar

**Endpoints Disponíveis:**

### Web (Frontend Web)
- ✅ GET `/web/api/users` - Listar todos (sem paginação)
- 📄 GET `/web/api/users/paginated` - Paginação offset (navegação por páginas)
- 👤 GET `/web/api/users/{id}` - Buscar por ID (com cache)
- ➕ POST `/web/api/users` - Criar usuário

### Mobile (Apps Mobile)
- 📱 GET `/mobile/api/users/paginated` - Paginação cursor (infinite scroll)
- 🔄 Inclui script automático para salvar `next_cursor`

### Testes de Cache
- 🔄 Fluxo completo: MISS → HIT → Cache validation
- 📊 Scripts para salvar IDs automaticamente
- ⚡ Validação de performance (cache vs database)

## 🧪 Testes

Execute os testes com:

```bash
# Todos os testes
docker compose exec backend php artisan test

# Com coverage
docker compose exec backend php artisan test --coverage

# Teste específico
docker compose exec backend php artisan module:test User
```

## 🔒 Cache Strategy

O boilerplate usa **Cache-Aside Pattern** com Redis dedicado:

- **Redis Cache (6379)**: Cache volátil com LRU eviction
  - **Leitura (Query)**: Busca no cache primeiro, se miss busca no DB e armazena
  - **Escrita (Command)**: Atualiza DB e invalida cache relacionado
  - **TTL**: 3600 segundos (1 hora) por padrão
  - **Invalidação**: Automática via tags em Commands
  - **Policy**: `allkeys-lru` - remove dados antigos quando memória cheia

- **Redis Sessions (6380)**: Sessões críticas sem eviction
  - **Policy**: `noeviction` - sessões nunca são removidas
  - **Persistência**: AOF habilitada
  - **Isolamento**: Problemas no cache não afetam sessões

- **Redis Queue (6381)**: Jobs garantidos
  - **Policy**: `noeviction` - jobs não podem ser perdidos
  - **Persistência**: AOF habilitada
  - **Isolamento**: Filas separadas de cache e sessões

## 🎨 Padrões de Design

- **Repository Pattern**: Abstração de persistência
- **Factory Pattern**: Criação de objetos complexos (CacheServiceFactory)
- **Dependency Injection**: Inversão de controle
- **Value Object**: Objetos imutáveis (Email, Password)
- **Cache-Aside**: Pattern de cache com invalidação

## 📊 Logging

Sistema de logs estruturado com 6 canais:

- `application`: Logs gerais da aplicação
- `domain`: Eventos de domínio
- `infrastructure`: Operações de infraestrutura
- `security`: Eventos de segurança
- `audit`: Auditoria de ações
- `performance`: Métricas de performance

---