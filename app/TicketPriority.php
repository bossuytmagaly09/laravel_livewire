<?php

namespace App;

enum TicketPriority: string
{
    case Low    = 'low';    // lage prioriteit
    case Medium = 'medium'; // normale prioriteit
    case High   = 'high';   // hoge prioriteit

    public function label(): string
    {
        return match ($this) {
            self::Low    => 'Laag',   // leesbaar label voor low
            self::Medium => 'Normaal', // leesbaar label voor medium
            self::High   => 'Hoog',   // leesbaar label voor high
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Low    => 'bg-gray-100 text-gray-700',     // badgekleur voor low
            self::Medium => 'bg-orange-100 text-orange-700', // badgekleur voor medium
            self::High   => 'bg-red-100 text-red-700',       // badgekleur voor high
        };
    }

    public static function values(): array
    {
        return array_map(
            fn (self $case) => $case->value, // geef alle ruwe enumwaarden terug
            self::cases()
        );
    }

    public static function options(): array
    {
        return array_map(
            fn (self $case) => [
                'value' => $case->value,   // value voor selectoptie
                'label' => $case->label(), // leesbaar label voor selectoptie
            ],
            self::cases()
        );
    }
}
