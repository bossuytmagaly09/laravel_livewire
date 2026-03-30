<?php

use App\Models\Ticket;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component {
    public Ticket $ticket; // automatisch via route binding

    public string $status;
    public string $priority;

    public function mount(Ticket $ticket): void
    {
        $this->ticket = $ticket;

        // initial state syncen met database
        $this->status = $ticket->status;
        $this->priority = $ticket->priority;
    }

    public function updateStatus(): void
    {
        $this->validate([
            'status' => 'required|in:open,in_progress,closed',
        ]);

        $this->ticket->update([
            'status' => $this->status,
        ]);

        session()->flash('success', 'Status succesvol aangepast.');
    }

    public function updatePriority(): void
    {
        $this->validate([
            'priority' => 'required|in:low,medium,high',
        ]);

        $this->ticket->update([
            'priority' => $this->priority,
        ]);

        session()->flash('success', 'Prioriteit succesvol aangepast.');
    }
};

?>
<div class="min-h-screen bg-gray-100 py-10">
    <div class="mx-auto max-w-4xl px-4">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    Ticket #{{ $ticket->id }}
                </h1>
                <p class="text-sm text-gray-600 mt-1">
                    Detailpagina van een support ticket
                </p>
            </div>
            <a href="{{ route('tickets.index') }}"
               class="text-sm text-blue-600 hover:underline">
                ← Terug naar overzicht
            </a>
        </div>
        @if (session()->has('success'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="space-y-6">

            <!-- BASIS INFO-->
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h2 class="text-lg font-semibold mb-4">Informatie</h2>
                <div class="space-y-3">
                    <div>
                        <span class="text-sm text-gray-500">Onderwerp</span>
                        <div class="font-semibold text-gray-900">
                            {{ $ticket->subject }}
                        </div>
                    </div>

                    <div>
                        <span class="text-sm text-gray-500">Beschrijving</span>
                        <div class="text-gray-700 mt-1">
                            {{ $ticket->description }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- STATUS-->
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h2 class="text-lg font-semibold mb-4">Status wijzigen</h2>

                <div class="flex gap-4 items-center">
                    <select wire:model="status"
                            class="rounded-lg border border-gray-300 px-4 py-2">
                        <option value="open">Open</option>
                        <option value="in_progress">In behandeling</option>
                        <option value="closed">Gesloten</option>
                    </select>

                    <button wire:click="updateStatus"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg">
                        Opslaan
                    </button>
                </div>

                @error('status')
                <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                @enderror
            </div>

            <!-- PRIORITEIT-->
            <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
                <h2 class="text-lg font-semibold mb-4">Prioriteit wijzigen</h2>

                <div class="flex gap-4 items-center">
                    <select wire:model="priority"
                            class="rounded-lg border border-gray-300 px-4 py-2">
                        <option value="low">Laag</option>
                        <option value="medium">Normaal</option>
                        <option value="high">Hoog</option>
                    </select>

                    <button wire:click="updatePriority"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg">
                        Opslaan
                    </button>
                </div>
                @error('priority')
                <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>
</div>
