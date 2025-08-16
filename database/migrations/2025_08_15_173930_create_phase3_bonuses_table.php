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
        Schema::create('phase3_bonuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained();
            $table->foreignId('question_id')->constrained();
            $table->foreignId('participant_id')->constrained('session_participants');
            $table->unsignedTinyInteger('rank');
            $table->integer('points')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['game_session_id','question_id','rank']);
            $table->unique(['game_session_id','question_id','participant_id']);
            $table->index(['game_session_id','question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phase3_bonuses');
    }
};
