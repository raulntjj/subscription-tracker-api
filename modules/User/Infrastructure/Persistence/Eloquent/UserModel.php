<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Persistence\Eloquent;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Modules\Shared\Infrastructure\Persistence\Concerns\HasUserActions;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

final class UserModel extends Authenticatable implements JWTSubject
{
    use HasUuids;
    use HasUserActions;

    protected $table = 'users';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'surname',
        'email',
        'password',
        'profile_path',
    ];

    protected $hidden = [
        'password',
        'deleted_at',
        'deleted_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function uniqueIds(): array
    {
        return ['id'];
    }

    /**
     * Identificador usado como "subject" do JWT.
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Claims customizados adicionados ao payload do JWT.
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'email' => $this->email,
            'name'  => $this->name,
        ];
    }
}
