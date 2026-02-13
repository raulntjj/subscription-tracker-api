<?php

declare(strict_types=1);

namespace Modules\Subscription\Interface\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Subscription\Application\DTOs\CreateWebhookConfigDTO;
use Modules\Subscription\Application\DTOs\UpdateWebhookConfigDTO;
use Modules\Subscription\Application\DTOs\WebhookConfigDTO;
use Modules\Subscription\Application\UseCases\CreateWebhookConfigUseCase;
use Modules\Subscription\Application\UseCases\UpdateWebhookConfigUseCase;
use Modules\Subscription\Application\UseCases\DeleteWebhookConfigUseCase;
use Modules\Subscription\Application\UseCases\ActivateWebhookUseCase;
use Modules\Subscription\Application\UseCases\DeactivateWebhookUseCase;
use Modules\Subscription\Application\UseCases\TestWebhookUseCase;
use Modules\Subscription\Application\Queries\GetWebhookConfigsQuery;
use Modules\Subscription\Application\Queries\GetWebhookConfigByIdQuery;
use Modules\Shared\Interface\Http\Responses\ApiResponse;

final class WebhookConfigController extends Controller
{
    public function __construct(
        private readonly CreateWebhookConfigUseCase $createUseCase,
        private readonly UpdateWebhookConfigUseCase $updateUseCase,
        private readonly DeleteWebhookConfigUseCase $deleteUseCase,
        private readonly ActivateWebhookUseCase $activateUseCase,
        private readonly DeactivateWebhookUseCase $deactivateUseCase,
        private readonly TestWebhookUseCase $testUseCase,
        private readonly GetWebhookConfigsQuery $getConfigsQuery,
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
            $userId = Auth::id();
            $items = $this->getConfigsQuery->execute($userId);

            $data = array_map(
                fn(WebhookConfigDTO $item) => $item->toArray(),
                $items
            );

            return ApiResponse::success([
                'webhooks' => $data,
                'total' => count($data),
            ], 'Webhook configs retrieved successfully');
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * GET /api/web/v1/webhooks/{id}
     * Retorna uma configuração de webhook específica
     */
    public function show(string $id): JsonResponse
    {
        try {
            $userId = Auth::id();
            $item = $this->getConfigByIdQuery->execute($id, $userId);

            if ($item === null) {
                return ApiResponse::notFound('Webhook config not found');
            }

            return ApiResponse::success(
                $item->toArray(),
                'Webhook config retrieved successfully'
            );
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * POST /api/web/v1/webhooks
     * Cria uma nova configuração de webhook
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'url' => ['required', 'string', 'url', 'max:500'],
                'secret' => ['nullable', 'string', 'min:8', 'max:255'],
            ]);

            $validated['user_id'] = auth('api')->id();
            $dto = CreateWebhookConfigDTO::fromArray($validated);

            $item = $this->createUseCase->execute($dto);

            return ApiResponse::created(
                $item->toArray(),
                'Webhook config created successfully'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        } catch (Throwable $e) {
            dd($e);
            return ApiResponse::error($e->getMessage());
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
            $userId = Auth::id();
            $existing = $this->getConfigByIdQuery->execute($id, $userId);

            if ($existing === null) {
                return ApiResponse::notFound('Webhook config not found');
            }

            $validated = $request->validate([
                'url' => ['sometimes', 'string', 'url', 'max:500'],
                'secret' => ['sometimes', 'string', 'min:8', 'max:255'],
            ]);

            $dto = UpdateWebhookConfigDTO::fromArray([
                'id' => $id,
                'url' => $validated['url'] ?? null,
                'secret' => $validated['secret'] ?? null,
            ]);

            $item = $this->updateUseCase->execute($dto);

            return ApiResponse::success(
                $item->toArray(),
                'Webhook config updated successfully'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::validationError($e->errors());
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * DELETE /api/web/v1/webhooks/{id}
     * Remove uma configuração de webhook
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            // Verifica se o webhook pertence ao usuário
            $userId = Auth::id();
            $existing = $this->getConfigByIdQuery->execute($id, $userId);

            if ($existing === null) {
                return ApiResponse::notFound('Webhook config not found');
            }

            $this->deleteUseCase->execute($id);

            return ApiResponse::success(
                null,
                'Webhook config deleted successfully'
            );
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
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
            $userId = Auth::id();
            $existing = $this->getConfigByIdQuery->execute($id, $userId);

            if ($existing === null) {
                return ApiResponse::notFound('Webhook config not found');
            }

            $item = $this->activateUseCase->execute($id);

            return ApiResponse::success(
                $item->toArray(),
                'Webhook config activated successfully'
            );
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
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
            $userId = Auth::id();
            $existing = $this->getConfigByIdQuery->execute($id, $userId);

            if ($existing === null) {
                return ApiResponse::notFound('Webhook config not found');
            }

            $item = $this->deactivateUseCase->execute($id);

            return ApiResponse::success(
                $item->toArray(),
                'Webhook config deactivated successfully'
            );
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    /**
     * POST /api/web/v1/webhooks/{id}/test
     * Envia um payload de teste para o webhook
     */
    public function test(string $id): JsonResponse
    {
        try {
            // Verifica se o webhook pertence ao usuário
            $userId = Auth::id();
            $existing = $this->getConfigByIdQuery->execute($id, $userId);

            if ($existing === null) {
                return ApiResponse::notFound('Webhook config not found');
            }

            $result = $this->testUseCase->execute($id);

            return ApiResponse::success(
                $result,
                'Webhook test completed'
            );
        } catch (Throwable $e) {
            return ApiResponse::error($e->getMessage());
        }
    }
}
