<?php

declare(strict_types=1);

namespace App\Enums;

enum PatientSex: string
{
    case Male = 'M';
    case Female = 'H';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Macho',
            self::Female => 'Hembra',
        };
    }
}
