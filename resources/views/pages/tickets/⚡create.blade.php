<?php

use App\Models\Ticket; // nodig om tickets op te slaan
use Livewire\Attributes\Layout; // om deze page aan de layout te koppelen
use Livewire\Component; // basis Livewire component

new
#[Layout('layouts.app')]
class extends Component
{
    public string $subject = '';      // onderwerp van het ticket
    public string $description = ''; // beschrijving van het probleem
    public string $priority = 'medium'; // standaard prioriteit
    public string $status = 'open';     // standaard status

    protected array $rules = [
        'subject'     => 'required|min:3|max:255',            // onderwerp moet geldig zijn
        'description' => 'required|min:10',                   // beschrijving moet voldoende lang zijn
        'priority'    => 'required|in:low,medium,high',        // alleen geldige prioriteiten
        'status'      => 'required|in:open,in_progress,closed', // alleen geldige statussen
    ];

    protected array $messages = [
        'subject.required'     => 'Het onderwerp is verplicht.',                    // foutmelding voor leeg onderwerp
        'subject.min'          => 'Het onderwerp moet minstens 3 tekens bevatten.', // foutmelding voor te kort onderwerp
        'subject.max'          => 'Het onderwerp mag maximaal 255 tekens bevatten.', // foutmelding voor te lang onderwerp
        'description.required' => 'De beschrijving is verplicht.',                  // foutmelding voor lege beschrijving
        'description.min'      => 'De beschrijving moet minstens 10 tekens bevatten.', // foutmelding voor te korte beschrijving
        'priority.required'    => 'Kies een prioriteit.',                            // foutmelding voor ontbrekende prioriteit
        'priority.in'          => 'De gekozen prioriteit is ongeldig.',              // foutmelding voor ongeldige prioriteit
        'status.required'      => 'Kies een status.',                                // foutmelding voor ontbrekende status
        'status.in'            => 'De gekozen status is ongeldig.',                  // foutmelding voor ongeldige status
    ];

    public function save(): void
    {
        $validated = $this->validate(); // valideer alle velden

        $ticket = Ticket::create([
            ...$validated,               // bestaande gevalideerde velden
            'assigned_user_id' => null,  // NIEUW: nieuwe tickets starten zonder assignee
            'workflow_step'    => 'new', // NIEUW: nieuwe tickets starten in workflow new
        ]);

        $ticket->logActivity('Ticket aangemaakt.'); // log het aanmaken van het ticket

        session()->flash('success', 'Het ticket werd succesvol aangemaakt.'); // toon succesmelding
        $this->reset('subject', 'description'); // reset de tekstvelden
        $this->priority = 'medium'; // herstel standaard prioriteit
        $this->status = 'open';     // herstel standaard status
    }
};
?>

<div class="min-h-screen bg-gray-100 py-10">
    <div class="mx-auto max-w-3xl px-4">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">
                Nieuw support ticket
            </h1>
            <p class="mt-2 text-sm text-gray-600">
                Maak een nieuw ticket aan via een Livewire 4 page component.
            </p>
        </div>

        @if (session()->has('success'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
            <form wire:submit="save" class="space-y-6">
                <div>
                    <label for="subject" class="mb-2 block text-sm font-medium text-gray-700">
                        Onderwerp
                    </label>
                    <input
                        id="subject"
                        type="text"
                        wire:model="subject"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Bijv. Fout bij aanmelden"
                    >
                    @error('subject')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="mb-2 block text-sm font-medium text-gray-700">
                        Beschrijving
                    </label>
                    <textarea
                        id="description"
                        rows="6"
                        wire:model="description"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Beschrijf het probleem zo duidelijk mogelijk..."
                    ></textarea>
                    @error('description')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="priority" class="mb-2 block text-sm font-medium text-gray-700">
                            Prioriteit
                        </label>
                        <select
                            id="priority"
                            wire:model="priority"
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="low">Laag</option>
                            <option value="medium">Normaal</option>
                            <option value="high">Hoog</option>
                        </select>
                        @error('priority')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="status" class="mb-2 block text-sm font-medium text-gray-700">
                            Status
                        </label>
                        <select
                            id="status"
                            wire:model="status"
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="open">Open</option>
                            <option value="in_progress">In behandeling</option>
                            <option value="closed">Gesloten</option>
                        </select>
                        @error('status')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    Nieuwe tickets starten automatisch als <strong>Niet toegewezen</strong> en workflow <strong>Nieuw</strong>. {{-- NIEUW: uitleg over de nieuwe standaardwaarden --}}
                </div>

                <div class="flex items-center gap-4">
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Ticket opslaan
                    </button>
                    <span wire:loading class="text-sm text-gray-500">
                        Bezig met opslaan...
                    </span>
                </div>
            </form>
        </div>
    </div>
</div>
