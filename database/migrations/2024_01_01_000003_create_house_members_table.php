<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('house_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('house_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_ready')->default(false);
            $table->integer('score')->default(0);
            $table->timestamps();
            $table->unique(['house_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('house_members');
    }
};
