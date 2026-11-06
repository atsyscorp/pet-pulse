<?php

declare(strict_types=1);

namespace App\Services\Hospitalization;

use App\Enums\AdministrationRoute;
use App\Enums\DoseMode;
use App\Models\Drug;
use App\Support\Pharmacy\DoseCalculator;
use App\Support\Pharmacy\Quantity;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * The fully-resolved shape of a prescription before it is saved: the dose in
 * the drug's fraction unit and every scheduled time.
 *
 * Pure (no database writes), so the prescription form uses the exact same
 * object for its live preview that the service persists: what the vet sees is
 * what lands on the kárdex.
 */
final readonly class PrescriptionPlan
{
    /** Hard cap on generated rows: 30 days of hourly doses. */
    public const int MAX_DOSES = 720;

    /**
     * @param  list<Carbon>  $times  UTC
     */
    private function __construct(
        public Drug $drug,
        public DoseMode $mode,
        public ?string $dosePerKg,
        public ?string $weightKg,
        public Quantity $dose,
        public AdministrationRoute $route,
        public ?int $frequencyHours,
        public Carbon $startsAt,
        public Carbon $endsAt,
        public array $times,
    ) {}

    /**
     * @param  ?int  $frequencyHours  null = single dose
     * @param  ?int  $durationHours  ignored for a single dose
     *
     * @throws InvalidArgumentException on impossible input (callers validate first)
     */
    public static function make(
        Drug $drug,
        DoseMode $mode,
        ?string $dosePerKg,
        ?string $fixedAmount,
        ?string $weightKg,
        AdministrationRoute $route,
        ?int $frequencyHours,
        Carbon $startsAt,
        ?int $durationHours,
    ): self {
        $dose = match ($mode) {
            DoseMode::PerKg => self::perKgDose($drug, $dosePerKg, $weightKg),
            DoseMode::Fixed => Quantity::of((string) $fixedAmount),
        };

        if (! $dose->isPositive()) {
            throw new InvalidArgumentException('The computed dose rounds to zero.');
        }

        $startsAt = $startsAt->copy()->utc()->startOfMinute();

        if ($frequencyHours === null) {
            $times = [$startsAt];
            $endsAt = $startsAt->copy()->addMinute();
        } else {
            if ($frequencyHours < 1 || $durationHours === null || $durationHours < 1) {
                throw new InvalidArgumentException('Frequency and duration must be positive.');
            }

            $endsAt = $startsAt->copy()->addHours($durationHours);
            $times = [];
            for ($t = $startsAt->copy(); $t->lessThan($endsAt); $t = $t->copy()->addHours($frequencyHours)) {
                $times[] = $t;

                if (count($times) > self::MAX_DOSES) {
                    throw new InvalidArgumentException('Too many doses.');
                }
            }
        }

        return new self(
            drug: $drug,
            mode: $mode,
            dosePerKg: $mode === DoseMode::PerKg ? $dosePerKg : null,
            weightKg: $mode === DoseMode::PerKg ? $weightKg : null,
            dose: $dose,
            route: $route,
            frequencyHours: $frequencyHours,
            startsAt: $startsAt,
            endsAt: $endsAt,
            times: $times,
        );
    }

    public function firstTime(): Carbon
    {
        return $this->times[0];
    }

    public function lastTime(): Carbon
    {
        return $this->times[array_key_last($this->times)];
    }

    public function doseCount(): int
    {
        return count($this->times);
    }

    /** Total drug needed for the whole plan, in fraction_unit. */
    public function totalRequired(): Quantity
    {
        return $this->dose->multipliedBy($this->doseCount());
    }

    /** True when sealed + open stock covers the whole plan right now. */
    public function stockCovers(): bool
    {
        return ! $this->drug->totalAvailable()->isLessThan($this->totalRequired());
    }

    private static function perKgDose(Drug $drug, ?string $dosePerKg, ?string $weightKg): Quantity
    {
        if ($drug->concentration_amount === null || $dosePerKg === null || $weightKg === null) {
            throw new InvalidArgumentException('A per-kg dose needs the drug concentration, the dose and the weight.');
        }

        return (new DoseCalculator)->amountFor($dosePerKg, $weightKg, (string) $drug->concentration_amount);
    }
}
