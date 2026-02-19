<?php

declare(strict_types=1);

namespace Modules\User\Interface\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\User\Application\DTOs\UserDTO;
use Modules\Shared\Application\DTOs\SearchDTO;
use Modules\Shared\Application\DTOs\SortDTO;
use Modules\Shared\Interface\Http\Responses\ApiResponse;
use Modules\User\Application\Queries\FindUserOptionsQuery;
use Modules\User\Application\Queries\FindUsersCursorPaginatedQuery;

/**
 * Controller para endpoints mobile
 */
final class MobileUserController extends Controller
{
    /**
     * Colunas permitidas para busca
     */
    private const SEARCHABLE_COLUMNS = ['name', 'email'];

    /**
     * Colunas permitidas para ordenação
     */
    private const SORTABLE_COLUMNS = ['name', 'email', 'created_at', 'updated_at'];

    public function __construct(
        private readonly FindUsersCursorPaginatedQuery $findUsersCursorPaginatedQuery,
        private readonly FindUserOptionsQuery $findUserOptionsQuery,
    ) {
    }

    /**
     * GET /api/mobile/v1/users
     * Lista usuários com cursor pagination, busca e ordenação (mobile)
     *
     * Query params:
     * - cursor: cursor para próxima página (opcional)
     * - per_page: itens por página (default: 20)
     * - search: termo de busca (busca em name e email)
     * - sort_by: coluna(s) para ordenar (default: created_at)
     * - sort_direction: direção(ões) da ordenação (default: desc)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $cursor = $request->query('cursor');
            $perPage = (int) ($request->query('per_page') ?? 10);

            $search = SearchDTO::fromRequest(
                $request->query(),
                self::SEARCHABLE_COLUMNS
            );

            $sort = SortDTO::fromRequest(
                $request->query(),
                self::SORTABLE_COLUMNS
            );

            $cursorPaginatedDTO = $this->findUsersCursorPaginatedQuery->execute($cursor, $perPage, $search, $sort);

            return ApiResponse::success(
                data: $cursorPaginatedDTO->toArray(),
                message: 'Users retrieved successfully'
            );
        } catch (Throwable $e) {
            return ApiResponse::error(exception: $e);
        }
    }

    /**
     * GET /api/mobile/v1/users/options
     * Lista opções de usuários para selects/autocompletes (mobile)
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

            $optionsDTO = $this->findUserOptionsQuery->execute($search);

            return ApiResponse::success(
                data: $optionsDTO->toArray(),
                message: 'User options retrieved successfully'
            );
        } catch (Throwable $e) {
            return ApiResponse::error(exception: $e);
        }
    }
}
