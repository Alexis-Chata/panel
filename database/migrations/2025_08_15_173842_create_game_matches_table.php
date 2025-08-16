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
        Schema::create('game_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained();
            $table->foreignId('player1_participant_id')->constrained('session_participants');
            $table->foreignId('player2_participant_id')->nullable()->constrained('session_participants')->nullOnDelete();
            $table->enum('status', ['pending', 'active', 'finished', 'cancelled'])->default('pending');
            $table->foreignId('winner_participant_id')->nullable()->constrained('session_participants')->nullOnDelete();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['game_session_id', 'status']);
            $table->unique(['game_session_id', 'player1_participant_id', 'player2_participant_id'], 'gm_session_p1_p2_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_matches');
    }
};
