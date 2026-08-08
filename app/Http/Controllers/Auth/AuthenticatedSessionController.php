<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        if ($user->google2fa_secret) {
            Auth::logout();
            $request->session()->put('2fa:user:id', $user->id);
            $request->session()->put('2fa:user:remember', $request->boolean('remember'));
            return redirect()->route('2fa.verify');
        }

        $request->session()->regenerate();
        
        $user = Auth::user();
        
        // Dynamic redirection based on role
        if ($user->hasRole('Super Admin')) {
            return redirect()->intended(route('dashboard', absolute: false));
        } elseif ($user->hasRole('Admin')) {
            // CNL Admin langsung ke halaman Jobs
            return redirect()->intended(route('admin.jobs.index', absolute: false));
        } elseif ($user->hasRole('HR') || $user->can('access submissions')) {
            return redirect()->intended(route('admin.submissions.index', absolute: false));
        }

        // Default fallback
        return redirect()->intended(route('admin.jobs.index', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
