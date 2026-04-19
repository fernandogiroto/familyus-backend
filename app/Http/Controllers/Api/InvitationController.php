<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\House;
use App\Models\HouseInvitation;
use App\Models\User;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    public function send(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        $house = $request->user()->houses()->latest()->first();

        if (!$house) {
            return response()->json(['message' => 'Você não tem uma casa.'], 422);
        }

        if ($house->members()->count() >= 2) {
            return response()->json(['message' => 'A casa já está cheia (máx 2 jogadores).'], 422);
        }

        if ($data['email'] === $request->user()->email) {
            return response()->json(['message' => 'Você não pode convidar a si mesmo.'], 422);
        }

        // Cancel existing pending invitation
        $house->invitations()->where('status', 'pending')->delete();

        $invitation = HouseInvitation::create([
            'house_id' => $house->id,
            'invited_by' => $request->user()->id,
            'invited_email' => $data['email'],
            'status' => 'pending',
        ]);

        // Notify the invited user if they have an account
        $invitedUser = User::where('email', $data['email'])->first();
        if ($invitedUser) {
            // Broadcast notification
            event(new \App\Events\InvitationSent($invitation, $invitedUser));
        }

        return response()->json(['invitation' => $invitation, 'message' => 'Convite enviado!']);
    }

    public function pending(Request $request)
    {
        $invitations = HouseInvitation::with(['house', 'inviter'])
            ->where('invited_email', $request->user()->email)
            ->where('status', 'pending')
            ->get();

        return response()->json(['invitations' => $invitations]);
    }

    public function accept(Request $request, string $token)
    {
        $invitation = HouseInvitation::where('token', $token)
            ->where('invited_email', $request->user()->email)
            ->where('status', 'pending')
            ->firstOrFail();

        $house = $invitation->house;

        if ($house->members()->count() >= 2) {
            return response()->json(['message' => 'A casa já está cheia.'], 422);
        }

        // Leave any existing house
        $existing = $request->user()->houses()->first();
        if ($existing) {
            $existing->members()->detach($request->user()->id);
        }

        $invitation->update(['status' => 'accepted']);
        $house->members()->attach($request->user()->id, ['is_ready' => false, 'score' => 0]);
        $house->load(['members', 'tasks']);

        return response()->json([
            'house' => [
                'id' => $house->id,
                'name' => $house->name,
                'status' => $house->status,
                'members' => $house->members->map(fn($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'avatar_color' => $m->avatar_color,
                    'is_me' => $m->id === $request->user()->id,
                ]),
            ],
            'message' => 'Convite aceito! Bem-vindo à casa!',
        ]);
    }

    public function reject(Request $request, string $token)
    {
        $invitation = HouseInvitation::where('token', $token)
            ->where('invited_email', $request->user()->email)
            ->where('status', 'pending')
            ->firstOrFail();

        $invitation->update(['status' => 'rejected']);

        return response()->json(['message' => 'Convite recusado.']);
    }
}
