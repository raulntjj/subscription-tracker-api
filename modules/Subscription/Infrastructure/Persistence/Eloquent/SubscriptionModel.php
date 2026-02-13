<?php

declare(strict_types=1);

namespace Modules\Subscription\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Infrastructure\Persistence\Concerns\HasUserActions;

final class SubscriptionModel extends Model
{
    use HasUuids;
    use HasUserActions;

    protected $table = 'subscriptions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'price',
        'currency',
        'billing_cycle',
        'next_billing_date',
        'category',
        'status',
        'user_id',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'deleted_at',
        'deleted_by',
    ];

    protected $casts = [
        'price' => 'integer',
        'next_billing_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'deleted_at',
        'deleted_by',
    ];

    public function uniqueIds(): array
    {
        return ['id'];
    }
}
