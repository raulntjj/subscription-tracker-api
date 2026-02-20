<?php

declare(strict_types=1);

namespace Modules\Subscription\Interface\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Shared\Interface\Http\Responses\ApiResponse;
use Modules\Subscription\Application\DTOs\CreateWebhookConfigDTO;
use Modules\Subscription\Application\DTOs\UpdateWebhookConfigDTO;
use Modules\Subscription\Application\UseCases\TestWebhookUseCase;
use Modules\Subscription\Application\Queries\GetWebhookConfigsQuery;
use Modules\Subscription\Application\UseCases\ActivateWebhookUseCase;
use Modules\Subscription\Application\Queries\GetWebhookConfigByIdQuery;
use Modules\Subscription\Application\UseCases\DeactivateWebhookUseCase;
use Modules\Subscription\Application\UseCases\CreateWebhookConfigUseCase;
use Modules\Subscription\Application\UseCases\DeleteWebhookConfigUseCase;
use Modules\Subscription\Application\UseCases\UpdateWebhookConfigUseCase;

final class WebhookConfigController extends Controller
{
    public function __construct(
        private readonly TestWebhookUseCase $testUseCase,
        private readonly ActivateWebhookUseCase $activateUseCase,
        private readonly GetWebhookConfigsQuery $getConfigsQuery,
        private readonly CreateWebhookConfigUseCase $createUseCase,
        private readonly UpdateWebhookConfigUseCase $updateUseCase,
        private readonly DeleteWebhookConfigUseCase $deleteUseCase,
        private readonly DeactivateWebhookUseCase $deactivateUseCase,
        private readonly GetWebhookConfigByIdQuery $getConfigByIdQuery,
    ) {
    }

    /**
     * GET /api/web/v1/webhooks
     * Lista todas as configurações de webhook do usuário
     */
    public function index(): JsonResponse
    {
        try {
            $userId = auth(guard: 'api')->id();
            $items = $this->getConfigsQuery->execute(userId: $userId);

            return ApiResponse::success([
                'webhooks' => $items,
                'total' => count(value: $items),
            ], message: __('Subscription::exception.configs_retrieved_success'));
        } catch (Throwable $e) {
            return ApiResponse::error(exception: $e);
        }
    }

    /**
     * GET /api/web/v1/webhooks/{id}
     * Retorna uma configuração de webhook específica
     */
    public function show(string $id): JsonResponse
    {
        try {
            $userId = auth(guard: 'api')->id();
            $item = $this->getConfigByIdQuery->execute(id: $id, userId: $userId);

            if ($item === null) {
                return ApiResponse::notFound(message: __('Subscription::exception.config_not_found'));
            }

            return ApiResponse::success(
                data: $item->toArray(),
                message: __('Subscription::exception.config_retrieved_success'),
            );
        } catch (Throwable $e) {
            return ApiResponse::error(exception: $e);
        }
    }

