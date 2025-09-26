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
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_participant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_option_id')->nullable()->constrained('question_options')->nullOnDelete();
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('time_ms')->default(0);
            $table->timestamp('answered_at')->nullable();
            $table->text('text')->nullable();        // lo que escribió el jugador
            $table->unsignedBigInteger('matched_id')->nullable(); // id en question_short_answers
            $table->float('score')->default(0);      // 0..1
            $table->timestamps();
            $table->unique(['session_participant_id', 'session_question_id']);
            $table->index('session_question_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
