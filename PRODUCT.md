# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Several audiences, served equally; none outranks the others.

- **Bedside nurses / technicians** in a veterinary ICU: tablet in hand, long shifts, interrupted constantly, confirming doses on the ward. Use the kárdex board and the administration sheet.
- **Veterinarians**: prescribe, review progress, oversee controlled drugs.
- **Clinic owners / managers**: the buyers. Care about traceability, inventory and regulatory compliance. Reached first by the public landing page.
- **Receptionists / admins**: supporting roles (`UserRole`: admin, veterinarian, nurse, receptionist).

Market: small and mid-size veterinary clinics in LATAM, Colombia first. UI language is Latin American Spanish.

## Product Purpose

Multi-tenant veterinary SaaS whose core module is a high-precision hospitalization kárdex: scheduled treatments per patient and hour, bedside dose administration, fractional drug stock, and every ICU screen updated in real time. Success: no dose is given twice or missed, stock matches reality to 0.0001 mL, and every movement is auditable.

## Positioning

Four claims, all true in the code today, that a generic clinic-management tool could not copy:

- **Exact micro-doses**: `DECIMAL(10,4)` stock, automatic vial unsealing, write-off of expired opened units.
- **Real-time ICU**: all screens synchronized in under a second (Reverb), with polling as fallback.
- **Colombian compliance**: append-only ledger supporting the special-control drug book (Res. 1478/2006, FNE) and personal-data discipline (Ley 1581/2012).
- **"5 correctos" + audit**: blocking checklist before administering; notes mandatory for controlled drugs.

## Operating Context

Hospital wards with tablets at the bedside and wall displays in the ICU, often on degraded hospital Wi-Fi. Multiple nurses administer doses at the same time. Time pressure and clinical risk: errors have consequences for animals. Phone, tablet and wall-display form factors all matter.

## Capabilities and Constraints

- Stack: PHP 8.4+, Laravel 13, Livewire 4, Alpine.js, Tailwind CSS 4, Reverb. PostgreSQL in production; SQLite only in tests.
- Row-level multi-tenancy by `clinic_id`. Broadcast payloads carry no owner personal data.
- Quantities are never floats (`Quantity` wrapper).
- Terms: *kárdex*, *dosis*, *triage* (Crítico / Intermedio / Observación), *5 correctos*, *fraccional*, *vial*.
- Public site: landing (`/`) and login (`/login`). Clinical app under `/hospital/kardex`.
- Built: owner mobile app (Flutter, `mobile/`) with account, pet linking by clinic code/QR, owner-facing status, partes médicos and administered medication; patients and admissions, prescriptions (per-kg or fixed dose, frequency, duration, suspension), drug catalog with stock receiving and the movement ledger.
- Not built yet: omission workflow, dose reversal, constant-rate infusions, waste/adjustment screens, user management, password reset.

## Brand Commitments

- Name: **PetPulse** (one word, capital P twice); slug `petpulse`.
- Logo: neon paw + ECG trace (`public/images/petpulse-logo.webp`, plus icon and OG assets). Binding identity; keep it.
- Typeface: **Outfit**, chosen by the user for all UI in Latin American Spanish. This supersedes the earlier "no web fonts" note in the README; font loading must stay light (self-hosted, subset, `font-display: swap`) because tablets run on weak networks.
- Copy in neutral Latin American Spanish.

## Evidence on Hand

- Real: the working kárdex, its 31 tests, the logo and brand assets in `public/images`.
- **Absent, must not be fabricated:** customers, testimonials, case studies, press, verified benchmarks, pricing, certifications. Existing landing stat tiles (0,0001 mL, < 1 s, 100 %) restate engineering properties; any new numeric claim needs the user's confirmation.

## Product Principles

1. **Safety over speed over style.** Anything that could cause a wrong or double dose gets friction; everything else gets out of the way.
2. **Legible at arm's length and in a hurry.** Status must read instantly on a tablet or wall display.
3. **The ledger is the truth.** Every number shown traces to an auditable movement.
4. **Resilient on bad networks.** Light assets, graceful degradation when sockets drop.
5. **Honest claims.** Only what the system demonstrably does.

## Accessibility & Inclusion

- WCAG AA contrast and visible, trapped focus in modals.
- Touch targets at least 48 px.
- Status never conveyed by color alone.
- Latin American Spanish with full diacritics.
