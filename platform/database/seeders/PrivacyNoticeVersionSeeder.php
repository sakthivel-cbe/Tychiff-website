<?php

namespace Database\Seeders;

use App\Models\PrivacyNoticeVersion;
use Illuminate\Database\Seeder;

class PrivacyNoticeVersionSeeder extends Seeder
{
    public function run(): void
    {
        $body = implode("\n\n", [
            'This notice describes the Phase 1 TYCHIFF platform account and security foundation. It is informational and is not legal advice or a substitute for a complete notice approved for the jurisdictions and services in which TYCHIFF operates.',
            'Purpose. The platform uses account information to create and administer staff accounts, authenticate users, verify email addresses, apply approved roles and access controls, protect the platform, and record security and governance events.',
            'Information categories. The foundation may handle a user name and email address; a password hash (not the password in readable form); account status, email-verification state and role assignments; session/security information; timestamps; and technical request metadata such as IP address and browser user-agent when needed for security, audit and consent-notice records.',
            'Current scope. This Phase 1 foundation does not provide candidate profiles, client portals, resumes, candidate matching, job applications or recruitment sharing workflows. Do not submit candidate or client recruitment data through this foundation. Any later workflow must have its own approved purpose, access rules and privacy information before real data is used.',
            'Notice acknowledgement. Signed-in users may record that they have read this version. That record documents acknowledgement of the notice only; it is not candidate consent, permission to share a candidate with a client, marketing consent or approval of future recruitment processing.',
            'Questions. Contact the TYCHIFF platform administrator with questions about an account or this notice. A complete organization-specific privacy notice and operational retention schedule must be approved before real candidate/client workflows are activated.',
        ]);

        PrivacyNoticeVersion::firstOrCreate(
            ['version' => 'phase-1.1'],
            [
                'title' => 'TYCHIFF Platform Privacy Notice — Phase 1 Foundation',
                'body' => $body,
                'published_at' => now(),
            ],
        );
    }
}
