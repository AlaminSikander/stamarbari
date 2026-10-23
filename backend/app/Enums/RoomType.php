<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RoomType: string implements HasColor, HasLabel
{
    case Couple = 'couple';
    case DoubleBed = 'double_bed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Couple => 'Couple Room',
            self::DoubleBed => 'Double-Bed Room',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Couple => 'info',
            self::DoubleBed => 'success',
        };
    }

    public function localizedLabel(string $locale = 'en'): string
    {
        if ($locale !== 'bn') {
            return $this->getLabel();
        }

        return match ($this) {
            self::Couple => 'কাপল রুম',
            self::DoubleBed => 'ডাবল বেড রুম',
        };
    }
}
