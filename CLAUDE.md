# PetPulse

- The product name is **PetPulse** (one word, capital P twice). Use it in UI copy, docs, config
  defaults and commit messages. Lowercase slug form: `petpulse`.
- Multi-tenant veterinary SaaS for LATAM (Colombia first). Laravel 13, Livewire 4, Alpine,
  Tailwind 4, Reverb. See README.md for architecture and trade-offs.
- Database: PostgreSQL in every environment except the automated tests (in-memory SQLite).
- Quantities (doses, stock) are `DECIMAL(10,4)` handled via `App\Support\Pharmacy\Quantity`; never floats.
- Run `php artisan test` and `vendor/bin/pint --test` before committing.
