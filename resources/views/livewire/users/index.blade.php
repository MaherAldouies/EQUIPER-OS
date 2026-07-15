<div class="space-y-6">
    <h1 class="text-2xl font-bold">المستخدمون</h1>

    <form wire:submit="addUser" class="bg-white shadow rounded-lg p-4 flex items-end gap-3 flex-wrap">
        <div class="flex-1 min-w-[160px]">
            <label class="block text-sm font-medium text-gray-700">الاسم</label>
            <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-700">البريد الإلكتروني</label>
            <input type="email" wire:model="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="flex-1 min-w-[160px]">
            <label class="block text-sm font-medium text-gray-700">كلمة المرور</label>
            <input type="password" wire:model="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="flex-1 min-w-[160px]">
            <label class="block text-sm font-medium text-gray-700">الدور</label>
            <select wire:model="roleId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                <option value="">اختر دورًا…</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
            @error('roleId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="bg-gray-900 text-white rounded-md px-4 py-2 hover:bg-gray-800">إضافة مستخدم</button>
    </form>

    <div class="bg-white shadow rounded-lg divide-y">
        @forelse ($members as $member)
            <div class="p-3 text-sm">
                @if ($editingUserId === $member->id)
                    <form wire:submit="updateUser" class="flex items-end gap-3 flex-wrap">
                        <div class="flex-1 min-w-[160px]">
                            <label class="block text-xs text-gray-500">الاسم</label>
                            <input type="text" wire:model="editName" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            @error('editName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-xs text-gray-500">البريد الإلكتروني</label>
                            <input type="email" wire:model="editEmail" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            @error('editEmail') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="min-w-[140px]">
                            <label class="block text-xs text-gray-500">الحالة</label>
                            <select wire:model="editStatus" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                <option value="active">نشط</option>
                                <option value="invited">مدعو</option>
                                <option value="suspended">موقوف</option>
                                <option value="deactivated">معطّل</option>
                            </select>
                        </div>
                        <div class="min-w-[160px]">
                            <label class="block text-xs text-gray-500">الدور</label>
                            <select wire:model="editRoleId" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                            @error('editRoleId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex-1 min-w-[160px]">
                            <label class="block text-xs text-gray-500">كلمة مرور جديدة (اختياري)</label>
                            <input type="password" wire:model="editPassword" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            @error('editPassword') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="bg-green-600 text-white text-sm rounded-md px-4 py-1.5 hover:bg-green-700">حفظ</button>
                            <button type="button" wire:click="cancelEdit" class="text-sm text-gray-500 hover:underline px-2">إلغاء</button>
                        </div>
                    </form>
                @else
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-[200px]">
                            <div class="font-medium">{{ $member->name }}</div>
                            <div class="text-gray-500">{{ $member->email }}</div>
                        </div>
                        <div class="text-gray-500 flex-1">{{ $member->roles->pluck('name')->join('، ') }}</div>
                        <span @class([
                            'px-2 py-0.5 rounded-full text-xs shrink-0',
                            'bg-yellow-100 text-yellow-800' => $member->status === 'invited',
                            'bg-green-100 text-green-800' => $member->status === 'active',
                            'bg-red-100 text-red-800' => $member->status === 'suspended',
                            'bg-gray-100 text-gray-800' => $member->status === 'deactivated',
                        ])>{{ $member->status }}</span>
                        <button type="button" wire:click="startEdit('{{ $member->id }}')" class="text-indigo-600 hover:underline text-xs shrink-0">تعديل</button>
                    </div>
                @endif
            </div>
        @empty
            <p class="p-3 text-gray-500 text-sm">لا يوجد مستخدمون بعد.</p>
        @endforelse
    </div>

    <div>{{ $members->links() }}</div>
</div>
