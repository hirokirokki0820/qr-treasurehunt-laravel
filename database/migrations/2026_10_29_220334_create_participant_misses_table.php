<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 参加者が開けたハズレの記録。同じハズレは1人1回だけ記録する。
     */
    public function up(): void
    {
        Schema::create('participant_misses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prize_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['participant_id', 'prize_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participant_misses');
    }
};
