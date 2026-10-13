<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\RequestAccessRequest;
use App\Models\AccessRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class AccessRequestController extends Controller
{
    public function create(): View
    {
        return view('auth.request-access');
    }

    public function store(RequestAccessRequest $request): RedirectResponse
    {
        // Honeypot: a bot filled the hidden field. Answer as if it worked, store nothing.
        if (filled($request->input('website'))) {
            return $this->thanks($request->string('email')->toString());
        }

        $data = $request->validated();

        AccessRequest::create([
            ...collect($data)->except('consent')->all(),
            'status' => AccessRequest::STATUS_PENDING,
            'consented_at' => now(),
        ]);

        return $this->thanks($data['email']);
    }

    private function thanks(string $email): RedirectResponse
    {
        return redirect()->route('access.create')->with('access_requested', $email);
    }
}
