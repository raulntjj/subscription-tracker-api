# Padrão de Endpoints Web vs Mobile

## Visão Geral

Este documento define o padrão para criação de endpoints separados para **Web** e **Mobile**, com estratégias de paginação, busca e ordenação diferentes para cada plataforma.

## Motivação

### Por que separar Web e Mobile?

1. **Experiências diferentes**: Web usa paginação tradicional, mobile usa infinite scroll
2. **Performance**: Mobile precisa de respostas mais leves e rápidas
3. **Estratégias de paginação**: Offset para web, Cursor para mobile
4. **Evolução independente**: Mudanças em um não afetam o outro


## Padrão de Rotas

### Configuração no Service Provider

O prefixo `/api/web/v1` e `/api/mobile/v1` é adicionado no Service Provider:

```php
// modules/{Module}/Infrastructure/Providers/{Module}ServiceProvider.php

public function boot(): void
{
    Route::prefix('/api/web/v1')
        ->group(__DIR__ . '/../../Interface/Routes/web.php');

    Route::prefix('/api/mobile/v1')
        ->group(__DIR__ . '/../../Interface/Routes/mobile.php');

    $this->loadMigrationsFrom(__DIR__ . '/../Persistence/Migrations');
}
```

### Web Routes (`web.php`)

```php
Route::prefix('{resource}')->group(function () {
    // Listagem paginada com busca e ordenação
    Route::get('/', [ResourceController::class, 'index']);

    // Opções para selects/autocompletes
    Route::get('/options', [ResourceController::class, 'options']);

    // CRUD
    Route::post('/', [ResourceController::class, 'store']);
    Route::get('/{id}', [ResourceController::class, 'show']);
    Route::put('/{id}', [ResourceController::class, 'update']);
    Route::patch('/{id}', [ResourceController::class, 'partialUpdate']);
    Route::delete('/{id}', [ResourceController::class, 'destroy']);
});
```

### Mobile Routes (`mobile.php`)

```php
Route::prefix('{resource}')->group(function () {
    // Listagem com cursor pagination, busca e ordenação
    Route::get('/', [MobileResourceController::class, 'index']);

    // Opções para selects/autocompletes
    Route::get('/options', [MobileResourceController::class, 'options']);
});
```

### URLs Finais

**Web:**
| Método | URL                         | Controller                     |
|--------|-----------------------------|--------------------------------|
| GET    | `/api/web/v1/users`         | `UserController@index`         |
| GET    | `/api/web/v1/users/options` | `UserController@options`       |
| GET    | `/api/web/v1/users/{id}`    | `UserController@show`          |
| POST   | `/api/web/v1/users`         | `UserController@store`         |
| PUT    | `/api/web/v1/users/{id}`    | `UserController@update`        |
| PATCH  | `/api/web/v1/users/{id}`    | `UserController@partialUpdate` |
| DELETE | `/api/web/v1/users/{id}`    | `UserController@destroy`       |

**Mobile:**
| Método | URL                            | Controller                     |
|--------|--------------------------------|--------------------------------|
| GET    | `/api/mobile/v1/users`         | `MobileUserController@index`   |
| GET    | `/api/mobile/v1/users/options` | `MobileUserController@options` |

## Busca e Ordenação

### SearchDTO

Parâmetro de busca unificado que busca em múltiplas colunas:

```php
use Modules\Shared\Application\DTOs\SearchDTO;

// No controller - define colunas pesquisáveis
private const SEARCHABLE_COLUMNS = ['name', 'email'];

$search = SearchDTO::fromRequest(
    $request->query(),
    self::SEARCHABLE_COLUMNS
);
```

**Query param:** `?search=termo`

### SortDTO

Suporta múltiplas colunas de ordenação:

```php
use Modules\Shared\Application\DTOs\SortDTO;

// No controller - define colunas ordenáveis
private const SORTABLE_COLUMNS = ['name', 'email', 'created_at', 'updated_at'];

$sort = SortDTO::fromRequest(
    $request->query(),
    self::SORTABLE_COLUMNS
);
```

**Query params:**
- Simples: `?sort_by=name&sort_direction=asc`
- Múltiplo: `?sort_by=name,email&sort_direction=asc,desc`

## Tipos de Endpoint

### 1. Index (Listagem Principal)

O endpoint `index` é a listagem principal do recurso.

**Web** - Offset pagination com busca e ordenação:
```
GET /api/web/v1/{resource}?page=1&per_page=15&search=termo&sort_by=name&sort_direction=asc
```

**Mobile** - Cursor pagination com busca e ordenação:
```
GET /api/mobile/v1/{resource}?per_page=20&search=termo&sort_by=name&sort_direction=asc
```

### 2. Options (Para Selects/Autocompletes)

Lista sem paginação para popular selects. Suporta busca:

```
GET /api/web/v1/{resource}/options?search=termo
GET /api/mobile/v1/{resource}/options?search=termo
```

### 3. CRUD (apenas Web)

| Método | Endpoint           | Descrição            |
|--------|--------------------|----------------------|
| POST   | `/{resource}`      | Criar                |
| GET    | `/{resource}/{id}` | Detalhe              |
| PUT    | `/{resource}/{id}` | Atualizar (completo) |
| PATCH  | `/{resource}/{id}` | Atualizar (parcial)  |
| DELETE | `/{resource}/{id}` | Deletar              |

## Criando um Novo Módulo

### Via Artisan Command

```bash
php artisan module:make NomeDoModulo
```

O comando cria automaticamente toda a estrutura DDD completa:

- **Domain**: Entity, RepositoryInterface, ValueObjects
- **Application**: DTOs, UseCases (CRUD), Queries (com search/sort)
- **Infrastructure**: Model, Repository, Migration, ServiceProvider
- **Interface**: WebController, MobileController, Routes (web + mobile)
- **Tests**: Unit, Feature, Integration

### Após criar o módulo:

1. Registrar o ServiceProvider em `bootstrap/providers.php`
2. Executar `composer dump-autoload`
3. Executar `php artisan migrate`
4. Personalizar entidade, DTOs e validações

## Checklist para Novos Módulos

- [ ] Executar `php artisan module:make {Nome}`
- [ ] Personalizar Entity no Domain
- [ ] Ajustar DTOs (campos, validações)
- [ ] Configurar SEARCHABLE_COLUMNS e SORTABLE_COLUMNS nos controllers
- [ ] Registrar ServiceProvider em `bootstrap/providers.php`
- [ ] Executar `composer dump-autoload`
- [ ] Executar `php artisan migrate`
- [ ] Adicionar testes
- [ ] Atualizar Postman collection
