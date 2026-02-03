<?php

namespace App\Models;

use App\Contracts\AuditableContract;
use App\Traits\Auditable;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipPlan extends Model implements AuditableContract
{
    use Auditable, HasFactory, HasUuid;

    protected $fillable = [
        'member_id',
        'monthly_fee',
        'weekly_frequency',
        'start_date',
        'is_active',
    ];

    protected $casts = [
        'monthly_fee' => 'float',
        'weekly_frequency' => 'integer',
        'start_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
