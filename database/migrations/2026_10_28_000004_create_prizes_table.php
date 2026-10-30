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
        Schema::create('prizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_miss')->default(false);
            $table->string('token', 64)->unique();
            $table->foreignId('participant_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('acquired_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prizes');
    }
};
