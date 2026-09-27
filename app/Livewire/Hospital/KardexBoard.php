<?php

declare(strict_types=1);

namespace App\Livewire\Hospital;

use App\Enums\KardexStatus;
use App\Enums\TriageLevel;
use App\Exceptions\Kardex\KardexException;
use App\Models\Clinic;
use App\Models\Hospitalization;
use App\Models\KardexSchedule;
use App\Services\Hospitalization\KardexAdministrationService;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

/**
 * Real-time nursing board (kárdex) for ICU tablets and wall displays.
 *
 * Refresh strategy:
 *  - Data changes arrive over Reverb (KardexUpdated) and simply invalidate the
 *    computed board; the component re-queries through the tenant scope. The
 *    websocket payload is treated as a signal, never as a source of truth.
 *  - A slow `wire:poll.60s` in the view only exists to move pending doses into
 *    "due soon"/"overdue" as the clock advances (time is not an event), and as
 *    a safety net if the websocket drops on hospital Wi-Fi.
 */
#[Layout('layouts.app')]
#[Title('Kárdex de hospitalización')]
final class KardexBoard extends Component
{
    /** Tenant id; locked so it cannot be tampered with from the browser. */
    #[Locked]
    public int $clinicId;

    #[Locked]
    public string $timezone = 'America/Bogota';

    /** Optional single-patient focus (bedside tablet mode). */
    #[Url(as: 'paciente')]
    public ?int $hospitalizationId = null;

    /** Board day in the clinic's timezone, Y-m-d. */
    #[Url(as: 'fecha')]
    public string $date = '';

    #[Url(as: 'triage')]
    public string $triage = '';

    /** ISO timestamp of the last websocket signal (shown as a live indicator). */
    public ?string $lastSyncAt = null;

    public function mount(TenantContext $tenant): void
    {
        $this->clinicId = $tenant->idOrFail();
        $this->timezone = Clinic::query()->whereKey($this->clinicId)->value('timezone') ?? $this->timezone;
        $this->date = $this->normalizeDate($this->date);

        $this->authorize('viewAny', KardexSchedule::class);
    }

    public function updatedDate(): void
    {
        $this->date = $this->normalizeDate($this->date);
    }

    public function updatedTriage(): void
    {
        if (TriageLevel::tryFrom($this->triage) === null) {
            $this->triage = '';
        }
    }

    public function shiftDay(int $days): void
    {
        $this->date = Carbon::parse($this->date, $this->timezone)->addDays($days)->toDateString();
    }

    public function today(): void
    {
        $this->date = Carbon::now($this->timezone)->toDateString();
    }

    public function clearFilters(): void
    {
        $this->reset('hospitalizationId', 'triage');
    }

    /**
     * `{clinicId}` is interpolated by Livewire from the public property above,
     * producing Echo's `private('clinic.7.icu').listen('KardexUpdated')`.
     *
     * @param  array<string, mixed>  $payload
     */
    #[On('echo-private:clinic.{clinicId}.icu,KardexUpdated')]
    public function onKardexUpdated(array $payload = []): void
    {
        unset($this->board, $this->summary);

        $this->lastSyncAt = Carbon::now($this->timezone)->toIso8601String();

        $drug = $payload['drug'] ?? null;

        if (is_array($drug) && ($drug['low_stock'] ?? false) === true) {
            $this->toast('warning', sprintf('Stock bajo: %s (%d empaques sellados).', $drug['name'] ?? '', (int) ($drug['stock_packages'] ?? 0)));
        }
    }

    /**
     * Quick action from the confirmation modal.
     */
    public function markAsAdministered(int $scheduleId, ?string $notes = null): void
    {
        $notes = $notes !== null ? mb_substr($notes, 0, 1000) : null;

        // Re-resolve through the tenant scope: an id from another clinic 404s here.
        $schedule = KardexSchedule::query()->with('drug:id,name')->findOrFail($scheduleId);

        $this->authorize('administer', $schedule);

        try {
            app(KardexAdministrationService::class)->administerDose($schedule, (int) auth()->id(), $notes);
        } catch (KardexException|AuthorizationException $e) {
            $this->toast('error', $e->getMessage());

            unset($this->board, $this->summary);

            return;
        } catch (Throwable $e) {
            report($e);
            $this->toast('error', 'No se pudo registrar la dosis. Intente de nuevo; no se descontó inventario.');

            return;
        }

        unset($this->board, $this->summary);

        $this->toast('success', sprintf('%s administrado a las %s.',
            $schedule->drug?->name ?? 'Medicamento',
            $schedule->administered_at?->setTimezone($this->timezone)->format('H:i') ?? '--:--',
        ));
    }

