<?php

use App\Models\Ticket;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new
class extends Component
{
    public Ticket $ticket;

    #[Computed]
    public function activities()
    {
        return $this->ticket->activities()
            ->latest()
            ->get();
    }

    #[On('comment-created')]
    #[On('comment-deleted')]
    #[On('attachment-created')]
    #[On('attachment-deleted')]
    public function refreshActivities(): void
    {
        unset($this->activities); // NIEUW: zodra een ander component iets wijzigt, maken we de cached computed property leeg zodat de historiek opnieuw opgebouwd wordt

        $this->ticket->refresh(); // NIEUW: het ticketmodel refreshen zorgt ervoor dat de relatie state opnieuw synchroon loopt met de database
    }
};
?>

<div class="mt-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">

    <div class="mb-4 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">
                Activity log
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Overzicht van acties die op dit ticket werden uitgevoerd.
            </p>
        </div>
        <span class="text-sm text-gray-500">
            {{ $this->activities->count() }} item(s)
        </span>
    </div>

    <div class="space-y-4">
        @forelse ($this->activities as $activity)
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $activity->badgeClasses() }}">
                                {{ $activity->label }}
                            </span>
                            <span class="text-xs text-gray-500">
                                {{ $activity->created_at->format('d/m/Y H:i') }}
                            </span>
                        </div>
                        @if ($activity->description)
                            <div class="mt-3 text-sm leading-6 text-gray-700">
                                {{ $activity->description }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500">
                Er is nog geen historiek beschikbaar voor dit ticket.
            </div>
        @endforelse
    </div>

</div>
