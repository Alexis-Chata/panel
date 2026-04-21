<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->timestamp('graded_at')->nullable()->after('answered_at');
        });
    }

    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->dropColumn('graded_at');
        });
    }
};

