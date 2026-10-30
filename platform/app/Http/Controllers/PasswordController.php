<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function resetForm(Request $request, string $token): Response|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Open the password reset page in a browser to continue.'], 406)
                ->header('Cache-Control', 'no-store, private');
        }

        return response()->view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function email(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:255']]);

        Password::sendResetLink(['email' => mb_strtolower($request->string('email')->toString())]);

        // Keep the response independent of whether the account exists.
        $message = 'If the account is eligible, password reset instructions will be sent.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('password.request')->with('status', $message);
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $credentials = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $status = Password::reset(
            [
                'email' => mb_strtolower($credentials['email']),
                'password' => $credentials['password'],
                'password_confirmation' => $request->input('password_confirmation'),
                'token' => $credentials['token'],
            ],
            function ($user, string $password): void {
                $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();

                if (config('session.driver') === 'database') {
                    DB::table(config('session.table', 'sessions'))->where('user_id', $user->getKey())->delete();
                }

                event(new PasswordReset($user));
                AuditLog::record('auth.password_reset', ['entity_type' => 'user', 'entity_id' => (string) $user->getKey()]);
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->expectsJson()) {
            $message = $status === Password::PASSWORD_RESET
                ? 'Your password has been reset. Sign in with your new password.'
                : 'This password reset link is invalid or expired. Request a new link.';

            return response()->json(['message' => $message], $status === Password::PASSWORD_RESET ? 200 : 422)
                ->header('Cache-Control', 'no-store, private');
        }

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Your password has been reset. Sign in with your new password.');
        }

        return redirect()->route('password.request')
            ->withErrors(['email' => 'This password reset link is invalid or expired. Request a new link.'])
            ->withInput($request->only('email'));
    }
}
