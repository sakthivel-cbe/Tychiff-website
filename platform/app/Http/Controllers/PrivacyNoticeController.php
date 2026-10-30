<?php

namespace App\Http\Controllers;

use App\Models\PrivacyNoticeVersion;
use Illuminate\View\View;

class PrivacyNoticeController extends Controller
{
    public function __invoke(): View
    {
        $notice = PrivacyNoticeVersion::query()
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->firstOrFail();

        return view('privacy.show', ['notice' => $notice]);
    }
}
