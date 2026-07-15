<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $tasks = Task::query()
            ->where('organization_id', $request->user()->organization_id)
            ->when($request->boolean('mine'), fn ($q) => $q->where('assigned_to', $request->user()->id))
            ->latest('due_at')
            ->paginate(50);

        return TaskResource::collection($tasks);
    }
}
