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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_home_id')->nullable()->after('remember_token')->constrained('homes')->nullOnDelete();
            $table->string('unit', 10)->default('metric')->after('current_home_id');
            $table->string('theme', 10)->default('system')->after('unit');
            $table->boolean('notifications')->default(true)->after('theme');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_home_id');
            $table->dropColumn(['unit', 'theme', 'notifications']);
        });
    }
};
