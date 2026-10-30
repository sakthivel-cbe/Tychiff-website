<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AuthenticatedHomeController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        return redirect()->to($this->destinationFor($request->user()));
    }

    public function destinationFor(User $user): string
    {
        $destinations = [
            ['system.manage', 'foundation.system'],
            ['recruiters.assign', 'foundation.recruitment-manager'],
            ['recruitment.intervene', 'foundation.management'],
            ['requirements.view_assigned', 'foundation.recruiter'],
            ['admin.support', 'foundation.admin'],
        ];

        foreach ($destinations as [$ability, $route]) {
            if (Gate::forUser($user)->allows($ability)) {
                return route($route);
            }
        }

        abort(403);
    }

    public function show(string $workspace): View
    {
        $labels = [
            'system' => 'System administration',
            'recruitment-manager' => 'Recruitment management',
            'management' => 'Management',
            'recruiter' => 'Recruiter',
            'admin' => 'Administrative support',
        ];

        abort_unless(isset($labels[$workspace]), 404);

        return view('foundation.home', ['workspace' => $labels[$workspace]]);
    }
}
