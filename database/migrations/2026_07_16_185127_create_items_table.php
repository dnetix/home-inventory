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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('place_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->index();
            $table->unsignedBigInteger('value')->nullable()->comment('minor currency units (cents)');
            $table->unsignedInteger('qty')->default(1);
            $table->unsignedInteger('width')->nullable()->comment('mm');
            $table->unsignedInteger('height')->nullable()->comment('mm');
            $table->unsignedInteger('depth')->nullable()->comment('mm');
            $table->string('note')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();

            $table->fullText(['name', 'note']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
