<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_executions', function (Blueprint $table) {
            $table->string('error_type', 50)->nullable()->after('last_error');
            $table->unsignedInteger('retry_count')->default(0)->after('error_type');
        });
    }

    public function down(): void
    {
        Schema::table('ai_executions', function (Blueprint $table) {
            $table->dropColumn(['error_type', 'retry_count']);
        });
    }
};
