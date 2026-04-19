<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('houses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['setup', 'waiting_ready', 'active', 'finished'])->default('setup');
            $table->boolean('tasks_locked')->default(false);
            $table->timestamp('game_start_date')->nullable();
            $table->timestamp('game_end_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('houses');
    }
};
