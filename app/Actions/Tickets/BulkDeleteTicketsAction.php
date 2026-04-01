<?php

namespace App\Actions\Tickets;

use App\Models\Ticket; // nodig om meerdere tickets te verwijderen

class BulkDeleteTicketsAction
{
    public function execute(array $ticketIds): int
    {
        if (empty($ticketIds)) {
            return 0; // niets geselecteerd, dus niets verwijderd
        }

        return Ticket::query()
            ->whereIn('id', $ticketIds) // neem alleen geselecteerde tickets
            ->delete(); // verwijder alle geselecteerde tickets
    }
}
