<?php

namespace App\Enums;

enum BioimpedanceStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente de confirmación',
            self::Confirmed => 'Confirmado',
        };
    }
}
