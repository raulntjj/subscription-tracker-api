<?php

declare(strict_types=1);

namespace Modules\User\Interface\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\User\Application\DTOs\UserDTO;
use Illuminate\Validation\ValidationException;
use Modules\User\Application\DTOs\CreateUserDTO;
use Modules\User\Application\DTOs\UpdateUserDTO;
use Modules\Shared\Application\DTOs\SearchDTO;
use Modules\Shared\Application\DTOs\SortDTO;
use Modules\Shared\Application\DTOs\PaginationDTO;
use Modules\User\Application\Queries\FindUserByIdQuery;
use Modules\User\Application\Queries\FindUserOptionsQuery;
use Modules\Shared\Interface\Http\Responses\ApiResponse;
use Modules\User\Application\UseCases\CreateUserUseCase;
use Modules\User\Application\UseCases\UpdateUserUseCase;
use Modules\User\Application\UseCases\DeleteUserUseCase;
use Modules\User\Application\Queries\FindUsersPaginatedQuery;
use Modules\User\Application\UseCases\PartialUpdateUserUseCase;

final class UserController extends Controller
{
    /**
     * Colunas permitidas para busca
     */
    private const SEARCHABLE_COLUMNS = ['name', 'surname', 'email'];

    /**
     * Colunas permitidas para ordenação
     */
    private const SORTABLE_COLUMNS = ['name', 'surname', 'email', 'created_at', 'updated_at'];

    public function __construct(
        private readonly CreateUserUseCase $createUserUseCase,
        private readonly UpdateUserUseCase $updateUserUseCase,
        private readonly DeleteUserUseCase $deleteUserUseCase,
        private readonly FindUserByIdQuery $findUserByIdQuery,
        private readonly FindUserOptionsQuery $findUserOptionsQuery,
        private readonly FindUsersPaginatedQuery $findUsersPaginatedQuery,
        private readonly PartialUpdateUserUseCase $partialUpdateUserUseCase,
    ) {
    }

    /**
     * GET /api/web/v1/users
     * Lista usuários com paginação offset, busca e ordenação (web)
     *
     * Query params:
     * - page: número da página (default: 1)
     * - per_page: itens por página (default: 15)
     * - search: termo de busca (busca em name e email)
     * - sort_by: coluna(s) para ordenar (default: created_at) - separar por vírgula para múltiplos
     * - sort_direction: direção(ões) da ordenação (default: desc) - separar por vírgula para múltiplos
     */
    public function paginated(Request $request): JsonResponse
    {
        try {
            $page = (int) ($request->query('page') ?? 1);
            $perPage = (int) ($request->query('per_page') ?? 15);

            $search = SearchDTO::fromRequest(
                $request->query(),
                self::SEARCHABLE_COLUMNS
            );

            $sort = SortDTO::fromRequest(
                $request->query(),
                self::SORTABLE_COLUMNS
            );

            $paginator = $this->findUsersPaginatedQuery->execute($page, $perPage, $search, $sort);

            $usersData = array_map(
                fn (UserDTO $user) => $user->toArray(),
                $paginator->items()
            );

            $pagination = PaginationDTO::fromPaginator($paginator);

            return ApiResponse::success([
                'users' => $usersData,
                'pagination' => $pagination->toArray(),
            ], 'Users retrieved successfully');
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * GET /api/web/v1/users/options
     * Lista opções de usuários para selects/autocompletes
     * Retorna lista leve sem paginação, com suporte a busca
     *
     * Query params:
     * - search: termo de busca (busca em name e email)
     */
    public function options(Request $request): JsonResponse
    {
        try {
            $search = SearchDTO::fromRequest(
                $request->query(),
                self::SEARCHABLE_COLUMNS
            );

            $users = $this->findUserOptionsQuery->execute($search);

            $usersData = array_map(
                fn (UserDTO $user) => $user->toOptions(),
                $users
            );

            return ApiResponse::success([
                'users' => $usersData,
                'total' => count($usersData),
            ], 'User options retrieved successfully');
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * GET /api/web/v1/users/{id}
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = $this->findUserByIdQuery->execute($id);

            if ($user === null) {
                return ApiResponse::notFound('User not found');
            }

            return ApiResponse::success(
                $user->toArray(),
                'User retrieved successfully'
            );
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * POST /api/web/v1/users
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
                'surname' => 'nullable|string|max:255',
                'profile_path' => 'nullable|string|max:500',
            ]);

            $dto = CreateUserDTO::fromArray($validated);

            $user = $this->createUserUseCase->execute(
                name: $dto->name,
                email: $dto->email,
                password: $dto->password,
                surname: $dto->surname,
                profilePath: $dto->profilePath
            );

            return ApiResponse::created(
                $user->toArray(),
                'User created successfully'
            );
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * PUT /api/web/v1/users/{id}
     * Atualização completa do usuário
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $id,
                'password' => 'required|string|min:8',
                'surname' => 'nullable|string|max:255',
                'profile_path' => 'nullable|string|max:500',
            ]);

            $dto = UpdateUserDTO::fromArray($validated);

            $user = $this->updateUserUseCase->execute($id, $dto);

            return ApiResponse::success(
                $user->toArray(),
                'User updated successfully'
            );
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::notFound($e->getMessage());
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * PATCH /api/web/v1/users/{id}
     * Atualização parcial do usuário
     */
    public function partialUpdate(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $id,
                'password' => 'sometimes|string|min:8',
                'surname' => 'nullable|string|max:255',
                'profile_path' => 'nullable|string|max:500',
            ]);

            $dto = UpdateUserDTO::fromArray($validated);

            $user = $this->partialUpdateUserUseCase->execute($id, $dto);

            return ApiResponse::success(
                $user->toArray(),
                'User patched successfully'
            );
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::notFound($e->getMessage());
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * DELETE /api/web/v1/users/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->deleteUserUseCase->execute($id);

            return ApiResponse::success(null, 'User deleted successfully');
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::notFound($e->getMessage());
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }
}
