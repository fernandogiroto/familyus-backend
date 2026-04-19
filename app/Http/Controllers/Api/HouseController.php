<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\House;
use Illuminate\Http\Request;

class HouseController extends Controller
{
    public function show(Request $request)
    {
        $house = $request->user()->houses()->latest()->first();

        if (!$house) {
            return response()->json(['house' => null]);
        }

        $house->load(['members', 'tasks', 'invitations' => function ($q) {
            $q->where('status', 'pending');
        }]);

        return response()->json(['house' => $this->formatHouse($house, $request->user())]);
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        // Leave any existing house first
        $existing = $request->user()->houses()->first();
        if ($existing) {
            $existing->members()->detach($request->user()->id);
        }

        $house = House::create([
            'name' => $data['name'],
            'created_by' => $request->user()->id,
            'status' => 'setup',
        ]);

        $house->members()->attach($request->user()->id, ['is_ready' => false, 'score' => 0]);
        $house->load(['members', 'tasks']);

        return response()->json(['house' => $this->formatHouse($house, $request->user())], 201);
    }

    public function setReady(Request $request)
    {
        $house = $request->user()->houses()->latest()->first();

        if (!$house || $house->status !== 'waiting_ready') {
            return response()->json(['message' => 'Casa não está aguardando confirmação.'], 422);
        }

        $house->members()->updateExistingPivot($request->user()->id, ['is_ready' => true]);
        $house->load('members');

        // Check if all members are ready
        $allReady = $house->members->every(fn($m) => $m->pivot->is_ready);

        if ($allReady && $house->members->count() >= 2) {
            $house->update(['status' => 'active']);
        }

        $house->refresh()->load(['members', 'tasks']);

        return response()->json(['house' => $this->formatHouse($house, $request->user())]);
    }

    private function formatHouse(House $house, $user): array
    {
        return [
            'id' => $house->id,
            'name' => $house->name,
            'status' => $house->status,
            'tasks_locked' => $house->tasks_locked,
            'game_start_date' => $house->game_start_date,
            'game_end_date' => $house->game_end_date,
            'members' => $house->members->map(fn($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'email' => $m->email,
                'avatar_color' => $m->avatar_color,
                'is_ready' => $m->pivot->is_ready,
                'score' => $m->pivot->score,
                'is_me' => $m->id === $user->id,
            ]),
            'tasks' => $house->tasks,
            'pending_invitations' => $house->invitations ?? [],
        ];
    }
}
