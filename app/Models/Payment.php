<?php

namespace App\Models;

use App\Contracts\AuditableContract;
use App\Traits\Auditable;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model implements AuditableContract
{
    use Auditable, HasFactory, HasUuid;

    protected $fillable = [
        'member_id',
        'month',
        'amount',
        'payment_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'float',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
