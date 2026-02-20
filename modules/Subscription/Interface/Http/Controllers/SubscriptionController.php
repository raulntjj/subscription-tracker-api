<?php

declare(strict_types=1);

namespace Modules\Subscription\Interface\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\DTOs\SortDTO;
use Illuminate\Validation\ValidationException;
use Modules\Shared\Application\DTOs\SearchDTO;
use Modules\Shared\Application\DTOs\PaginationDTO;
use Modules\Shared\Interface\Http\Responses\ApiResponse;
use Modules\Subscription\Application\DTOs\SubscriptionDTO;
use Modules\Subscription\Application\DTOs\CreateSubscriptionDTO;
use Modules\Subscription\Application\DTOs\UpdateSubscriptionDTO;
use Modules\Subscription\Application\Queries\FindSubscriptionByIdQuery;
use Modules\Subscription\Application\UseCases\CreateSubscriptionUseCase;
use Modules\Subscription\Application\UseCases\DeleteSubscriptionUseCase;
use Modules\Subscription\Application\UseCases\UpdateSubscriptionUseCase;
use Modules\Subscription\Application\Queries\FindSubscriptionOptionsQuery;
use Modules\Subscription\Application\UseCases\CalculateMonthlyBudgetUseCase;
use Modules\Subscription\Application\Queries\FindSubscriptionsPaginatedQuery;

final class SubscriptionController extends Controller
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
        private readonly CreateSubscriptionUseCase $createUseCase,
        private readonly UpdateSubscriptionUseCase $updateUseCase,
        private readonly DeleteSubscriptionUseCase $deleteUseCase,
        private readonly FindSubscriptionByIdQuery $findByIdQuery,
        private readonly FindSubscriptionOptionsQuery $findOptionsQuery,
        private readonly FindSubscriptionsPaginatedQuery $findPaginatedQuery,
        private readonly CalculateMonthlyBudgetUseCase $calculateBudgetUseCase,
    ) {
    }

    /**
     * GET /api/web/v1/subscriptions
     * Lista com paginação offset, busca e ordenação (web)
     *
     * Query params:
     * - page: número da página (default: 1)
     * - per_page: itens por página (default: 15)
     * - search: termo de busca
     * - sort_by: coluna(s) para ordenar (default: created_at)
     * - sort_direction: direção(ões) da ordenação (default: desc)
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

            $result = $this->findPaginatedQuery->execute($page, $perPage, $search, $sort);

            $data = array_map(
                fn (SubscriptionDTO $item) => $item->toArray(),
                $result->data
            );

            return ApiResponse::success([
                'subscriptions' => $data,
                'pagination' => [
                    'total' => $result->total,
                    'per_page' => $result->perPage,
                    'current_page' => $result->currentPage,
                    'last_page' => $result->lastPage,
                ],
            ], 'Subscriptions retrieved successfully');
        } catch (Throwable $e) {
            return ApiResponse::error(exception: $e);
        }
    }

    /**
     * GET /api/web/v1/subscriptions/options
     * Lista opções para selects/autocompletes
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

            $result = $this->findOptionsQuery->execute($search);

            return ApiResponse::success([
                'subscriptions' => $result->options,
                'total' => count($result->options),
            ], 'Subscription options retrieved successfully');
        } catch (Throwable $e) {
            return ApiResponse::error(exception: $e);
        }
    }

    /**
     * GET /api/web/v1/subscriptions/{id}
     */
    public function show(string $id): JsonResponse
    {
        try {
            $item = $this->findByIdQuery->execute($id);

            if ($item === null) {
                return ApiResponse::notFound('Subscription not found');
            }

            return ApiResponse::success(
                $item->toArray(),
                'Subscription retrieved successfully'
            );
        } catch (Throwable $e) {
            return ApiResponse::error(exception: $e);
        }
    }

    /**
     * POST /api/web/v1/subscriptions
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|integer|min:0',
                'currency' => 'required|string|in:BRL,USD,EUR',
                'billing_cycle' => 'required|string|in:monthly,yearly',
                'next_billing_date' => 'required|date|after_or_equal:today',
                'category' => 'required|string|max:255',
                'status' => 'sometimes|string|in:active,paused,cancelled',
            ]);

            $validated['user_id'] = auth('api')->id();

            $dto = CreateSubscriptionDTO::fromArray($validated);

            $item = $this->createUseCase->execute($dto);

            return ApiResponse::created(
                $item->toArray(),
                'Subscription created successfully'
            );
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        } catch (Throwable $e) {
            return ApiResponse::error(exception: $e);
        }
    }

    /**
     * PUT /api/web/v1/subscriptions/{id}
     * Atualização completa
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'price' => 'sometimes|integer|min:0',
                'currency' => 'sometimes|string|in:BRL,USD,EUR',
                'billing_cycle' => 'sometimes|string|in:monthly,yearly',
                'next_billing_date' => 'sometimes|date|after_or_equal:today',
                'category' => 'sometimes|string|max:255',
                'status' => 'sometimes|string|in:active,paused,cancelled',
            ]);

            $dto = UpdateSubscriptionDTO::fromArray($validated);

            $item = $this->updateUseCase->execute($id, $dto);

            return ApiResponse::success(
                $item->toArray(),
                'Subscription updated successfully'
            );
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::notFound($e->getMessage());
        } catch (Throwable $e) {
            return ApiResponse::error(exception: $e);
        }
    }

    /**
     * GET /api/web/v1/subscriptions/budget
     * Calcula o orçamento mensal do usuário autenticado
     *
     * Query params:
     * - currency: moeda desejada (default: BRL)
     */
    public function budget(Request $request): JsonResponse
    {
        try {
            // Assumindo que o usuário está autenticado via JWT
            $userId = auth()->id();

            if (!$userId) {
                return ApiResponse::unauthorized('User not authenticated');
            }

            $currency = $request->query('currency', 'BRL');

            $budgetDTO = $this->calculateBudgetUseCase->execute($userId, $currency);

            return ApiResponse::success(
                $budgetDTO->toArray(),
                'Monthly budget calculated successfully'
            );
        } catch (Throwable $e) {
            return ApiResponse::error(exception: $e);
        }
    }

    /**
     * DELETE /api/web/v1/subscriptions/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->deleteUseCase->execute($id);

            return ApiResponse::success(null, 'Subscription deleted successfully');
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::notFound($e->getMessage());
        } catch (Throwable $e) {
            return ApiResponse::error(exception: $e);
        }
    }
}
