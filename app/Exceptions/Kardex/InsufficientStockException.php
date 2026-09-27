<?php

declare(strict_types=1);

namespace App\Exceptions\Kardex;

use App\Models\Drug;

final class InsufficientStockException extends KardexException
{
    public function __construct(
        public readonly int $drugId,
        public readonly string $requested,
        public readonly string $available,
        string $drugName,
        string $unit,
    ) {
        parent::__construct(sprintf(
            'Stock insuficiente de %s: se requieren %s %s y hay %s %s disponibles (incluyendo empaques sellados).',
            $drugName, $requested, $unit, $available, $unit,
        ));
    }

    public static function for(Drug $drug, string $requested, string $available): self
    {
        return new self($drug->getKey(), $requested, $available, $drug->name, $drug->fraction_unit);
    }
}
