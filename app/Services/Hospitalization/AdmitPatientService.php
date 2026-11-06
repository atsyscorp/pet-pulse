<?php

declare(strict_types=1);

namespace App\Services\Hospitalization;

use App\Enums\HospitalizationStatus;
use App\Enums\TriageLevel;
use App\Exceptions\Hospitalization\PatientAlreadyAdmittedException;
use App\Models\Hospitalization;
use App\Models\Patient;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;

/**
 * Opens a hospitalization for a patient.
 *
 * A patient can occupy one bed at a time. The check runs under a lock on the
 * patient row so two receptionists admitting the same animal at once cannot
 * both succeed.
 */
final readonly class AdmitPatientService
{
    public function __construct(private ConnectionInterface $db) {}

    /**
     * @param  array{
     *     current_weight_kg: string,
     *     triage_level: TriageLevel,
     *     bed_label: ?string,
     *     attending_vet_id: ?int,
     *     admission_reason: string,
     *     diagnosis: ?string,
     *     admitted_at: Carbon,
     * }  $data
     *
     * @throws PatientAlreadyAdmittedException
     */
    public function admit(Patient $patient, array $data): Hospitalization
    {
        return $this->db->transaction(function () use ($patient, $data): Hospitalization {
            $locked = Patient::query()->whereKey($patient->getKey())->lockForUpdate()->firstOrFail();

            $alreadyAdmitted = $locked->hospitalizations()
                ->where('status', HospitalizationStatus::Admitted)
                ->exists();

            if ($alreadyAdmitted) {
                throw PatientAlreadyAdmittedException::for($locked);
            }

            return $locked->hospitalizations()->create([
                'clinic_id' => $locked->clinic_id,
                'attending_vet_id' => $data['attending_vet_id'],
                'bed_label' => $data['bed_label'],
                'triage_level' => $data['triage_level'],
                'current_weight_kg' => $data['current_weight_kg'],
                'weight_recorded_at' => $data['admitted_at'],
                'admission_reason' => $data['admission_reason'],
                'diagnosis' => $data['diagnosis'],
                'admitted_at' => $data['admitted_at'],
                'status' => HospitalizationStatus::Admitted,
            ]);
        });
    }
}
