<?php

namespace App\Enums;

/**
 * イベントの宝探しタイマーの状態。
 */
enum TimerState: string
{
    /** 制限時間を設定していない（いつでも宝箱を開けられる） */
    case None = 'none';

    /** 制限時間はあるが、まだスタートしていない */
    case Waiting = 'waiting';

    /** 宝探し中 */
    case Running = 'running';

    /** 制限時間が過ぎた、または締め切った */
    case Ended = 'ended';

    /**
     * 宝箱を開けられる状態かどうか。
     */
    public function acceptsTreasures(): bool
    {
        return $this === self::None || $this === self::Running;
    }
}
