# PetPulse — Veterinary Hospitalization & Nursing Kárdex

A multi-tenant SaaS for veterinary clinics in LATAM (primary compliance target: Colombia).
Its core module is a **high-precision hospitalization kárdex**. It tracks fractional drug
stock (4-decimal micro-doses, automatic vial unsealing) and bedside administration by several
nurses at once, with every ICU screen updated in real time.

**Stack:** PHP 8.3+, Laravel 13, Livewire 4 (v3-compatible API), Alpine.js, Tailwind CSS 4, Laravel Reverb.

---

## Quick start

Requires PostgreSQL 14+ (the production engine; see *Decisions* below).

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
createdb petpulse                     # or set DB_* in .env to an existing server
php artisan migrate --seed
npm run build

php artisan reverb:start      # terminal 1: WebSocket server
php artisan serve             # terminal 2
```

The seeder prints demo logins. Open `http://localhost:8000/dev/login/3` (the nurse; this route
only exists when `APP_ENV=local`). Open the board in a second window as well. When you
administer a dose in one window, the other one updates within a second.

```bash
php artisan test              # 31 tests
vendor/bin/pint --test        # code style
```

---

## Module map

| Concern | Location |
|---|---|
| Migrations | `database/migrations/2026_01_01_*` (`clinics`, `patients`, `drugs`, `hospitalizations`, `kardex_schedules`, `drug_fractions_log`) |
| Tenancy | `app/Tenancy/TenantContext.php`, `app/Models/Concerns/BelongsToClinic.php`, `app/Models/Scopes/ClinicScope.php`, `app/Http/Middleware/IdentifyClinic.php` |
| Fixed-point math | `app/Support/Pharmacy/Quantity.php`, `app/Support/Pharmacy/DoseCalculator.php` |
| **Administration domain service** | `app/Services/Hospitalization/KardexAdministrationService.php` |
| Inventory ledger | `app/Services/Pharmacy/DrugInventoryLedger.php`, `app/Models/DrugFractionLog.php` (append-only) |
| Real-time event | `app/Events/KardexUpdated.php`, `routes/channels.php`, `resources/js/echo.js` |
| Lifecycle observer | `app/Observers/HospitalizationObserver.php` (a discharge cancels pending doses) |
| Livewire board | `app/Livewire/Hospital/KardexBoard.php`, `resources/views/livewire/hospital/kardex-board.blade.php` |
| HTTP API (scanners/mobile) | `POST /hospital/kardex/{schedule}/administer`: `AdministerDoseController` + `AdministerDoseRequest` |
| Authorization | `app/Policies/KardexSchedulePolicy.php`, `App\Enums\UserRole` |
| Domain exceptions | `app/Exceptions/Kardex/*` (rendered as HTTP 409 / Livewire toasts, not reported) |

---

## Key design decisions and trade-offs

### 1. Row-level multi-tenancy (`clinic_id`), not database-per-tenant
The target market is thousands of small clinics, so one shared schema keeps migrations,
backups, Reverb channels and cross-tenant analytics simple. Isolation is enforced at three layers:

1. **`ClinicScope`**: a global scope on every tenant-owned model.
2. **`BelongsToClinic::creating`**: fills in `clinic_id` automatically and throws
   `TenantMismatchException` on any attempt to write into another clinic.
3. **Middleware ordering**: `IdentifyClinic` runs *before* `SubstituteBindings`, so
   `/kardex/{schedule}` with a foreign id returns 404. It is also registered as Livewire
   persistent middleware, so every `/livewire/update` round-trip binds the tenant again.

`TenantContext` is a `scoped` singleton, which means Octane workers and queue jobs cannot
carry one tenant over into the next request or job. If you later move to schema-per-tenant,
you only replace the connection resolver. The domain code stays the same.

### 2. Dual-state stock with exact decimals
`drugs.stock_packages` (sealed, integer) + `drugs.open_fraction_balance` (`DECIMAL(10,4)`, in
`fraction_unit`). All arithmetic goes through `Quantity`, a wrapper around `brick/math`. It uses
ext-bcmath or ext-gmp when present and falls back to pure PHP otherwise, so it gives the same
result on every host. `Quantity::of()` uses `RoundingMode::Unnecessary`: input with more than
4 decimals is **rejected, never silently truncated**. `DoseCalculator` computes
`mg/kg × kg ÷ mg/mL` at scale 10 and rounds HALF_UP only once, at the end.

