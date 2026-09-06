<?php

namespace App\Enums;

/**
 * The four fixed roles in the system. Role records are created by the
 * RoleSeeder and are referenced by their string name everywhere in code.
 */
enum RoleName: string
{
    case Admin = 'admin';
    case L1 = 'l1';
    case L2 = 'l2';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::L1 => 'L1 Support',
            self::L2 => 'L2 Support',
            self::Viewer => 'Viewer',
        };
    }

    /** @return array<string, string> value => label */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->all();
    }
}
