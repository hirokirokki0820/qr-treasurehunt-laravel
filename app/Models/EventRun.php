<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 本番を開いた記録（1回ごと）。お試しモードのあとではなく、本番で最初の参加者が入ったときに作る。
 * 片付けてからまた参加者が入ると、次の1回になる。
 */
#[Fillable(['user_id', 'event_id', 'started_at', 'peak_participants'])]
class EventRun extends Model
{
    /**
     * 参加者が入ったことを記録する。本番の最初の1人なら新しい回を作り、そうでなければ今の回の最大人数を更新する。
     */
    public static function recordJoin(Event $event): void
    {
        if ($event->trial_mode) {
            return;
        }

        $count = $event->participants()->count();

        if ($count === 1) {
            static::query()->create([
                'user_id' => $event->user_id,
                'event_id' => $event->id,
                'started_at' => now(),
                'peak_participants' => 1,
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
