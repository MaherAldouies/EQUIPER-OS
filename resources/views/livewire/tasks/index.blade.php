<div class="space-y-6">
    <h1 class="text-2xl font-bold">المهام</h1>

    <form wire:submit="addTask" class="bg-white shadow rounded-lg p-4 flex items-end gap-3 flex-wrap">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-700">العنوان</label>
            <input type="text" wire:model="title" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            @error('title') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="flex-1 min-w-[220px]">
            <label class="block text-sm font-medium text-gray-700">الوصف</label>
            <input type="text" wire:model="description" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            @error('description') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="flex-1 min-w-[160px]">
            <label class="block text-sm font-medium text-gray-700">تعيين إلى</label>
            <select wire:model="assignedTo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                <option value="">بدون تعيين</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
            @error('assignedTo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="min-w-[160px]">
            <label class="block text-sm font-medium text-gray-700">تاريخ الاستحقاق</label>
            <input type="date" wire:model="dueAt" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            @error('dueAt') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="bg-gray-900 text-white rounded-md px-4 py-2 hover:bg-gray-800">إضافة مهمة</button>
    </form>

    <div class="bg-white shadow rounded-lg divide-y">
        @forelse ($tasks as $task)
            <div class="p-3 text-sm">
                @if ($editingTaskId === $task->id)
                    <form wire:submit="updateTask" class="flex items-end gap-3 flex-wrap">
                        <div class="flex-1 min-w-[200px]">
                            <div class="font-medium">{{ $task->title }}</div>
                            @if ($task->description)
                                <div class="text-gray-500">{{ $task->description }}</div>
                            @endif
                        </div>
                        <div class="min-w-[160px]">
                            <label class="block text-xs text-gray-500">تعيين إلى</label>
                            <select wire:model="editAssignedTo" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                <option value="">بدون تعيين</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                            @error('editAssignedTo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="min-w-[140px]">
                            <label class="block text-xs text-gray-500">الحالة</label>
                            <select wire:model="editStatus" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                <option value="created">تم الإنشاء</option>
                                <option value="assigned">معيّنة</option>
                                <option value="in_progress">قيد التنفيذ</option>
                                <option value="completed">مكتملة</option>
                                <option value="cancelled">ملغاة</option>
                            </select>
                            @error('editStatus') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="bg-green-600 text-white text-sm rounded-md px-4 py-1.5 hover:bg-green-700">حفظ</button>
                            <button type="button" wire:click="cancelEdit" class="text-sm text-gray-500 hover:underline px-2">إلغاء</button>
                        </div>
                    </form>
                @else
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-[220px]">
                            <div class="font-medium">{{ $task->title }}</div>
                            @if ($task->description)
                                <div class="text-gray-500">{{ $task->description }}</div>
                            @endif
                        </div>
                        <div class="text-gray-500 flex-1">{{ $task->assignee?->name ?? 'بدون تعيين' }}</div>
                        <div class="text-gray-400 text-xs shrink-0">{{ $task->due_at?->format('Y-m-d') ?? '—' }}</div>
                        <span @class([
                            'px-2 py-0.5 rounded-full text-xs shrink-0',
                            'bg-gray-100 text-gray-800' => $task->status === 'created',
                            'bg-blue-100 text-blue-800' => $task->status === 'assigned',
                            'bg-yellow-100 text-yellow-800' => $task->status === 'in_progress',
                            'bg-green-100 text-green-800' => $task->status === 'completed',
                            'bg-red-100 text-red-800' => $task->status === 'cancelled',
                        ])>{{ $task->status }}</span>
                        <button type="button" wire:click="startEdit('{{ $task->id }}')" class="text-indigo-600 hover:underline text-xs shrink-0">تعديل</button>
                    </div>
                @endif
            </div>
        @empty
            <p class="p-3 text-gray-500 text-sm">لا توجد مهام بعد.</p>
        @endforelse
    </div>

    <div>{{ $tasks->links() }}</div>
</div>
