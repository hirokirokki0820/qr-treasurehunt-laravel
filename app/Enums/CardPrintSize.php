<?php

namespace App\Enums;

/**
 * 参加者カードの印刷サイズ。
 */
enum CardPrintSize: string
{
    case Large = 'large';
    case Medium = 'medium';

    public function label(): string
    {
        return match ($this) {
            self::Large => '大',
            self::Medium => '中',
        };
    }

    /**
     * A4用紙1枚あたりの並び（列×行）。
     */
    public function layout(): string
    {
        return match ($this) {
            self::Large => '2×2',
            self::Medium => '3×3',
        };
    }

    /**
     * A4用紙1枚あたりの枚数。
     */
    public function perSheet(): int
    {
        return match ($this) {
            self::Large => 4,
            self::Medium => 9,
        };
    }
}
