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
        Schema::create('assigned_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained();
            $table->foreignId('participant_id')->constrained('session_participants');
            $table->foreignId('question_id')->constrained();
            $table->foreignId('game_match_id')->nullable()->constrained('game_matches')->nullOnDelete();
            $table->unsignedTinyInteger('phase');
            $table->unsignedTinyInteger('order')->default(0);
            $table->dateTime('available_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['game_session_id', 'participant_id', 'phase', 'order'], 'aq_session_part_phase_order_idx');
            $table->unique(['participant_id', 'phase', 'question_id', 'game_match_id'], 'aq_part_phase_question_match_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assigned_questions');
    }
};
