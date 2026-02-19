<?php

declare(strict_types=1);

namespace Modules\Subscription\Interface\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\DTOs\SortDTO;
use Modules\Shared\Application\DTOs\SearchDTO;
use Modules\Shared\Application\DTOs\CursorPaginationDTO;
use Modules\Shared\Interface\Http\Responses\ApiResponse;
use Modules\Subscription\Application\DTOs\SubscriptionDTO;
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
            $cursor = $request->query('cursor');
            $perPage = (int) ($request->query('per_page') ?? 20);

            $search = SearchDTO::fromRequest(
                $request->query(),
                self::SEARCHABLE_COLUMNS
            );

            $sort = SortDTO::fromRequest(
                $request->query(),
                self::SORTABLE_COLUMNS
            );

            $paginator = $this->findCursorPaginatedQuery->execute($cursor, $perPage, $search, $sort);

            $data = array_map(
                fn (SubscriptionDTO $item) => $item->toOptions(),
                $paginator->items()
            );

            $pagination = CursorPaginationDTO::fromCursorPaginator($paginator);

            return ApiResponse::success([
                'subscriptions' => $data,
                'pagination' => $pagination->toArray(),
            ], 'Subscriptions retrieved successfully');
        } catch (Throwable $e) {
            return ApiResponse::error(exception: $e);
        }
    }

    /**
     * GET /api/mobile/v1/subscriptions/options
     * Lista opções para selects/autocompletes (mobile)
     *
     * Query params:
     * - search: termo de busca
     */
    public function options(Request $request): JsonResponse
    {
        try {
            $search = SearchDTO::fromRequest(
                $request->query(),
                self::SEARCHABLE_COLUMNS
            );

            $items = $this->findOptionsQuery->execute($search);

            $data = array_map(
                fn (SubscriptionDTO $item) => $item->toArray(),
                $items
            );

            return ApiResponse::success([
                'subscriptions' => $data,
                'total' => count($data),
            ], 'Subscription options retrieved successfully');
        } catch (Throwable $e) {
            return ApiResponse::error(exception: $e);
        }
    }
}
