<?php

namespace Tests\Feature;

use App\Models\PrivacyNoticeVersion;
use App\Models\Role;
use App\Models\User;
use App\Notifications\SafePasswordResetNotification;
use App\Notifications\SafeVerifyEmailNotification;
use App\Notifications\StaffInvitationNotification;
use Database\Seeders\PrivacyNoticeVersionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class FoundationBrowserJourneysTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_accessible_and_contains_csrf_protected_form(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Sign in to your account')
            ->assertSee('name="_token"', false)
            ->assertSee(route('password.request'), false);
    }

    public function test_valid_browser_login_redirects_to_the_role_authorized_workspace(): void
    {
        $this->seed();
        $user = $this->userWithRole('recruiter');

        $this->post('/login', ['email' => $user->email, 'password' => 'correct horse battery staple'])
            ->assertRedirect(route('foundation.recruiter'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_browser_login_returns_a_safe_error_without_flashing_password(): void
    {
        $user = User::factory()->create(['password' => 'correct horse battery staple', 'status' => 'active']);
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong password value',
        ])->assertRedirect('/login')->assertSessionHasErrors('email')->assertSessionMissing('_old_input.password');

        $this->assertGuest();
        $this->assertArrayNotHasKey('password', session('_old_input', []));
    }

    public function test_pending_user_can_sign_in_only_to_reach_the_verification_notice(): void
    {
        $user = User::factory()->unverified()->create([
            'password' => 'correct horse battery staple',
            'status' => 'pending',
        ]);

        $this->post('/login', ['email' => $user->email, 'password' => 'correct horse battery staple'])
            ->assertRedirect(route('verification.notice'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('verification.notice'))->assertOk()->assertSee('Verify your email address');
        $this->get('/consent')->assertRedirect(route('verification.notice'));
    }

    public function test_logout_ends_the_server_side_session(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_role_workspace_routes_enforce_the_server_side_gate(): void
    {
        $this->seed();
        $recruiter = $this->userWithRole('recruiter');

        $this->actingAs($recruiter)->get(route('foundation.system'))->assertForbidden();
        $this->actingAs($recruiter)->get(route('foundation.recruiter'))->assertOk();
    }

    public function test_unauthenticated_user_is_redirected_from_consent_page(): void
    {
        $this->get('/consent')->assertRedirect(route('login'));
    }

    public function test_forgot_password_page_is_accessible(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Reset your password')
            ->assertSee('name="_token"', false);
    }

    public function test_forgot_password_response_does_not_reveal_account_existence(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $message = 'If the account is eligible, password reset instructions will be sent.';

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', $message);

        Notification::assertSentTo($user, SafePasswordResetNotification::class);

        $this->post('/forgot-password', ['email' => 'unknown@example.test'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', $message);
    }

    public function test_authentication_bearer_links_use_a_non_logging_mailer_locally(): void
    {
        config(['mail.default' => 'log']);
        $user = User::factory()->create();

        $reset = (new SafePasswordResetNotification('private-reset-token'))->toMail($user);
        $verification = (new SafeVerifyEmailNotification)->toMail($user);
        $invitation = (new StaffInvitationNotification('https://example.test/invite/private-invitation-token', ['Recruiter']))->toMail($user);

        $this->assertSame('array', $reset->mailer);
        $this->assertSame('array', $verification->mailer);
        $this->assertSame('array', $invitation->mailer);
    }

    public function test_reset_link_opens_a_no_store_browser_form(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('Choose a new password')
            ->assertSee('name="token"', false);
    }

    public function test_browser_password_reset_hashes_new_password_and_revokes_existing_database_sessions(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create(['password' => 'old password value 123']);
        $token = Password::createToken($user);
        $sessionId = 'phase1-auth-session';
        \DB::table('sessions')->insert([
            'id' => $sessionId,
            'user_id' => $user->getKey(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phase1 test',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'a stronger password 123',
            'password_confirmation' => 'a stronger password 123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(password_verify('a stronger password 123', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertDatabaseMissing('sessions', ['id' => $sessionId]);
        $this->assertGuest();
    }

    public function test_invalid_and_expired_reset_tokens_are_rejected_with_a_generic_message(): void
    {
        $user = User::factory()->create();
        $data = [
            'email' => $user->email,
            'password' => 'a stronger password 123',
            'password_confirmation' => 'a stronger password 123',
        ];

        $this->from('/forgot-password')->post('/reset-password', $data + ['token' => 'invalid-token'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('email');

        $token = Password::createToken($user);
        $this->travel(61)->minutes();
        $this->from('/forgot-password')->post('/reset-password', $data + ['token' => $token])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('email');
        $this->travelBack();
    }

    public function test_verification_resend_and_signed_link_complete_browser_journey(): void
    {
        Notification::fake();
        $this->seed();
        $user = $this->userWithRole('admin', verified: false);
        $this->actingAs($user);

        $this->post(route('verification.send'))->assertRedirect();
        Notification::assertSentTo($user, SafeVerifyEmailNotification::class);

        $signedUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(10), [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->get($signedUrl)->assertRedirect(route('foundation.admin'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertSame('active', $user->fresh()->status);
    }

    public function test_expired_or_invalid_verification_link_returns_a_safe_browser_message(): void
    {
        $user = User::factory()->unverified()->create(['status' => 'pending']);
        $this->actingAs($user);
        $expiredUrl = URL::temporarySignedRoute('verification.verify', now()->subMinute(), [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->get($expiredUrl)
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHasErrors('verification');
    }

    public function test_privacy_notice_is_public_and_uses_the_published_version_record(): void
    {
        $this->seed(PrivacyNoticeVersionSeeder::class);
        $notice = PrivacyNoticeVersion::where('version', 'phase-1.1')->firstOrFail();

        $this->get('/privacy')
            ->assertOk()
            ->assertSee($notice->title)
            ->assertSee($notice->version)
            ->assertSee('Purpose. The platform uses account information');
    }

    public function test_consent_page_records_an_idempotent_auditable_notice_acknowledgement(): void
    {
        $this->seed(PrivacyNoticeVersionSeeder::class);
        $user = $this->userWithRole('recruiter');

        $this->actingAs($user)->get('/consent')->assertOk()->assertSee('Privacy notice acknowledgement');
        $this->assertDatabaseCount('consent_records', 0);

        $payload = ['acknowledged' => '1'];
        $this->post('/consent/acknowledge', $payload)->assertRedirect(route('consent.index'));
        $this->assertDatabaseCount('consent_records', 1);
        $this->assertDatabaseHas('consent_records', [
            'user_id' => $user->getKey(),
            'consent_type' => 'privacy_notice_acknowledgement',
            'granted' => true,
            'source' => 'authenticated_web',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'privacy.notice_acknowledged']);

        $this->post('/consent/acknowledge', $payload)->assertRedirect(route('consent.index'));
        $this->assertDatabaseCount('consent_records', 1);
        $this->assertDatabaseCount('audit_logs', 1);
    }

    public function test_consent_acknowledgement_requires_a_verified_authenticated_user(): void
    {
        $this->seed(PrivacyNoticeVersionSeeder::class);
        $user = User::factory()->unverified()->create(['status' => 'pending']);

        $this->actingAs($user)->get('/consent')->assertRedirect(route('verification.notice'));
        $this->actingAs($user)->post('/consent/acknowledge', ['acknowledged' => '1'])
            ->assertRedirect(route('verification.notice'));
        $this->assertDatabaseCount('consent_records', 0);
    }

    private function userWithRole(string $slug, bool $verified = true): User
    {
        $this->seed();
        $user = User::factory()->create([
            'password' => 'correct horse battery staple',
            'status' => $verified ? 'active' : 'pending',
        ]);
        if ($verified) {
            $user->forceFill(['email_verified_at' => now()])->save();
        } else {
            $user->forceFill(['email_verified_at' => null])->save();
        }
        $user->roles()->attach(Role::where('slug', $slug)->value('id'));

        return $user;
    }
}
