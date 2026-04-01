<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;  // nodig om meerdere tickets op te halen
use App\TicketStatus;   // enum voor status

class BulkCloseTicketsAction
{
    public function execute(array $ticketIds): int
    {
        if (empty($ticketIds)) {
            return 0; // niets geselecteerd, dus niets aangepast
        }

        return Ticket::query()
            ->whereIn('id', $ticketIds) // neem alleen geselecteerde tickets
            ->update([
                'status' => TicketStatus::Closed, // zet alle geselecteerde tickets op gesloten
            ]);
    }
}
