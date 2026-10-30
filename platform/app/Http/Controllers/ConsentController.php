<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ConsentRecord;
use App\Models\PrivacyNoticeVersion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ConsentController extends Controller
{
    private const ACKNOWLEDGEMENT_TYPE = 'privacy_notice_acknowledgement';

    public function index(Request $request): View
    {
        $notice = $this->currentNotice();

        $acknowledged = ConsentRecord::query()
            ->where('user_id', $request->user()->getKey())
            ->where('privacy_notice_version_id', $notice->getKey())
            ->where('consent_type', self::ACKNOWLEDGEMENT_TYPE)
            ->where('granted', true)
            ->exists();

        $history = ConsentRecord::query()
            ->with('noticeVersion:id,version,title')
            ->where('user_id', $request->user()->getKey())
            ->where('consent_type', self::ACKNOWLEDGEMENT_TYPE)
            ->latest('recorded_at')
            ->limit(10)
            ->get();

        return view('privacy.consent', compact('notice', 'acknowledged', 'history'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['acknowledged' => ['required', 'accepted']]);
        $notice = $this->currentNotice();
        $actor = $request->user();

        $created = DB::transaction(function () use ($actor, $notice, $request): bool {
            User::query()->whereKey($actor->getKey())->lockForUpdate()->firstOrFail();

            $exists = ConsentRecord::query()
                ->where('user_id', $actor->getKey())
                ->where('privacy_notice_version_id', $notice->getKey())
                ->where('consent_type', self::ACKNOWLEDGEMENT_TYPE)
                ->where('granted', true)
                ->exists();

            if ($exists) {
                return false;
            }

            $record = ConsentRecord::create([
                'user_id' => $actor->getKey(),
                'privacy_notice_version_id' => $notice->getKey(),
                'consent_type' => self::ACKNOWLEDGEMENT_TYPE,
                'granted' => true,
                'recorded_at' => now(),
                'source' => 'authenticated_web',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'context' => ['interaction' => 'phase_1_notice_acknowledgement'],
            ]);

            AuditLog::record('privacy.notice_acknowledged', [
                'user_id' => $actor->getKey(),
                'entity_type' => ConsentRecord::class,
                'entity_id' => (string) $record->getKey(),
                'new_state' => [
                    'notice_version' => $notice->version,
                    'consent_type' => self::ACKNOWLEDGEMENT_TYPE,
                    'granted' => true,
                ],
            ]);

            return true;
        });

        return redirect()->route('consent.index')->with(
            'status',
            $created ? 'Your notice acknowledgement has been recorded.' : 'Your acknowledgement for this notice version is already recorded.'
        );
    }

    private function currentNotice(): PrivacyNoticeVersion
    {
        return PrivacyNoticeVersion::query()
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->firstOrFail();
    }
}
