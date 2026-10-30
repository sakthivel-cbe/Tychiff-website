<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        if (! Auth::attempt([
            'email' => mb_strtolower($credentials['email']),
            'password' => $credentials['password'],
        ], $request->boolean('remember'))) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'The provided credentials are invalid or the account is not active.'], 422);
            }

            return redirect()->route('login')
                ->withErrors(['email' => 'The provided credentials are invalid or the account is not active.'])
                ->withInput($request->only('email'));
        }

        $request->session()->regenerate();
        $user = $request->user();

        if (! in_array($user->status, ['active', 'pending'], true)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'The provided credentials are invalid or the account is not active.'], 422);
            }

            return redirect()->route('login')
                ->withErrors(['email' => 'The provided credentials are invalid or the account is not active.'])
                ->withInput($request->only('email'));
        }

        $user->forceFill(['last_login_at' => now()])->save();

        if ($user->status !== 'active' || ! $user->hasVerifiedEmail()) {
            if ($request->expectsJson()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return response()->json(['message' => 'Verify your email before signing in.'], 422);
            }

            return redirect()->route('verification.notice')
                ->with('status', 'Verify your email address before signing in.');
        }

        AuditLog::record('auth.login', ['entity_type' => 'user', 'entity_id' => (string) $user->getKey()]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Signed in successfully.']);
        }

        $destination = app(AuthenticatedHomeController::class)->destinationFor($user);

        return redirect()->intended($destination);
    }

    public function destroy(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->user()) {
            AuditLog::record('auth.logout', ['entity_type' => 'user', 'entity_id' => (string) $request->user()->getKey()]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Signed out successfully.']);
        }

        return redirect()->route('login')->with('status', 'You have been signed out.');
    }
}
