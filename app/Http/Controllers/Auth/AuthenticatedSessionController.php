<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * AuthenticatedSessionController — v1.0 has NO public registration
 * (PRD F2: "Owner can invite a Team Member by email; invited member
 * sets up their own password"). This controller handles login/logout
 * only; the invitation-acceptance flow (setting an initial password)
 * is a small remaining piece — see TeamController below for the invite
 * side.
 */
class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'بيانات الدخول غير صحيحة.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
