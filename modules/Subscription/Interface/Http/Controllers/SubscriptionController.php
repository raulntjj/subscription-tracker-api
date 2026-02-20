<?php

declare(strict_types=1);

namespace Modules\Subscription\Interface\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Shared\Application\DTOs\SortDTO;
use Illuminate\Validation\ValidationException;
use Modules\Shared\Application\DTOs\SearchDTO;
use Modules\Shared\Interface\Http\Responses\ApiResponse;
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
            $page = (int) ($request->query(key: 'page') ?? 1);
            $perPage = (int) ($request->query(key: 'per_page') ?? 15);

            $search = SearchDTO::fromRequest(
                params: $request->query(),
                searchableColumns: self::SEARCHABLE_COLUMNS,
            );

            $sort = SortDTO::fromRequest(
                params: $request->query(),
                sortableColumns: self::SORTABLE_COLUMNS,
            );

            $result = $this->findPaginatedQuery->execute(
                page: $page,
                perPage: $perPage,
                search: $search,
                sort: $sort,
            );

            return ApiResponse::success(
                data: $result->toArray(),
                message: __('Subscription::message.subscriptions_retrieved_success'),
            );
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
                params: $request->query(),
                searchableColumns: self::SEARCHABLE_COLUMNS,
            );

            $result = $this->findOptionsQuery->execute(search: $search);

            return ApiResponse::success(
                data: $result->toArray(),
                message: __('Subscription::message.subscription_options_retrieved_success'),
            );
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
            $item = $this->findByIdQuery->execute(id: $id);

            if ($item === null) {
                return ApiResponse::notFound(message: __('Subscription::message.subscription_not_found'));
            }

            return ApiResponse::success(
                data: $item->toArray(),
                message: __('Subscription::message.subscription_retrieved_success'),
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
            $validated = $request->validate(rules: [
                'name' => 'required|string|max:255',
                'price' => 'required|integer|min:0',
                'currency' => 'required|string|in:BRL,USD,EUR',
                'billing_cycle' => 'required|string|in:monthly,yearly',
                'next_billing_date' => 'required|date|after_or_equal:today',
                'category' => 'required|string|max:255',
                'status' => 'sometimes|string|in:active,paused,cancelled',
            ]);

            $validated['user_id'] = auth(guard: 'api')->id();

            $dto = CreateSubscriptionDTO::fromArray(data: $validated);

            $item = $this->createUseCase->execute(dto: $dto);

            return ApiResponse::created(
                data: $item->toArray(),
                message: __('Subscription::message.subscription_created_success'),
            );
        } catch (ValidationException $e) {
            return ApiResponse::validationError(errors: $e->errors());
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
            $validated = $request->validate(rules: [
                'name' => 'required|string|max:255',
                'price' => 'required|integer|min:0',
                'currency' => 'required|string|in:BRL,USD,EUR',
                'billing_cycle' => 'required|string|in:monthly,yearly',
                'next_billing_date' => 'required|date|after_or_equal:today',
                'category' => 'required|string|max:255',
                'status' => 'required|string|in:active,paused,cancelled',
            ]);

            $dto = UpdateSubscriptionDTO::fromArray(data: $validated);

            $item = $this->updateUseCase->execute(id: $id, dto: $dto);

            return ApiResponse::success(
                $item->toArray(),
                __('Subscription::message.subscription_updated_success'),
            );
        } catch (ValidationException $e) {
            return ApiResponse::validationError(errors: $e->errors());
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::notFound(message: $e->getMessage());
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
            $userId = auth(guard: 'api')->id();

            if (!$userId) {
                return ApiResponse::unauthorized(message: __('Subscription::message.user_not_authenticated'));
            }

            $currency = $request->query(key: 'currency', default: 'BRL');

            $budgetDTO = $this->calculateBudgetUseCase->execute(userId: $userId, currency: $currency);

            return ApiResponse::success(
                data: $budgetDTO->toArray(),
                message: __('Subscription::message.monthly_budget_calculated_success'),
            );
        } catch (Throwable $e) {
            return ApiResponse::error(exception: $e);
        }
    }

    /**
     * DELETE /api/web/v1/subscriptions/{id}
     */
    public function destroy(string $id): Response | JsonResponse
    {
        try {
            $this->deleteUseCase->execute(id: $id);

            return ApiResponse::noContent();
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::notFound(message: $e->getMessage());
        } catch (Throwable $e) {
            return ApiResponse::error(exception: $e);
        }
    }
}
