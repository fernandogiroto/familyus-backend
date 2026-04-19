<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('house_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('completed_at');
            $table->date('completion_date');
            $table->string('photo_url')->nullable();
            $table->unsignedTinyInteger('points_earned');
            $table->timestamps();
            $table->unique(['task_id', 'completion_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_completions');
    }
};
