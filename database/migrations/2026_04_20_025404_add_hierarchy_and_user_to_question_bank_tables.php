<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_groups', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('question_groups')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->after('is_active')->constrained()->nullOnDelete();
            $table->index(['parent_id', 'user_id']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('question_group_id')->constrained()->nullOnDelete();
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('question_groups', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(['parent_id', 'user_id']);
        });
    }
};
