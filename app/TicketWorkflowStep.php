<?php

namespace App;

enum TicketWorkflowStep: string
{
    case New             = 'new';              // ticket is net aangemaakt
    case Triage          = 'triage';           // eerste beoordeling
    case Investigating   = 'investigating';    // inhoudelijk onderzoek
    case WaitingCustomer = 'waiting_customer'; // wacht op klant
    case Resolved        = 'resolved';         // inhoudelijk opgelost

    public function label(): string
    {
        return match ($this) {
            self::New             => 'Nieuw',          // leesbaar label voor new
            self::Triage          => 'Triage',         // leesbaar label voor triage
            self::Investigating   => 'Onderzoek',      // leesbaar label voor investigating
            self::WaitingCustomer => 'Wacht op klant', // leesbaar label voor waiting_customer
            self::Resolved        => 'Opgelost',       // leesbaar label voor resolved
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::New             => 'bg-slate-100 text-slate-700',    // badgekleur voor new
            self::Triage          => 'bg-indigo-100 text-indigo-700',  // badgekleur voor triage
            self::Investigating   => 'bg-purple-100 text-purple-700',  // badgekleur voor investigating
            self::WaitingCustomer => 'bg-amber-100 text-amber-700',    // badgekleur voor waiting_customer
            self::Resolved        => 'bg-emerald-100 text-emerald-700', // badgekleur voor resolved
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
