<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(): View
    {
        return view('auth.verify-email');
    }

    public function send(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Email address is already verified.'])
                : redirect()->route('home')->with('status', 'Your email address is already verified.');
        }

        $request->user()->sendEmailVerificationNotification();
        AuditLog::record('auth.verification_requested', ['entity_type' => 'user', 'entity_id' => (string) $request->user()->getKey()]);

        return $request->expectsJson()
            ? response()->json(['message' => 'Verification link sent.'])
            : redirect()->route('verification.notice')->with('status', 'A new verification link has been sent to your email address.');
    }

    public function __invoke(Request $request, AuthenticatedHomeController $home): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $valid = $request->hasValidSignature()
            && (int) $request->route('id') === (int) $user->getKey()
            && hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()));

        if (! $valid) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'This verification link is invalid or expired. Request a new link.'], 403);
            }

            return redirect()->route('verification.notice')
                ->withErrors(['verification' => 'This verification link is invalid or expired. Request a new link.']);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
            AuditLog::record('auth.email_verified', ['entity_type' => 'user', 'entity_id' => (string) $user->getKey()]);
        }

        if ($user->status === 'pending') {
            $user->forceFill(['status' => 'active'])->save();
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Email address verified.']);
        }

        return redirect()->to($home->destinationFor($user))->with('status', 'Your email address has been verified.');
    }
}
