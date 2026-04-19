<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskCompletion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GameController extends Controller
{
    public function startGame(Request $request)
    {
        $data = $request->validate([
            'start_now' => 'required|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:today',
        ]);

        $house = $request->user()->houses()->latest()->first();

        if (!$house || $house->status !== 'setup') {
            return response()->json(['message' => 'A casa não está na fase de configuração.'], 422);
        }

        if ($house->members()->count() < 2) {
            return response()->json(['message' => 'Aguardando o segundo jogador.'], 422);
        }

        if ($house->tasks()->count() === 0) {
            return response()->json(['message' => 'Adicione pelo menos uma tarefa.'], 422);
        }

        $startDate = $data['start_now'] ? Carbon::now() : Carbon::parse($data['start_date']);

        $house->update([
            'tasks_locked' => true,
            'status' => 'waiting_ready',
            'game_start_date' => $startDate,
            'game_end_date' => isset($data['end_date']) ? Carbon::parse($data['end_date']) : null,
        ]);

        // Reset ready status
        $house->members()->each(function ($member) use ($house) {
            $house->members()->updateExistingPivot($member->id, ['is_ready' => false]);
        });

        $house->refresh()->load(['members', 'tasks']);

        return response()->json(['house' => [
            'id' => $house->id,
            'name' => $house->name,
            'status' => $house->status,
            'game_start_date' => $house->game_start_date,
            'game_end_date' => $house->game_end_date,
        ]]);
    }

    public function todayTasks(Request $request)
    {
        $house = $request->user()->houses()->latest()->first();

        if (!$house || $house->status !== 'active') {
            return response()->json(['tasks' => [], 'message' => 'Jogo não está ativo.']);
        }

        $today = Carbon::today();
        $tasks = $house->tasks()->get();

        $todayTasks = $tasks->filter(fn($t) => $t->isScheduledFor($today));

        $completions = TaskCompletion::where('house_id', $house->id)
            ->whereDate('completion_date', $today)
            ->get()
            ->keyBy('task_id');

        return response()->json([
            'tasks' => $todayTasks->map(fn($task) => [
                'id' => $task->id,
                'name' => $task->name,
                'frequency' => $task->frequency,
                'points' => $task->points,
                'emoji' => $task->emoji,
                'completed' => isset($completions[$task->id]),
                'completed_by' => isset($completions[$task->id])
                    ? $completions[$task->id]->user_id
                    : null,
                'photo_url' => isset($completions[$task->id])
                    ? $completions[$task->id]->photo_url
                    : null,
            ])->values(),
            'date' => $today->toDateString(),
        ]);
    }

    public function completeTask(Request $request, Task $task)
    {
        $house = $request->user()->houses()->latest()->first();

        if (!$house || $house->status !== 'active' || $task->house_id !== $house->id) {
            return response()->json(['message' => 'Operação inválida.'], 422);
        }

        $today = Carbon::today();

        if (!$task->isScheduledFor($today)) {
            return response()->json(['message' => 'Esta tarefa não é para hoje.'], 422);
        }

        $existing = TaskCompletion::where('task_id', $task->id)
            ->whereDate('completion_date', $today)
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Tarefa já foi concluída hoje.'], 422);
        }

        // Handle photo upload
        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('completions', 'public');
            $photoUrl = Storage::url($path);
        }

        $completion = TaskCompletion::create([
            'task_id' => $task->id,
            'house_id' => $house->id,
            'user_id' => $request->user()->id,
            'completed_at' => Carbon::now(),
            'completion_date' => $today,
            'photo_url' => $photoUrl,
            'points_earned' => $task->points,
        ]);

        // Update member score
        $house->members()->updateExistingPivot($request->user()->id, [
            'score' => $house->members()->where('users.id', $request->user()->id)->first()->pivot->score + $task->points,
        ]);

        // Broadcast to other members
        $otherMembers = $house->members()->where('users.id', '!=', $request->user()->id)->get();
        foreach ($otherMembers as $member) {
            event(new \App\Events\TaskCompleted($completion, $request->user(), $member, $task));
        }

        return response()->json([
            'completion' => $completion,
            'points_earned' => $task->points,
            'message' => "Tarefa concluída! +{$task->points} pontos!",
        ]);
    }

    public function score(Request $request)
    {
        $house = $request->user()->houses()->latest()->first();

        if (!$house) {
            return response()->json(['scores' => []]);
        }

        $house->load('members');
        $members = $house->members;

        $totalPossiblePoints = $house->tasks()->sum('points');

        $scores = $members->map(fn($m) => [
            'id' => $m->id,
            'name' => $m->name,
            'avatar_color' => $m->avatar_color,
            'score' => $m->pivot->score,
            'is_me' => $m->id === $request->user()->id,
        ])->sortByDesc('score')->values();

        return response()->json([
            'scores' => $scores,
            'house_name' => $house->name,
            'status' => $house->status,
            'game_end_date' => $house->game_end_date,
            'total_possible' => $totalPossiblePoints,
        ]);
    }

    public function history(Request $request)
    {
        $house = $request->user()->houses()->latest()->first();

        if (!$house) {
            return response()->json(['history' => []]);
        }

        $completions = TaskCompletion::with(['task', 'user'])
            ->where('house_id', $house->id)
            ->orderByDesc('completed_at')
            ->limit(50)
            ->get();

        return response()->json(['history' => $completions]);
    }
}
