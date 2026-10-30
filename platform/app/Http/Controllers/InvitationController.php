<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Invitation;
use App\Models\Role;
use App\Models\User;
use App\Notifications\StaffInvitationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InvitationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Invitation::class);

        $input = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role_ids' => ['required', 'array', 'min:1', 'max:5'],
            'role_ids.*' => ['required', 'integer', 'distinct', Rule::exists('roles', 'id')],
        ]);

        $email = mb_strtolower($input['email']);
        abort_if(User::where('email', $email)->exists(), 409, 'An account already exists for this email address.');

        $plainToken = Str::random(64);
        $roles = Role::whereIn('id', $input['role_ids'])->get();
        abort_if($roles->isEmpty(), 422, 'Select at least one valid role.');
        $this->authorize('assignRoles', [Invitation::class, $roles]);

        $invitation = DB::transaction(function () use ($email, $plainToken, $roles, $request): Invitation {
            Invitation::where('email', $email)->whereNull('accepted_at')->whereNull('revoked_at')
                ->update(['revoked_at' => now(), 'updated_at' => now()]);

            $invitation = Invitation::create([
                'email' => $email,
                'token_hash' => hash('sha256', $plainToken),
                'invited_by' => $request->user()->id,
                'expires_at' => now()->addDays(7),
            ]);
            $invitation->roles()->attach($roles->modelKeys());

            AuditLog::record('staff.invitation_issued', [
                'entity_type' => 'invitation',
                'entity_id' => (string) $invitation->id,
                'new_state' => ['email' => $email, 'roles' => $roles->pluck('slug')->all(), 'expires_at' => $invitation->expires_at->toIso8601String()],
            ]);

            return $invitation;
        });

        $url = route('invitations.accept.show', ['token' => $plainToken]);
        Notification::route('mail', $email)
            ->notify(new StaffInvitationNotification($url, $roles->pluck('name')->all()));

        return response()->json(['message' => 'Invitation issued.', 'invitation_id' => $invitation->id], 201);
    }

    public function show(string $token): JsonResponse
    {
        $invitation = Invitation::with('roles')->where('token_hash', hash('sha256', $token))->firstOrFail();
        abort_unless($invitation->isUsable(), 410, 'This invitation is no longer valid.');

        return response()->json([
            'email' => $invitation->email,
            'roles' => $invitation->roles->pluck('name')->all(),
            'expires_at' => $invitation->expires_at->toIso8601String(),
        ]);
    }

    public function accept(Request $request, string $token): JsonResponse
    {
        $input = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        [$user, $invitation] = DB::transaction(function () use ($input, $token): array {
            $invitation = Invitation::with('roles')->where('token_hash', hash('sha256', $token))->lockForUpdate()->firstOrFail();
            abort_unless($invitation->isUsable(), 410, 'This invitation is no longer valid.');
            abort_if(User::where('email', $invitation->email)->exists(), 409, 'An account already exists for this email address.');

            $user = User::create([
                'name' => $input['name'],
                'email' => $invitation->email,
                'password' => Hash::make($input['password']),
                'status' => 'pending',
            ]);
            $user->roles()->attach($invitation->roles->mapWithKeys(fn (Role $role) => [
                $role->id => ['assigned_by' => $invitation->invited_by, 'assigned_at' => now()],
            ])->all());
            $invitation->forceFill(['accepted_at' => now()])->save();

            AuditLog::record('staff.invitation_accepted', [
                'user_id' => $user->id,
                'entity_type' => 'invitation',
                'entity_id' => (string) $invitation->id,
                'new_state' => ['user_id' => $user->id, 'roles' => $invitation->roles->pluck('slug')->all()],
            ]);

            return [$user, $invitation];
        });

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Invitation accepted. Verify your email address before signing in.',
            'email' => $user->email,
        ], 201);
    }
}
