<?php

namespace App\Enums;

enum Role: string
{
    case Root = 'root';
    case Admin = 'admin';
    case Member = 'member';

    /**
     * Roles that can be assigned when creating a user (only root can create users).
     */
    public static function assignableValues(): array
    {
        return [self::Admin->value, self::Member->value];
    }

    public function label(): string
    {
        return match ($this) {
            self::Root => 'Root',
            self::Admin => 'Administrador',
            self::Member => 'Miembro',
        };
    }
}
