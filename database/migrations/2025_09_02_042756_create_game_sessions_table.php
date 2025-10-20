<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique();
            $table->string('title')->nullable();
            $table->enum('phase_mode', ['basic'])->default('basic');
            $table->unsignedTinyInteger('questions_total')->default(10);
            $table->unsignedSmallInteger('timer_default')->default(30);
            $table->enum('student_view_mode', ['solo_alternativas','completo'])->default('completo');
            $table->boolean('is_active')->default(false);
            $table->boolean('is_running')->default(false);
            $table->unsignedSmallInteger('current_q_index')->default(0);
            $table->timestamp('current_q_started_at')->nullable();
            $table->boolean('is_paused')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
    }
};
