<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Member extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'document_number',
        'email',
        'date_of_birth',
        'gender',
        'user_id',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

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
