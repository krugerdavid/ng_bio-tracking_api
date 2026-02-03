<?php

namespace App\Traits;

use App\Contracts\AuditableContract;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Use this trait on models that should be audited.
 *
 * Add the model to config/audit.php 'models' and optionally override
 * excludedAuditAttributes() for model-specific exclusions.
 */
trait Auditable
{
    public function excludedAuditAttributes(): array
    {
        return [];
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable')->latest();
    }
}
