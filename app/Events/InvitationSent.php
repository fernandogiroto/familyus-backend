<?php

namespace App\Events;

use App\Models\HouseInvitation;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvitationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public HouseInvitation $invitation,
        public User $invitedUser,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("user.{$this->invitedUser->id}")];
    }

    public function broadcastAs(): string
    {
        return 'invitation.received';
    }

    public function broadcastWith(): array
    {
        return [
            'invitation_token' => $this->invitation->token,
            'house_name' => $this->invitation->house->name,
            'invited_by' => $this->invitation->inviter->name,
        ];
    }
}
