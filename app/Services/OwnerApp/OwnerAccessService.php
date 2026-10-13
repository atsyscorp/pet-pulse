<?php

declare(strict_types=1);

namespace App\Services\OwnerApp;

use App\Exceptions\OwnerApp\InvalidLinkCodeException;
use App\Models\Owner;
use App\Models\OwnerPatientLink;
use App\Models\Patient;
use App\Models\PatientLinkCode;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;

/**
 * The clinic grants an owner access to one pet with a single-use code; the
 * owner redeems it from the app; the clinic can revoke it at any time.
 */
final readonly class OwnerAccessService
{
    public const int CODE_LIFETIME_DAYS = 7;

    public function __construct(private ConnectionInterface $db) {}

    /**
     * Issues a fresh code for the patient and voids any earlier unused one,
     * so only the code most recently handed over works.
     *
     * @return array{code: string, expires_at: Carbon}
     */
    public function issueCode(Patient $patient, int $userId): array
    {
        return $this->db->transaction(function () use ($patient, $userId): array {
            PatientLinkCode::query()
                ->where('patient_id', $patient->getKey())
                ->whereNull('used_at')
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            // Collisions are astronomically unlikely, but the unique index makes them impossible.
            do {
                $code = LinkCode::generate();
                $hash = LinkCode::hash($code);
            } while (PatientLinkCode::query()->withoutGlobalScopes()->where('code_hash', $hash)->exists());

            $expiresAt = now()->addDays(self::CODE_LIFETIME_DAYS);

            PatientLinkCode::query()->create([
                'clinic_id' => $patient->clinic_id,
                'patient_id' => $patient->getKey(),
                'created_by' => $userId,
                'code_hash' => $hash,
                'expires_at' => $expiresAt,
            ]);

            return ['code' => $code, 'expires_at' => $expiresAt];
        });
    }

    /** @throws InvalidLinkCodeException */
    public function redeem(Owner $owner, string $input): Patient
    {
        $normalized = LinkCode::normalize($input);

        if (strlen($normalized) !== LinkCode::LENGTH) {
            throw new InvalidLinkCodeException;
        }

        return $this->db->transaction(function () use ($owner, $normalized): Patient {
            // API requests carry no tenant, so read across clinics explicitly.
            $code = PatientLinkCode::query()->withoutGlobalScopes()
                ->where('code_hash', LinkCode::hash($normalized))
                ->lockForUpdate()
                ->first();

            if ($code === null || ! $code->isUsable()) {
                throw new InvalidLinkCodeException;
            }

            $patient = Patient::query()->withoutGlobalScopes()->whereKey($code->patient_id)->whereNull('deleted_at')->first()
                ?? throw new InvalidLinkCodeException;

            $code->forceFill(['used_at' => now(), 'used_by_owner_id' => $owner->getKey()])->save();

            // Re-linking after a revocation restores the same row (keeps one row per pair).
            $link = OwnerPatientLink::query()->withoutGlobalScopes()->firstOrNew([
                'owner_id' => $owner->getKey(),
                'patient_id' => $patient->getKey(),
            ]);
            $link->forceFill([
                'clinic_id' => $patient->clinic_id,
                'linked_at' => now(),
                'revoked_at' => null,
                'revoked_by' => null,
            ])->save();

            return $patient;
        });
    }

    public function revoke(OwnerPatientLink $link, int $userId): void
    {
        $link->forceFill(['revoked_at' => now(), 'revoked_by' => $userId])->save();

        // Their app session keeps working for other pets; this one disappears on next refresh.
    }
}
