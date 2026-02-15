<?php

declare(strict_types=1);

namespace Modules\Subscription\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

final class BillingHistoryModel extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'billing_histories';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'subscription_id',
        'amount_paid',
        'paid_at',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'deleted_at',
        'deleted_by',
    ];

    protected $casts = [
        'amount_paid' => 'integer',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
