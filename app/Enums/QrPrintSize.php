<?php

namespace App\Enums;

/**
 * 宝のQRコード印刷画面の印刷サイズ。
 */
enum QrPrintSize: string
{
    case Large = 'large';
    case Medium = 'medium';
    case Small = 'small';

    public function label(): string
    {
        return match ($this) {
            self::Large => '大',
            self::Medium => '中',
            self::Small => '小',
        };
    }

    /**
     * A4用紙1枚あたりの並び（列×行）。
     */
    public function layout(): string
    {
        return match ($this) {
            self::Large => '3×3',
            self::Medium => '4×4',
            self::Small => '5×6',
        };
    }

    /**
     * A4用紙1枚あたりの枚数。
     */
    public function perSheet(): int
    {
        return match ($this) {
            self::Large => 9,
            self::Medium => 16,
            self::Small => 30,
        };
    }
}
