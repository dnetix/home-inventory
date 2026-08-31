<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Notes now carry documentation links (`[Title](https://…)`) — one pasted
     * URL alone can approach the old 255-char cap.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('note', 1000)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('note', 255)->nullable()->change();
        });
    }
};
