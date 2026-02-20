<?php

declare(strict_types=1);

namespace Modules\User\Application\UseCases;

use Throwable;
use InvalidArgumentException;
use Modules\User\Application\DTOs\AuthTokenDTO;
use Modules\User\Application\DTOs\CreateUserDTO;
use Modules\Shared\Domain\Contracts\JwtServiceInterface;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
use Modules\Shared\Infrastructure\Logging\Concerns\Loggable;

final readonly class RegisterUseCase
{
    use Loggable;

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private CreateUserUseCase $createUserUseCase,
        private JwtServiceInterface $jwtService,
    ) {
    }

    /**
     * Registra um novo usuário e retorna o token JWT
     *
     * @throws InvalidArgumentException Se o email já estiver em uso
     */
    public function execute(CreateUserDTO $dto): AuthTokenDTO
    {
        $this->logger()->info('Registering new user', [
            'email' => $dto->email,
            'name' => $dto->name,
        ]);

        try {
            // Normaliza o email para verificação e uso
            $normalizedEmail = strtolower(trim($dto->email));

            $existingUser = $this->userRepository->findByEmail($normalizedEmail);
            if ($existingUser !== null) {
                throw new InvalidArgumentException('Email is already in use.');
            }

            $userDTO = $this->createUserUseCase->execute(
                name: $dto->name,
                email: $dto->email,
                password: $dto->password,
                surname: $dto->surname,
                profilePath: $dto->profilePath,
            );

            $token = $this->jwtService->attemptLogin([
                'email' => $normalizedEmail,
                'password' => $dto->password,
            ]);

            if ($token === null) {
                throw new \RuntimeException('Failed to generate token after registration.');
            }

            $this->logger()->event('UserRegistered', [
                'user_id' => $userDTO->id,
                'email' => $normalizedEmail,
            ]);

            return AuthTokenDTO::fromToken($token, $this->jwtService->getTokenTtl());
        } catch (InvalidArgumentException $e) {
            $this->logger()->warning('Registration failed: email already in use', [
                'email' => $dto->email,
            ]);

            throw $e;
        } catch (Throwable $e) {
            $this->logger()->error('Failed to register user', [
                'email' => $dto->email,
                'name' => $dto->name,
            ], $e);

            throw $e;
        }
    }
}
