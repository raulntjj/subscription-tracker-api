<?php

declare(strict_types=1);

namespace Modules\Subscription\Interface\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\DTOs\SortDTO;
use Modules\Shared\Application\DTOs\SearchDTO;
use Modules\Shared\Interface\Responses\ApiResponse;
use Modules\Subscription\Application\Queries\FindSubscriptionOptionsQuery;
use Modules\Subscription\Application\Queries\FindSubscriptionsCursorPaginatedQuery;

/**
 * Controller para endpoints mobile
 */
final class MobileSubscriptionController extends Controller
{
    /**
     * Colunas permitidas para busca
     */
    private const SEARCHABLE_COLUMNS = ['name'];

    /**
     * Colunas permitidas para ordenação
     */
    private const SORTABLE_COLUMNS = ['name', 'created_at', 'updated_at'];

    public function __construct(
        private readonly FindSubscriptionOptionsQuery $findOptionsQuery,
        private readonly FindSubscriptionsCursorPaginatedQuery $findCursorPaginatedQuery,
    ) {
    }

    /**
     * GET /api/mobile/v1/subscriptions
     * Lista com cursor pagination, busca e ordenação (mobile)
     *
     * Query params:
     * - cursor: cursor para próxima página (opcional)
     * - per_page: itens por página (default: 20)
     * - search: termo de busca
     * - sort_by: coluna(s) para ordenar (default: created_at)
     * - sort_direction: direção(ões) da ordenação (default: desc)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $cursor = $request->query(key: 'cursor');
            $perPage = (int) ($request->query(key: 'per_page') ?? 20);

            $search = SearchDTO::fromRequest(
                params: $request->query(),
                searchableColumns: self::SEARCHABLE_COLUMNS,
            );

            $sort = SortDTO::fromRequest(
                params: $request->query(),
                sortableColumns: self::SORTABLE_COLUMNS,
            );

            $cursorPaginatedDTO = $this->findCursorPaginatedQuery->execute(
                cursor: $cursor,
                perPage: $perPage,
                search: $search,
                sort: $sort,
            );

            return ApiResponse::success(
                data: $cursorPaginatedDTO->toArray(),
                message: __('Subscription::message.subscriptions_retrieved_success'),
            );
        } catch (Throwable $e) {
            return ApiResponse::error(exception: $e);
        }
    }
}
