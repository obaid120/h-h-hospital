<?php

namespace App\Enums;

enum StaffRole: string
{
    case Admin = 'admin';
    case Receptionist = 'receptionist';
    case Doctor = 'doctor';

    public function label(): string
    {
        return __('staff.roles.'.$this->value);
    }

    public function homeRoute(): string
    {
        return 'staff.'.$this->value.'.home';
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $role) {
            $options[$role->value] = $role->label();
        }

        return $options;
    }
}
