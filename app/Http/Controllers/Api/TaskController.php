<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $house = $request->user()->houses()->latest()->first();
        if (!$house) {
            return response()->json(['tasks' => []]);
        }

        return response()->json(['tasks' => $house->tasks()->orderBy('created_at')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'frequency' => 'required|in:daily,weekly,monthly',
            'points' => 'required|integer|min:1|max:20',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'day_of_month' => 'nullable|integer|min:1|max:28',
            'emoji' => 'nullable|string|max:10',
        ]);

        $house = $request->user()->houses()->latest()->first();

        if (!$house) {
            return response()->json(['message' => 'Você não tem uma casa.'], 422);
        }

        if ($house->tasks_locked) {
            return response()->json(['message' => 'A lista de tarefas está fechada.'], 422);
        }

        $data['house_id'] = $house->id;
        $data['created_by'] = $request->user()->id;

        $task = Task::create($data);

        return response()->json(['task' => $task], 201);
    }

    public function update(Request $request, Task $task)
    {
        $this->authorizeTask($task, $request->user());

        $data = $request->validate([
            'name' => 'string|max:100',
            'frequency' => 'in:daily,weekly,monthly',
            'points' => 'integer|min:1|max:20',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'day_of_month' => 'nullable|integer|min:1|max:28',
            'emoji' => 'nullable|string|max:10',
        ]);

        $task->update($data);

        return response()->json(['task' => $task]);
    }

    public function destroy(Request $request, Task $task)
    {
        $this->authorizeTask($task, $request->user());

        if ($task->house->tasks_locked) {
            return response()->json(['message' => 'A lista de tarefas está fechada.'], 422);
        }

        $task->delete();

        return response()->json(['message' => 'Tarefa removida.']);
    }

    private function authorizeTask(Task $task, $user): void
    {
        $house = $user->houses()->latest()->first();
        abort_if(!$house || $task->house_id !== $house->id, 403, 'Sem permissão.');
    }
}
