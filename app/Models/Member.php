<?php

namespace App\Models;

use App\Contracts\AuditableContract;
use App\Traits\Auditable;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Member extends Model implements AuditableContract
{
    use Auditable, HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'document_number',
        'email',
        'date_of_birth',
        'gender',
        'user_id',
        'credit_balance',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'credit_balance' => 'float',
    ];

    /**
     * User account linked to this member (when member has login).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function membershipPlan(): HasOne
    {
        return $this->hasOne(MembershipPlan::class);
    }

    public function bioimpedances(): HasMany
    {
        return $this->hasMany(Bioimpedance::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