    /**
     * POST /api/web/v1/webhooks
     * Cria uma nova configuração de webhook
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate(rules: [
                'url' => ['required', 'string', 'url', 'max:500'],
                'secret' => ['nullable', 'string', 'min:8', 'max:255'],
            ]);

            $validated['user_id'] = auth(guard: 'api')->id();
            $dto = CreateWebhookConfigDTO::fromArray(data: $validated);

            $item = $this->createUseCase->execute(dto: $dto);

            return ApiResponse::created(
                data: $item->toArray(),
                message: __('Subscription::exception.config_created_success'),
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::validationError(errors: $e->errors());
        } catch (Throwable $e) {
            return ApiResponse::error(exception: $e);
        }
    }

    /**
     * PUT /api/web/v1/webhooks/{id}
     * Atualiza uma configuração de webhook
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            // Verifica se o webhook pertence ao usuário
            $userId = auth(guard: 'api')->id();
            $existing = $this->getConfigByIdQuery->execute(id: $id, userId: $userId);

            if ($existing === null) {
                return ApiResponse::notFound(message: __('Subscription::exception.config_not_found'));
            }

            $validated = $request->validate(rules: [
                'url' => ['sometimes', 'string', 'url', 'max:500'],
                'secret' => ['sometimes', 'string', 'min:8', 'max:255'],
            ]);

            $dto = UpdateWebhookConfigDTO::fromArray(data: [
                'id' => $id,
                'url' => $validated['url'] ?? null,
                'secret' => $validated['secret'] ?? null,
            ]);

            $item = $this->updateUseCase->execute(dto: $dto);

            return ApiResponse::success(
                data: $item->toArray(),
                message: __('Subscription::exception.config_updated_success'),
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::validationError(errors: $e->errors());
        } catch (Throwable $e) {
            return ApiResponse::error(exception: $e);
        }
    }

    /**
     * DELETE /api/web/v1/webhooks/{id}
     * Remove uma configuração de webhook
     */
    public function destroy(string $id): Response | JsonResponse
    {
        try {
            // Verifica se o webhook pertence ao usuário
            $userId = auth(guard: 'api')->id();
            $existing = $this->getConfigByIdQuery->execute(id: $id, userId: $userId);

            if ($existing === null) {
                return ApiResponse::notFound(message: __('Subscription::exception.config_not_found'));
            }

            $this->deleteUseCase->execute(id: $id);

            return ApiResponse::noContent();
        } catch (Throwable $e) {
            return ApiResponse::error(exception: $e);
        }
    }

    /**
     * POST /api/web/v1/webhooks/{id}/activate
     * Ativa um webhook
     */
    public function activate(string $id): JsonResponse
    {
        try {
            // Verifica se o webhook pertence ao usuário
            $userId = auth(guard: 'api')->id();
            $existing = $this->getConfigByIdQuery->execute(id: $id, userId: $userId);

            if ($existing === null) {
                return ApiResponse::notFound(message: __('Subscription::exception.config_not_found'));
            }

            $item = $this->activateUseCase->execute(id: $id);

            return ApiResponse::success(
                data: $item->toArray(),
                message: __('Subscription::exception.config_activated_success'),
            );
        } catch (Throwable $e) {
            return ApiResponse::error(exception: $e);
        }
    }

    /**
     * POST /api/web/v1/webhooks/{id}/deactivate
     * Desativa um webhook
     */
    public function deactivate(string $id): JsonResponse
    {
        try {
            // Verifica se o webhook pertence ao usuário
            $userId = auth(guard: 'api')->id();
            $existing = $this->getConfigByIdQuery->execute(id: $id, userId: $userId);

            if ($existing === null) {
                return ApiResponse::notFound(message: __('Subscription::exception.config_not_found'));
            }

            $item = $this->deactivateUseCase->execute(id: $id);

            return ApiResponse::success(
                data: $item->toArray(),
                message: __('Subscription::exception.config_deactivated_success'),
            );
        } catch (Throwable $e) {
            return ApiResponse::error(exception: $e);
        }
    }

    /**
     * POST /api/web/v1/webhooks/{id}/test
     * Envia um payload de teste para o webhook
     *
     * Query params:
     * - async: boolean (opcional) - Se true, despacha para fila RabbitMQ
     */
    public function test(Request $request, string $id): JsonResponse
    {
        try {
            // Verifica se o webhook pertence ao usuário
            $userId = auth(guard: 'api')->id();
            $existing = $this->getConfigByIdQuery->execute(id: $id, userId: $userId);

            if ($existing === null) {
                return ApiResponse::notFound(message: __('Subscription::exception.config_not_found'));
            }

            // Verifica se deve executar assincronamente via RabbitMQ
            $async = $request->boolean(key: 'async', default: false);

            $result = $this->testUseCase->execute(id: $id, async: $async);

            $message = $async
                ? __('Subscription::exception.test_dispatched')
                : __('Subscription::exception.test_completed');

            return ApiResponse::success(
                data: $result,
                message: $message,
            );
        } catch (Throwable $e) {
            return ApiResponse::error(exception: $e);
        }
    }
}
