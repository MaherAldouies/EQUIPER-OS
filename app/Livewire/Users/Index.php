<?php

namespace App\Livewire\Users;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Users\Index — manual user management (add/edit) for an Owner/Manager,
 * separate from TeamController's email-invite flow: the admin sets the
 * password directly here, so the new user can log in immediately without
 * depending on outbound mail delivery being configured.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $roleId = '';

    public ?string $editingUserId = null;

    public string $editName = '';

    public string $editEmail = '';

    public string $editStatus = '';

    public string $editRoleId = '';

    public string $editPassword = '';

    public function addUser(): void
    {
        Gate::authorize('team.manage');

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'roleId' => ['required', 'uuid', 'exists:roles,id'],
        ]);

        $user = User::query()->create([
            'organization_id' => auth()->user()->organization_id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $user->roles()->attach($data['roleId']);

        $user->recordEvent(eventType: 'TeamMemberActivated', payload: ['created_by' => auth()->id()]);

        $this->reset(['name', 'email', 'password', 'roleId']);

        session()->flash('status', "تمت إضافة {$user->name} بنجاح.");
    }

    public function startEdit(string $userId): void
    {
        Gate::authorize('team.manage');

        $user = User::query()->findOrFail($userId);

        $this->editingUserId = $user->id;
        $this->editName = $user->name;
        $this->editEmail = $user->email;
        $this->editStatus = $user->status;
        $this->editRoleId = $user->roles()->first()?->id ?? '';
        $this->editPassword = '';
    }

    public function cancelEdit(): void
    {
        $this->editingUserId = null;
        $this->editPassword = '';
    }

    public function updateUser(): void
    {
        Gate::authorize('team.manage');

        $user = User::query()->findOrFail($this->editingUserId);

        $data = $this->validate([
            'editName' => ['required', 'string', 'max:255'],
            'editEmail' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'editStatus' => ['required', 'in:invited,active,suspended,deactivated'],
            'editRoleId' => ['required', 'uuid', 'exists:roles,id'],
            'editPassword' => ['nullable', 'string', 'min:8'],
        ]);

        $user->forceFill([
            'name' => $data['editName'],
            'email' => $data['editEmail'],
            'status' => $data['editStatus'],
        ]);

        if (! empty($data['editPassword'])) {
            $user->password = Hash::make($data['editPassword']);
        }

        $user->save();

        $user->roles()->sync([$data['editRoleId']]);

        $this->editingUserId = null;
        $this->editPassword = '';

        session()->flash('status', "تم تحديث بيانات {$user->name}.");
    }

    public function render()
    {
        $organizationId = auth()->user()->organization_id;

        return view('livewire.users.index', [
            'members' => User::query()
                ->where('organization_id', $organizationId)
                ->with('roles')
                ->orderBy('name')
                ->paginate(15),
            'roles' => Role::query()->where('organization_id', $organizationId)->get(),
        ]);
    }
}
