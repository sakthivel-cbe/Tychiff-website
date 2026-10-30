<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\StaffInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class FoundationAuthAndInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_uses_server_credentials_and_rejects_pending_accounts(): void
    {
        $user = User::factory()->create(['password' => 'correct horse battery staple', 'status' => 'active']);
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->postJson('/login', ['email' => $user->email, 'password' => 'wrong password'])->assertUnprocessable();
        $this->postJson('/login', ['email' => $user->email, 'password' => 'correct horse battery staple'])->assertOk();
        $this->assertAuthenticatedAs($user);

        auth()->logout();
        $user->forceFill(['status' => 'pending'])->save();
        $this->postJson('/login', ['email' => $user->email, 'password' => 'correct horse battery staple'])->assertUnprocessable();
        $this->assertGuest();
    }

    public function test_only_users_with_invite_permission_can_issue_staff_invites(): void
    {
        $this->seed();
        Notification::fake();
        $superAdmin = User::factory()->create(['status' => 'active']);
        $superAdmin->forceFill(['email_verified_at' => now()])->save();
        $superAdmin->roles()->attach(Role::where('slug', 'super-admin')->value('id'));
        $recruiter = User::factory()->create(['status' => 'active']);
        $recruiter->forceFill(['email_verified_at' => now()])->save();
        $recruiter->roles()->attach(Role::where('slug', 'recruiter')->value('id'));
        $roleId = Role::where('slug', 'recruiter')->value('id');

        $this->actingAs($recruiter)->postJson('/admin/invitations', [
            'email' => 'new.staff@example.test', 'role_ids' => [$roleId],
        ])->assertForbidden();

        $this->actingAs($superAdmin)->postJson('/admin/invitations', [
            'email' => 'new.staff@example.test', 'role_ids' => [$roleId],
        ])->assertCreated();

        $invitation = Invitation::where('email', 'new.staff@example.test')->firstOrFail();
        $this->assertSame(64, strlen($invitation->token_hash));
        $this->assertNotSame($invitation->email, $invitation->token_hash);
        Notification::assertSentOnDemand(StaffInvitationNotification::class);
    }

    public function test_invitation_requires_roles_assign_in_addition_to_users_invite(): void
    {
        $this->seed();
        Notification::fake();

        $inviter = User::factory()->create(['status' => 'active']);
        $inviteOnlyRole = Role::create([
            'name' => 'Invitation Coordinator',
            'slug' => 'invitation-coordinator',
            'description' => 'Test-only role for authorization coverage.',
            'is_system' => false,
        ]);
        $inviteOnlyRole->permissions()->attach(Permission::where('name', 'users.invite')->value('id'));
        $inviter->roles()->attach($inviteOnlyRole->id);
        $recruiterRoleId = Role::where('slug', 'recruiter')->value('id');

        $this->actingAs($inviter)->postJson('/admin/invitations', [
            'email' => 'role.assignment.denied@example.test',
            'role_ids' => [$recruiterRoleId],
        ])->assertForbidden();

        $this->assertDatabaseMissing('invitations', ['email' => 'role.assignment.denied@example.test']);
    }

    public function test_inviter_cannot_assign_roles_outside_the_approved_staff_role_set(): void
    {
        $this->seed();
        Notification::fake();
        $superAdmin = $this->userWithRole('super-admin');
        $candidateRole = Role::create([
            'name' => 'Candidate',
            'slug' => 'candidate',
            'description' => 'Test-only non-staff role.',
            'is_system' => false,
        ]);

        $this->actingAs($superAdmin)->postJson('/admin/invitations', [
            'email' => 'candidate.role.denied@example.test',
            'role_ids' => [$candidateRole->id],
        ])->assertForbidden();

        $this->assertDatabaseMissing('invitations', ['email' => 'candidate.role.denied@example.test']);
    }

    public function test_super_admin_can_assign_multiple_approved_staff_roles_in_one_invitation(): void
    {
        $this->seed();
        Notification::fake();
        $superAdmin = $this->userWithRole('super-admin');
        $roleIds = Role::whereIn('slug', ['management', 'recruiter'])->pluck('id')->all();

        $this->actingAs($superAdmin)->postJson('/admin/invitations', [
            'email' => 'multiple.staff.roles@example.test',
            'role_ids' => $roleIds,
        ])->assertCreated();

        $this->assertEqualsCanonicalizing(
            ['management', 'recruiter'],
            Invitation::where('email', 'multiple.staff.roles@example.test')->firstOrFail()->roles()->pluck('slug')->all(),
        );
    }

    public function test_invitation_acceptance_creates_pending_account_with_multiple_roles(): void
    {
        $this->seed();
        Notification::fake();
        $inviter = User::factory()->create();
        $token = Str::random(64);
        $invitation = Invitation::create([
            'email' => 'multi.role@example.test',
            'token_hash' => hash('sha256', $token),
            'invited_by' => $inviter->id,
            'expires_at' => now()->addDay(),
        ]);
        $invitation->roles()->attach(Role::whereIn('slug', ['management', 'super-admin'])->pluck('id'));

        $this->postJson('/invitations/'.$token, [
            'name' => 'Multi Role User',
            'password' => 'a long test password 123',
            'password_confirmation' => 'a long test password 123',
        ])->assertCreated();

        $user = User::where('email', 'multi.role@example.test')->firstOrFail();
        $this->assertSame('pending', $user->status);
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertTrue($user->hasRole('management'));
        $this->assertTrue($user->hasRole('super-admin'));
        $this->assertNotSame('a long test password 123', $user->password);
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_signed_email_verification_activates_a_pending_user(): void
    {
        $user = User::factory()->unverified()->create(['status' => 'pending']);
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(10), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->actingAs($user)->getJson($url)->assertOk();

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertSame('active', $user->fresh()->status);
    }

    public function test_password_reset_uses_a_single_use_framework_token_and_hashed_password(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->postJson('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'a stronger password 123',
            'password_confirmation' => 'a stronger password 123',
        ])->assertOk();

        $this->assertTrue(password_verify('a stronger password 123', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    private function userWithRole(string $slug): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->roles()->attach(Role::where('slug', $slug)->value('id'));

        return $user;
    }
}
