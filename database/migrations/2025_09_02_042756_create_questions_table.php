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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->longText('statement');
            $table->longText('feedback')->nullable();
            $table->enum('qtype', ['multiple', 'short'])->default('multiple');
            $table->json('meta')->nullable(); // { short_answer: {case_sensitive: false, strip_accents: true, max_distance: 0..3} }
            $table->foreignId('question_group_id')->constrained('question_groups');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
