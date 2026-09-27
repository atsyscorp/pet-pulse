<?php

declare(strict_types=1);

namespace App\Enums;

enum AdministrationRoute: string
{
    case Intravenous = 'IV';
    case Intramuscular = 'IM';
    case Subcutaneous = 'SC';
    case Oral = 'PO';
    case Intraosseous = 'IO';
    case Topical = 'TOP';
    case Inhalation = 'INH';
    case Rectal = 'REC';
    case Ophthalmic = 'OPH';
    case Otic = 'OT';

    public function label(): string
    {
        return match ($this) {
            self::Intravenous => 'Intravenosa',
            self::Intramuscular => 'Intramuscular',
            self::Subcutaneous => 'Subcutánea',
            self::Oral => 'Oral',
            self::Intraosseous => 'Intraósea',
            self::Topical => 'Tópica',
            self::Inhalation => 'Inhalada',
            self::Rectal => 'Rectal',
            self::Ophthalmic => 'Oftálmica',
            self::Otic => 'Ótica',
        };
    }

    /** Parenteral routes get an extra "injection" visual cue on the board. */
    public function isInjectable(): bool
    {
        return in_array($this, [self::Intravenous, self::Intramuscular, self::Subcutaneous, self::Intraosseous], true);
    }
}
