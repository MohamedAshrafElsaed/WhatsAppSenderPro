<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $user->load(['country', 'industry', 'subscription.package']);

        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'mobile_number' => $user->mobile_number,
                'country' => $user->country ? [
                    'id' => $user->country->id,
                    'name' => $user->country->name,
                    'phone_code' => $user->country->phone_code,
                ] : null,
                'industry' => $user->industry ? [
                    'id' => $user->industry->id,
                    'name' => $user->industry->name,
                ] : null,
                'locale' => $user->locale,
                'email_verified_at' => $user->email_verified_at?->toISOString(),
                'mobile_verified_at' => $user->mobile_verified_at?->toISOString(),
                'created_at' => $user->created_at->toISOString(),
                'subscription_status' => $user->subscription_status,
            ],
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validated();

        // Update locale if provided
        if (isset($validated['locale'])) {
            session()->put('locale', $validated['locale']);
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return back()->with('success', __('settings.saved'));
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
