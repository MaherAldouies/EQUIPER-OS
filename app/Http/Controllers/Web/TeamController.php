<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * TeamController — PRD F2: "Owner can invite a Team Member by email;
 * invited member sets up their own password."
 *
 * v1.0 keeps this simple: a random temporary password is generated and
 * Laravel's standard password-reset flow is reused to let the invited
 * member set their own password (rather than building a separate
 * invitation-token system) — a deliberate scope-control choice.
 */
class TeamController extends Controller
{
    public function index(Request $request)
    {
        $organization = $request->attributes->get('organization');

        $members = User::query()
            ->where('organization_id', $organization->id)
            ->with('roles')
            ->get();

        $roles = Role::query()->where('organization_id', $organization->id)->get();

        return view('team.index', compact('members', 'roles'));
    }

    public function invite(Request $request)
    {
        $organization = $request->attributes->get('organization');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role_id' => ['required', 'uuid', 'exists:roles,id'],
        ]);

        $user = User::query()->create([
            'organization_id' => $organization->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make(Str::random(32)), // unusable until reset
            'status' => 'invited',
        ]);

        $user->roles()->attach($data['role_id']);

        $user->recordEvent(eventType: 'TeamMemberActivated', payload: ['invited_by' => $request->user()->id]);

        Password::sendResetLink(['email' => $user->email]);

        return back()->with('status', "تمت دعوة {$user->name} — سيصله رابط لتعيين كلمة المرور.");
    }
}
