<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessRequest;
use App\Models\Clinic;
use App\Models\Owner;
use App\Models\OwnerPatientLink;
use App\Models\Scopes\ClinicScope;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The superadministrator's read-only view of the whole platform: who has
 * signed up (clinics, their staff, pet owners) and who is asking to join.
 *
 * No tenant is bound on these routes, and every tenant-owned query below
 * drops ClinicScope explicitly so the figures are platform-wide by design,
 * not by accident.
 */
final class PlatformController extends Controller
{
    private const PER_PAGE = 25;

    public function dashboard(): View
    {
        $weekAgo = now()->subDays(7);

        return view('admin.dashboard', [
            'stats' => [
                'clinics' => Clinic::query()->where('is_active', true)->count(),
                'staff' => User::query()->whereNotNull('clinic_id')->count(),
                'owners' => Owner::query()->count(),
                'owners_week' => Owner::query()->where('created_at', '>=', $weekAgo)->count(),
                'linked_pets' => OwnerPatientLink::query()->withoutGlobalScope(ClinicScope::class)->whereNull('revoked_at')->count(),
                'pending_requests' => AccessRequest::query()->where('status', AccessRequest::STATUS_PENDING)->count(),
            ],
            'recentOwners' => $this->ownersQuery()->latest()->limit(5)->get(),
            'recentRequests' => AccessRequest::query()->latest()->limit(5)->get(),
        ]);
    }

    public function owners(Request $request): View
    {
        return view('admin.owners', [
            'search' => $term = $this->term($request),
            'owners' => $this->ownersQuery()
                ->when($term !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                    ->whereLike('name', "%{$term}%")
                    ->orWhereLike('email', "%{$term}%")
                    ->orWhereLike('phone', "%{$term}%")))
                ->latest()
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
        ]);
    }

    public function clinics(Request $request): View
    {
        return view('admin.clinics', [
            'search' => $term = $this->term($request),
            'clinics' => Clinic::query()
                ->withCount([
                    'users',
                    'patients' => fn (Builder $q) => $q->withoutGlobalScope(ClinicScope::class),
                    'ownerLinks as linked_pets_count' => fn (Builder $q) => $q->withoutGlobalScope(ClinicScope::class)->whereNull('revoked_at'),
                ])
                ->when($term !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                    ->whereLike('name', "%{$term}%")
                    ->orWhereLike('tax_id', "{$term}%")))
                ->latest()
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
        ]);
    }

    public function staff(Request $request): View
    {
        return view('admin.staff', [
            'search' => $term = $this->term($request),
            'users' => User::query()
                ->with('clinic:id,name')
                ->whereNotNull('clinic_id')
                ->when($term !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                    ->whereLike('name', "%{$term}%")
                    ->orWhereLike('email', "%{$term}%")
                    ->orWhereHas('clinic', fn (Builder $c) => $c->whereLike('name', "%{$term}%"))))
                ->latest()
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
        ]);
    }

    public function accessRequests(Request $request): View
    {
        return view('admin.access-requests', [
            'search' => $term = $this->term($request),
            'requests' => AccessRequest::query()
                ->when($term !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                    ->whereLike('clinic_name', "%{$term}%")
                    ->orWhereLike('contact_name', "%{$term}%")
                    ->orWhereLike('email', "%{$term}%")
                    ->orWhereLike('city', "%{$term}%")))
                ->latest()
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
        ]);
    }

    /** Owners with how many pets they can see right now and in how many clinics. */
    private function ownersQuery(): Builder
    {
        $activeLinks = fn (Builder $q) => $q->withoutGlobalScope(ClinicScope::class)->whereNull('revoked_at');

        return Owner::query()->withCount([
            'links as pets_count' => $activeLinks,
            'links as clinics_count' => fn (Builder $q) => $activeLinks($q)->select(DB::raw('count(distinct clinic_id)')),
        ]);
    }

    private function term(Request $request): string
    {
        return mb_substr(trim($request->string('q')->toString()), 0, 100);
    }
}
