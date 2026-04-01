<?php

namespace App;

enum TicketStatus: string
{
    case Open       = 'open';        // status voor open ticket
    case InProgress = 'in_progress'; // status voor ticket in behandeling
    case Closed     = 'closed';      // status voor afgesloten ticket

    public function label(): string
    {
        return match ($this) {
            self::Open       => 'Open',           // leesbaar label voor open
            self::InProgress => 'In behandeling', // leesbaar label voor in_progress
            self::Closed     => 'Gesloten',       // leesbaar label voor closed
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Open       => 'bg-blue-100 text-blue-700',     // badgekleur voor open
            self::InProgress => 'bg-yellow-100 text-yellow-700', // badgekleur voor in behandeling
            self::Closed     => 'bg-green-100 text-green-700',   // badgekleur voor gesloten
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
