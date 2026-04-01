<?php

namespace App\Actions\Tickets;

use App\Models\Ticket; // nodig om activiteit aan een ticket te koppelen

class LogTicketActivityAction
{
    public function execute(
        Ticket $ticket,
        string $description,
        string $event = 'ticket_event',
        string $label = 'Ticket activiteit',
    ): void {
        $ticket->activities()->create([
            'event'       => $event,       // technisch type van de activiteit
            'label'       => $label,       // leesbaar label voor UI
            'description' => $description, // beschrijving van de activiteit
        ]);
    }
}
