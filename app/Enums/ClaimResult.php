<?php

namespace App\Enums;

/**
 * 宝のQRコードを開けたときの結果。
 */
enum ClaimResult: string
{
    case Won = 'won';
    case AlreadyMine = 'already_mine';
    case TakenByOther = 'taken_by_other';
    case Miss = 'miss';
    case LimitReached = 'limit_reached';
}
