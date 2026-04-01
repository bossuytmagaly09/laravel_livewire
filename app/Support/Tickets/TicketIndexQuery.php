<?php

namespace App\Support\Tickets;

use App\Models\Ticket;                                        // nodig om ticketquery op te bouwen
use Illuminate\Contracts\Pagination\LengthAwarePaginator;    // returntype voor pagination

class TicketIndexQuery
{
    public function execute(array $filters): LengthAwarePaginator
    {
        return Ticket::query()
            ->with('assignee') // eager load assignee voor tabelweergave
            ->when(($filters['search'] ?? '') !== '', function ($query) use ($filters) {
                $query->where(function ($subQuery) use ($filters) {
                    $subQuery->where('subject', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('description', 'like', '%' . $filters['search'] . '%'); // zoek in onderwerp of beschrijving
                });
            })
            ->when(($filters['status'] ?? '') !== '', function ($query) use ($filters) {
                $query->where('status', $filters['status']); // filter op status
            })
            ->when(($filters['priority'] ?? '') !== '', function ($query) use ($filters) {
                $query->where('priority', $filters['priority']); // filter op prioriteit
            })
            ->when(($filters['workflow'] ?? '') !== '', function ($query) use ($filters) {
                $query->where('workflow_step', $filters['workflow']); // filter op workflow
            })
            ->when(($filters['assigned_user_id'] ?? '') !== '', function ($query) use ($filters) {
                $query->where('assigned_user_id', $filters['assigned_user_id']); // filter op assignee
            })
            ->orderBy(
                $filters['sortField'] ?? 'created_at',  // standaard sorteren op created_at
                $filters['sortDirection'] ?? 'desc',    // standaard nieuwste eerst
            )
            ->paginate($filters['perPage'] ?? 10); // standaard 10 per pagina
    }
}