A test administers 800 × 0.0125 mL and checks that exactly 10.0000 mL was consumed.

### 3. Concurrency: pessimistic locks in a fixed global order
`administerDose()` runs in one `DB::transaction(..., attempts: 3)` and takes locks in this order:

```
hospitalizations (SHARED) → kardex_schedules (FOR UPDATE) → drugs (FOR UPDATE)
```

* The **schedule lock + status re-check under lock** makes a double tap idempotent: one
  administration, one stock deduction. The model passed in may be stale, so only its ids are trusted.
* The **drug lock** serializes every writer of the two stock columns.
* A **shared lock on the stay** stops a discharge from committing in the middle of an
  administration, while other nurses can still give doses to the same patient in parallel.
  The discharge observer locks in `hospitalization → schedules` order, which is a prefix of
  the same order. That makes lock-order-inversion deadlocks structurally impossible.
  Leftover deadlocks from gap locks are retried by Laravel, which only retries concurrency errors.

> **PostgreSQL is the production database.** Row locks (`FOR UPDATE`), exact `NUMERIC`
> arithmetic and per-clinic scalability (partial indexes, `jsonb`, an optional move to
> schema-per-tenant) all rely on it. The automated test suite runs on in-memory SQLite for
> speed; SQLite ignores `FOR UPDATE`, so idempotency and rollback are covered by tests but true
> parallel contention needs a PostgreSQL run (see *Next steps*).

### 4. Automatic unsealing, stability expiry and the ledger
When the open balance is below the dose, packages are opened in a loop. For example, a 15 mL
dose from 10 mL vials opens 2 vials. Each opening is its own `package_opened` ledger row.
Before that, an open unit past `stability_hours_after_opening` (e.g. reconstituted ceftriaxone,
24 h) is written off as `expired_waste` and never administered. Feasibility
(`sealed × volume + open ≥ dose`) is checked **before** any mutation, so a shortage throws
`InsufficientStockException` with the real numbers and rolls back.

`drug_fractions_log` is append-only: the model throws on update or delete. Each row stores
signed deltas **and** post-movement balances, so an audit is O(1) and drift can be detected.
This supports the special-control drug book required under Res. 1478/2006 (FNE).
Controlled drugs (`is_controlled`) require a note on every administration. This rule is
enforced in the service, the Form Request and the modal.

### 5. Real-time: broadcast after commit, payload as signal
`KardexUpdated` implements `ShouldBroadcastNow`, because a queue would add latency exactly
when the ICU is busiest. It also implements `ShouldDispatchAfterCommit`, so a rolled-back
administration never appears on a wall display. The payload carries **no owner personal
data** (Ley 1581/2012). The board treats it as an invalidation signal and re-queries through
the tenant scope. The private channel `clinic.{id}.icu` is authorized against the user's
own `clinic_id`.

The board also runs `wire:poll.60s.visible`. Time is not an event: doses must turn "due" or
"overdue" as the clock moves, and the poll also covers dropped WebSocket connections on
hospital Wi-Fi. The server computes all status colors (`cellFor()`), so screens with drifting
clocks never disagree.

### 6. Bedside UX
* Timeline grid with patients × 24 h. The patient column is sticky and sorted by triage
  (Critical → Intermediate → Observation). The grid scrolls to the current hour on load.
* Every tap target is at least 48 px. The confirmation is a bottom sheet on phones and a
  centered modal on tablets or walls. Focus is trapped (`x-trap`).
* **"5 correctos" checklist** (right patient, drug, dose, route, time). The confirm button
  stays disabled until all five are checked, and until a note is entered for controlled drugs.
* The modal warns before the action when it will **open N new vials** ("label the opening
  date/time"), and when the dose is overdue.
* Only a corner dot pulses on overdue doses, so the dose text stays fully legible.
* No web fonts, because tablets must render at once on degraded networks.

---

## Next steps (not in this iteration)
* Omission workflow (`omitted` + mandatory reason) and dose reversal as a compensating ledger entry.
* Prescription UI (a Form Request exists for administration only), with CRIs/infusions as a separate model.
* PostgreSQL CI job with a parallel-process contention test for `lockForUpdate`.
* Stock receiving/waste screens (the ledger already supports `package_received`, `waste`, `adjustment`).
* Authentication scaffolding (Breeze/Fortify) to replace the local-only `/dev/login` helper.
