<?php

declare(strict_types=1);

namespace Modules\User\Domain\Entities;

use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;
use Modules\User\Domain\ValueObjects\Email;
use Modules\User\Domain\ValueObjects\Password;

final class User
{
    private UuidInterface $id;
    private string $name;
    private ?string $surname;
    private Email $email;
    private Password $password;
    private ?string $profilePath;
    private DateTimeImmutable $createdAt;

    public function __construct(
        UuidInterface $id,
        string $name,
        Email $email,
        Password $password,
        DateTimeImmutable $createdAt,
        ?string $surname = null,
        ?string $profilePath = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->surname = $surname;
        $this->email = $email;
        $this->password = $password;
        $this->profilePath = $profilePath;
        $this->createdAt = $createdAt;
    }

    public function id(): UuidInterface
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function surname(): ?string
    {
        return $this->surname;
    }

    public function fullName(): string
    {
        return trim($this->name . ' ' . ($this->surname ?? ''));
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function password(): Password
    {
        return $this->password;
    }

    public function profilePath(): ?string
    {
        return $this->profilePath;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function changeName(string $newName): void
    {
        $this->name = $newName;
    }

    public function changeSurname(?string $newSurname): void
    {
        $this->surname = $newSurname;
    }

    public function changeEmail(Email $newEmail): void
    {
        $this->email = $newEmail;
    }

    public function changePassword(Password $newPassword): void
    {
        $this->password = $newPassword;
    }

    public function changeProfilePath(?string $newProfilePath): void
    {
        $this->profilePath = $newProfilePath;
    }
}
