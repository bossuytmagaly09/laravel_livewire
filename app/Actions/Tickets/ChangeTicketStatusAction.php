<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;  // nodig om ticket op te halen
use App\TicketStatus;   // enum voor toegelaten statussen

class ChangeTicketStatusAction
{
    public function __construct(
        protected LogTicketActivityAction $logTicketActivityAction, // centrale logger
    ) {
    }

    public function execute(Ticket $ticket, string $status): Ticket
    {
        $oldStatus = $ticket->status; // oude status bewaren

        $ticket->update([
            'status' => TicketStatus::from($status), // nieuwe status veilig via enum
        ]);

        $ticket->refresh(); // herlaad ticket uit database

        if ($oldStatus !== $ticket->status) {
            $this->logTicketActivityAction->execute(
                $ticket,
                "Status snel gewijzigd van {$oldStatus->value} naar {$ticket->status->value}." // log snelle statuswijziging
            );
        }

        return $ticket->fresh(['assignee']); // geef verse ticketversie terug
    }
}
