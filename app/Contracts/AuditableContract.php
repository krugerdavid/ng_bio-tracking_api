<?php

namespace App\Contracts;

/**
 * Contract for models that should be audited.
 *
 * Implement this interface and add the model to config/audit.php 'models'
 * to have create/update/delete automatically logged.
 */
interface AuditableContract
{
    /**
     * Attribute names to exclude from audit old_values/new_values.
     * Overrides config when present.
     *
     * @return array<int, string>
     */
    public function excludedAuditAttributes(): array;
}
