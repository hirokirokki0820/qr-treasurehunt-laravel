<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 本番を開いた記録（1回ごと）。片付けると参加者は消えるので、開いた回数と人数はここに残す。
 * 無料の範囲（年に何回・何人まで）を決めるための集計に使う。お試しモードは数えない。4人目が入ったときに1回と数える（EventRun::COUNTS_FROM）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('started_at');
            $table->unsignedInteger('peak_participants')->default(1);
            $table->timestamps();

            $table->index(['user_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_runs');
    }
};
