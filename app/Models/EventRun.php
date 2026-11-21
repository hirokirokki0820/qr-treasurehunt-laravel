<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 本番を開いた記録（1回ごと）。本番で参加者が4人目になったときに作る（3人までは数えない。お試しモードも数えない）。
 * 片付けてからまた4人目が入ると、次の1回になる。有料プランを始めたら、無料の回数（年5回）はこの記録で数える。
 */
#[Fillable(['user_id', 'event_id', 'started_at', 'peak_participants'])]
class EventRun extends Model
{
    /**
     * 本番の1回として数え始める参加者の人数（4人目）。3人までは、お試しと取り違えたときのために数えない。
     */
    public const COUNTS_FROM = 4;

    /**
     * 参加者が入ったことを記録する。本番の4人目なら新しい回を作り、5人目からは今の回の最大人数を更新する。
     */
    public static function recordJoin(Event $event): void
    {
        if ($event->trial_mode) {
            return;
        }

        $count = $event->participants()->count();

        if ($count < self::COUNTS_FROM) {
            return;
        }

        if ($count === self::COUNTS_FROM) {
            static::query()->create([
                'user_id' => $event->user_id,
                'event_id' => $event->id,
                'started_at' => now(),
                'peak_participants' => $count,
            ]);

            return;
        }

        // この仕組みを入れる前から続いている回は記録がないので、何もしない
        static::query()
            ->where('event_id', $event->id)
            ->where('peak_participants', '<', $count)
            ->latest('id')
            ->limit(1)
            ->get()
            ->each(fn (EventRun $run) => $run->update(['peak_participants' => $count]));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'peak_participants' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
