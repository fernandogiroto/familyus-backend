<?php

namespace App\Events;

use App\Models\Task;
use App\Models\TaskCompletion;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public TaskCompletion $completion,
        public User $completedBy,
        public User $notifyUser,
        public Task $task,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("user.{$this->notifyUser->id}")];
    }

    public function broadcastAs(): string
    {
        return 'task.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'task_name' => $this->task->name,
            'task_emoji' => $this->task->emoji,
            'completed_by' => $this->completedBy->name,
            'points' => $this->task->points,
            'photo_url' => $this->completion->photo_url,
            'completed_at' => $this->completion->completed_at,
        ];
    }
}