    /**
     * Rows of the board: one per hospitalized patient, most critical first.
     *
     * @return EloquentCollection<int, Hospitalization>
     */
    #[Computed]
    public function board(): EloquentCollection
    {
        [$from, $to] = $this->dayBoundsUtc();

        return Hospitalization::query()
            ->with([
                'patient:id,name,species,breed',
                'kardexSchedules' => fn (HasMany $q) => $q
                    ->whereBetween('scheduled_at', [$from, $to])
                    ->with([
                        'drug:id,name,fraction_unit,presentation_unit,volume_per_presentation,open_fraction_balance,stock_packages,is_controlled',
                        'administeredBy:id,name',
                    ])
                    ->orderBy('scheduled_at'),
            ])
            ->where(fn (Builder $q) => $q
                ->active()
                // Keep patients discharged during the viewed day visible for traceability.
                ->orWhereHas('kardexSchedules', fn (Builder $s) => $s->whereBetween('scheduled_at', [$from, $to])))
            ->when($this->hospitalizationId, fn (Builder $q, int $id) => $q->whereKey($id))
            ->when(TriageLevel::tryFrom($this->triage), fn (Builder $q, TriageLevel $t) => $q->where('triage_level', $t))
            ->get()
            ->sortBy(fn (Hospitalization $h): string => $h->triage_level->priority().'|'.str_pad((string) $h->bed_label, 8, '0', STR_PAD_LEFT))
            ->values();
    }

    /** @return array{pending: int, due: int, overdue: int, administered: int, total: int} */
    #[Computed]
    public function summary(): array
    {
        $now = Carbon::now();
        $schedules = $this->board->flatMap->kardexSchedules;

        return [
            'total' => $schedules->count(),
            'administered' => $schedules->where('status', KardexStatus::Administered)->count(),
            'pending' => $schedules->where('status', KardexStatus::Pending)->count(),
            'due' => $schedules->filter(fn (KardexSchedule $s) => $s->isDueSoon($now))->count(),
            'overdue' => $schedules->filter(fn (KardexSchedule $s) => $s->isOverdue($now))->count(),
        ];
    }

    /**
     * Dropdown options for the single-patient filter.
     *
     * @return Collection<int, string>
     */
    #[Computed]
    public function patientOptions(): Collection
    {
        return Hospitalization::query()
            ->active()
            ->with('patient:id,name')
            ->orderBy('bed_label')
            ->get(['id', 'patient_id', 'bed_label'])
            ->mapWithKeys(fn (Hospitalization $h) => [$h->id => trim(($h->bed_label ? $h->bed_label.' · ' : '').$h->patient->name)]);
    }

    /**
     * Per-cell presentation state, computed server-side so every display shows
     * identical colours regardless of the device clock.
     *
     * @return array<string, mixed>
     */
    public function cellFor(KardexSchedule $schedule, Hospitalization $stay): array
    {
        $drug = $schedule->drug;
        $dose = $schedule->dose();
        $openBalance = $drug->openBalance();

        $state = match (true) {
            $schedule->status === KardexStatus::Administered => 'administered',
            $schedule->status === KardexStatus::Omitted => 'omitted',
            $schedule->status === KardexStatus::Cancelled => 'cancelled',
            $schedule->isOverdue() => 'overdue',
            $schedule->isDueSoon() => 'due',
            default => 'pending',
        };

        $packagesToOpen = $openBalance->isLessThan($dose)
            ? $dose->minus($openBalance)->packagesNeeded($drug->packageVolume())
            : 0;

        return [
            'id' => $schedule->id,
            'state' => $state,
            'actionable' => $schedule->status === KardexStatus::Pending && $stay->isActive(),
            'time' => $schedule->scheduled_at->setTimezone($this->timezone)->format('H:i'),
            'administeredAt' => $schedule->administered_at?->setTimezone($this->timezone)->format('H:i'),
            'administeredBy' => $schedule->administeredBy?->name,
            'drug' => $drug->name,
            'dose' => $dose->format(),
            'unit' => $drug->fraction_unit,
            'route' => $schedule->route->value,
            'routeLabel' => $schedule->route->label(),
            'injectable' => $schedule->route->isInjectable(),
            'controlled' => $drug->is_controlled,
            'packagesToOpen' => $packagesToOpen,
            'presentation' => $drug->presentation_unit,
            'stockPackages' => $drug->stock_packages,
            'openBalance' => $openBalance->format(),
            'patient' => $stay->patient->name,
            'species' => $stay->patient->species,
            'bed' => $stay->bed_label,
            'weight' => rtrim(rtrim((string) $stay->current_weight_kg, '0'), '.'),
            'notes' => $schedule->notes,
        ];
    }

    public function render(): View
    {
        return view('livewire.hospital.kardex-board', [
            'hours' => range(0, 23),
            'currentHour' => $this->isToday() ? (int) Carbon::now($this->timezone)->format('G') : null,
            'triageLevels' => TriageLevel::cases(),
        ]);
    }

    public function isToday(): bool
    {
        return $this->date === Carbon::now($this->timezone)->toDateString();
    }

    /** @return array{0: Carbon, 1: Carbon} UTC bounds of the selected clinic-local day. */
    private function dayBoundsUtc(): array
    {
        $day = Carbon::parse($this->date, $this->timezone);

        return [
            $day->copy()->startOfDay()->utc(),
            $day->copy()->endOfDay()->utc(),
        ];
    }

    private function normalizeDate(?string $date): string
    {
        try {
            return Carbon::createFromFormat('Y-m-d', (string) $date, $this->timezone)->toDateString();
        } catch (Throwable) {
            return Carbon::now($this->timezone)->toDateString();
        }
    }

    private function toast(string $type, string $message): void
    {
        $this->dispatch('kardex-toast', type: $type, message: $message);
    }
}
