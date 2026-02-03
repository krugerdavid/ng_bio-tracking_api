<?php

namespace App\Models;

use App\Contracts\AuditableContract;
use App\Traits\Auditable;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bioimpedance extends Model implements AuditableContract
{
    use Auditable, HasFactory, HasUuid;

    protected $fillable = [
        'member_id',
        'date',
        'height',
        'weight',
        'imc',
        'body_fat_percentage',
        'muscle_mass_percentage',
        'kcal',
        'metabolic_age',
        'visceral_fat_percentage',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'height' => 'float',
        'weight' => 'float',
        'imc' => 'float',
        'body_fat_percentage' => 'float',
        'muscle_mass_percentage' => 'float',
        'kcal' => 'float',
        'metabolic_age' => 'float',
        'visceral_fat_percentage' => 'float',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
