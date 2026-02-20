<?php

declare(strict_types=1);

namespace Modules\User\Interface\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\DTOs\SortDTO;
use Modules\Shared\Application\DTOs\SearchDTO;
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
            $cursor = $request->query(key: 'cursor');
            $perPage = (int) ($request->query(key: 'per_page') ?? 10);

            $search = SearchDTO::fromRequest(
                params: $request->query(),
                searchableColumns: self::SEARCHABLE_COLUMNS,
            );

            $sort = SortDTO::fromRequest(
                params: $request->query(),
                sortableColumns: self::SORTABLE_COLUMNS,
            );

            $cursorPaginatedDTO = $this->findUsersCursorPaginatedQuery->execute(
                cursor: $cursor,
                perPage: $perPage,
                search: $search,
                sort: $sort,
            );

            return ApiResponse::success(
                data: $cursorPaginatedDTO->toArray(),
                message: __('User::message.users_retrieved_success'),
            );
        } catch (Throwable $e) {
            return ApiResponse::error(exception: $e);
        }
    }
}
