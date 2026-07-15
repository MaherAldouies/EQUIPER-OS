<?php

namespace App\Livewire\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tasks\Index — PRD F13: create tasks and assign them to a Team Member.
 * Reuses Task::markCompleted()'s event ('TaskCompleted') via the same
 * status transition here so only one place records that event.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $title = '';

    public string $description = '';

    public string $assignedTo = '';

    public string $dueAt = '';

    public ?string $editingTaskId = null;

    public string $editStatus = '';

    public string $editAssignedTo = '';

    public function addTask(): void
    {
        Gate::authorize('task.manage');

        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'assignedTo' => ['nullable', 'uuid', 'exists:users,id'],
            'dueAt' => ['nullable', 'date'],
        ]);

        $task = Task::query()->create([
            'organization_id' => auth()->user()->organization_id,
            'title' => $data['title'],
            'description' => $data['description'] ?: null,
            'assigned_to' => $data['assignedTo'] ?: null,
            'status' => $data['assignedTo'] ? 'assigned' : 'created',
            'due_at' => $data['dueAt'] ?: null,
        ]);

        $task->recordEvent(eventType: 'TaskCreated', payload: ['created_by' => auth()->id()]);

        $this->reset(['title', 'description', 'assignedTo', 'dueAt']);

        session()->flash('status', "تمت إضافة المهمة \"{$task->title}\".");
    }

    public function startEdit(string $taskId): void
    {
        Gate::authorize('task.manage');

        $task = Task::query()->findOrFail($taskId);

        $this->editingTaskId = $task->id;
        $this->editStatus = $task->status;
        $this->editAssignedTo = $task->assigned_to ?? '';
    }

    public function cancelEdit(): void
    {
        $this->editingTaskId = null;
    }

    public function updateTask(): void
    {
        Gate::authorize('task.manage');

        $task = Task::query()->findOrFail($this->editingTaskId);

        $data = $this->validate([
            'editStatus' => ['required', 'in:created,assigned,in_progress,completed,cancelled'],
            'editAssignedTo' => ['nullable', 'uuid', 'exists:users,id'],
        ]);

        $task->forceFill([
            'assigned_to' => $data['editAssignedTo'] ?: null,
            'status' => $data['editStatus'],
            'completed_at' => $data['editStatus'] === 'completed' ? now() : null,
        ])->save();

        if ($data['editStatus'] === 'completed') {
            $task->recordEvent(eventType: 'TaskCompleted', payload: ['task_id' => $task->id]);
        }

        $this->editingTaskId = null;

        session()->flash('status', 'تم تحديث المهمة.');
    }

    public function render()
    {
        $organizationId = auth()->user()->organization_id;

        return view('livewire.tasks.index', [
            'tasks' => Task::query()
                ->where('organization_id', $organizationId)
                ->with('assignee')
                ->latest('due_at')
                ->paginate(15),
            'users' => User::query()->where('organization_id', $organizationId)->orderBy('name')->get(),
        ]);
    }
}
