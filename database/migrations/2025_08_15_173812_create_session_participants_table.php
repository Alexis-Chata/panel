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
        Schema::create('session_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->string('nickname', 60)->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('joined_at')->nullable();
            $table->integer('phase1_score')->default(0);
            $table->integer('phase2_score')->default(0);
            $table->integer('phase3_score')->default(0);
            $table->integer('total_score')->default(0);
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['game_session_id', 'user_id']);
            $table->index(['game_session_id', 'total_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_participants');
    }
};
