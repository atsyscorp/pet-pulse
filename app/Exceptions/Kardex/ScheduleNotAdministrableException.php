<?php

declare(strict_types=1);

namespace App\Exceptions\Kardex;

use App\Models\KardexSchedule;

final class ScheduleNotAdministrableException extends KardexException
{
    public static function alreadyFinal(KardexSchedule $schedule): self
    {
        return new self(sprintf(
            'La dosis #%d ya fue registrada como "%s"%s. Recargue el tablero.',
            $schedule->getKey(),
            $schedule->status->value,
            $schedule->administered_at !== null ? ' a las '.$schedule->administered_at->format('H:i') : '',
        ));
    }

    public static function hospitalizationClosed(KardexSchedule $schedule): self
    {
        return new self(sprintf(
            'La hospitalización #%d ya no está activa; no se pueden administrar dosis.',
            $schedule->hospitalization_id,
        ));
    }

    public static function drugInactive(KardexSchedule $schedule): self
    {
        return new self(sprintf('El medicamento de la dosis #%d está inactivo en el catálogo.', $schedule->getKey()));
    }

    public static function controlledSubstanceRequiresNotes(): self
    {
        return new self('Medicamento de control especial: debe registrar una observación (lote, testigo o justificación).');
    }
}
