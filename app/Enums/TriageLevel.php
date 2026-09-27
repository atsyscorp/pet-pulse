<?php

declare(strict_types=1);

namespace App\Enums;

enum TriageLevel: string
{
    case Critical = 'critical';
    case Intermediate = 'intermediate';
    case Observation = 'observation';

    public function label(): string
    {
        return match ($this) {
            self::Critical => 'Crítico / UCI',
            self::Intermediate => 'Intermedio',
            self::Observation => 'Observación',
        };
    }

    /** Lower sorts first on the board: critical patients always on top. */
    public function priority(): int
    {
        return match ($this) {
            self::Critical => 0,
            self::Intermediate => 1,
            self::Observation => 2,
        };
    }

    /** Tailwind classes kept as literal strings so the JIT scanner picks them up. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Critical => 'bg-red-600 text-white ring-red-700',
            self::Intermediate => 'bg-amber-400 text-amber-950 ring-amber-500',
            self::Observation => 'bg-sky-500 text-white ring-sky-600',
        };
    }

    public function railClasses(): string
    {
        return match ($this) {
            self::Critical => 'border-l-red-600',
            self::Intermediate => 'border-l-amber-400',
            self::Observation => 'border-l-sky-500',
        };
    }
}
