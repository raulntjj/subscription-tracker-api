<?php

declare(strict_types=1);

namespace Modules\Subscription\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Shared\Infrastructure\Persistence\Concerns\HasUserActions;
use Modules\User\Infrastructure\Persistence\Eloquent\UserModel;

final class WebhookConfigModel extends Model
{
    use HasUuids;
    use HasUserActions;

    protected $table = 'webhook_configs';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'url',
        'secret',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relacionamento com User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
