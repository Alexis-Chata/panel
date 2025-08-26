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
            $table->string('title', 120);
            $table->enum('status', ['draft','lobby','phase1','phase2','phase3','results','finished'])->default('draft');
            $table->unsignedTinyInteger('current_phase')->default(0);
            $table->unsignedTinyInteger('phase1_count')->default(10);
            $table->unsignedTinyInteger('phase2_count')->default(3);
            $table->unsignedTinyInteger('phase3_count')->default(10);
            $table->json('settings_json')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->dateTime('phase_ends_at')->nullable()->index();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index('status');
            $table->index('current_phase');
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
