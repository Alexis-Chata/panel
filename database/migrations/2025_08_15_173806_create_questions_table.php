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
            $table->foreignId('question_pool_id')->constrained();
            $table->string('code', 36)->unique();
            $table->enum('type', ['single','multi','boolean','numeric','text'])->default('single');
            $table->text('stem');
            $table->json('media')->nullable();
            $table->unsignedTinyInteger('difficulty')->default(1);
            $table->json('meta')->nullable();
            $table->unsignedSmallInteger('time_limit_seconds')->default(20);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index(['question_pool_id', 'difficulty']);
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
