<?php

namespace App\Enums;

/**
 * 参加用QRコードの印刷サイズ。
 */
enum JoinQrPrintSize: string
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

    public function description(): string
    {
        return match ($this) {
            self::Large => 'A4いっぱい',
            self::Medium => 'A4中央に配置',
            self::Small => 'A4横に2枚',
        };
    }

    /**
     * A4用紙の向き（CSS の @page size に使う値）。
     */
    public function orientation(): string
    {
        return $this === self::Small ? 'landscape' : 'portrait';
    }

    /**
     * 1枚の用紙に並べる枚数。
     */
    public function copiesPerSheet(): int
    {
        return $this === self::Small ? 2 : 1;
    }
}
