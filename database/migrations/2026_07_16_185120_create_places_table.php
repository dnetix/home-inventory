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
        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('places')->restrictOnDelete();
            $table->string('label');
            $table->string('glyph');
            $table->string('description')->nullable();
            $table->unsignedInteger('width')->nullable()->comment('interior mm');
            $table->unsignedInteger('height')->nullable()->comment('interior mm');
            $table->unsignedInteger('depth')->nullable()->comment('interior mm');
            $table->timestamps();

            $table->fullText('label');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('places');
    }
};
