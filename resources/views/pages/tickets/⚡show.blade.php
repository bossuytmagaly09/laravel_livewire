<?php // start van de single-file page component

use App\Models\Ticket; // nodig voor route model binding en updates
use Livewire\Attributes\Layout; // om deze page aan de layout te koppelen
use Livewire\Component; // basis Livewire component

new
#[Layout('layouts.app')] // deze page gebruikt resources/views/layouts/app.blade.php
class extends Component
{
    public Ticket $ticket; // het ticket dat via de route binnenkomt

    public string $subject = ''; // onderwerp dat in het formulier leeft

    public string $description = ''; // beschrijving die in het formulier leeft

    public string $status = 'open'; // status in component-state

    public string $priority = 'medium'; // prioriteit in component-state

    public function mount(Ticket $ticket): void // wordt uitgevoerd bij het laden van de pagina
    {
        $this->ticket = $ticket; // sla de route-bound ticket instance op
        $this->subject = $ticket->subject; // vul formulierstate met bestaande data
        $this->description = $ticket->description; // vul formulierstate met bestaande data
        $this->status = $ticket->status; // vul formulierstate met bestaande data
        $this->priority = $ticket->priority; // vul formulierstate met bestaande data
    }

    public function save(): void // bewaar alle ticketwijzigingen samen
    {
        $validated = $this->validate( // valideer alle editvelden
            [
                'subject' => 'required|min:3|max:255', // onderwerp moet geldig zijn
                'description' => 'required|min:10', // beschrijving moet voldoende lang zijn
                'status' => 'required|in:open,in_progress,closed', // alleen geldige statussen
                'priority' => 'required|in:low,medium,high', // alleen geldige prioriteiten
            ],
            [
                'subject.required' => 'Het onderwerp is verplicht.', // foutmelding voor leeg onderwerp
                'subject.min' => 'Het onderwerp moet minstens 3 tekens bevatten.', // foutmelding voor te kort onderwerp
                'subject.max' => 'Het onderwerp mag maximaal 255 tekens bevatten.', // foutmelding voor te lang onderwerp
                'description.required' => 'De beschrijving is verplicht.', // foutmelding voor lege beschrijving
                'description.min' => 'De beschrijving moet minstens 10 tekens bevatten.', // foutmelding voor te korte beschrijving
                'status.required' => 'Kies een status.', // foutmelding voor ontbrekende status
                'status.in' => 'De gekozen status is ongeldig.', // foutmelding voor ongeldige status
                'priority.required' => 'Kies een prioriteit.', // foutmelding voor ontbrekende prioriteit
                'priority.in' => 'De gekozen prioriteit is ongeldig.', // foutmelding voor ongeldige prioriteit
            ]
        );

        $this->ticket->update($validated); // update het ticket in de database

        $this->ticket->refresh(); // laad de nieuwste modelwaarden opnieuw in

        session()->flash('success', 'Het ticket werd succesvol bijgewerkt.'); // toon succesfeedback bovenaan
    }

    public function delete(): mixed // verwijder het huidige ticket
    {
        $this->ticket->delete(); // verwijder het ticket uit de database

        session()->flash('success', 'Het ticket werd succesvol verwijderd.'); // zet een flash message

        return $this->redirect(route('tickets.index')); // ga terug naar het overzicht
    }
};
?>

<div class="min-h-screen bg-gray-100 py-10"> {{-- wrapper van de volledige detailpagina --}}
    <div class="mx-auto max-w-4xl px-4"> {{-- centrale breedtebeperking --}}

        <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between"> {{-- header van de pagina --}}
            <div> {{-- linkerdeel van de header --}}
                <h1 class="text-3xl font-bold text-gray-900"> {{-- hoofdtitel --}}
                    Ticket #{{ $ticket->id }} {{-- toon ticket id --}}
                </h1>
                <p class="mt-1 text-sm text-gray-600"> {{-- subtitel --}}
                    Bewerk dit support ticket via een Livewire 4 detailpagina. {{-- subtiteltekst --}}
                </p>
            </div>
            <div class="flex items-center gap-3"> {{-- acties rechtsboven --}}
                <a
                    href="{{ route('tickets.index') }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                > {{-- terugknop --}}
                    Terug naar overzicht {{-- knoptekst --}}
                </a>
                <button
                    type="button"
                    wire:click="delete"
                    wire:confirm="Weet je zeker dat je dit ticket wilt verwijderen?"
                    wire:loading.attr="disabled"
                    wire:target="delete"
                    class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                > {{-- deleteknop --}}
                    Verwijderen {{-- knoptekst --}}
                </button>
            </div>
        </div>

        @if (session()->has('success')) {{-- toon succesfeedback na save --}}
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"> {{-- success box --}}
            {{ session('success') }} {{-- flash message --}}
        </div>
        @endif

        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200"> {{-- kaart met editformulier --}}
            <form wire:submit="save" class="space-y-6"> {{-- formulier dat save() uitvoert zonder reload --}}

                <div> {{-- blok voor onderwerp --}}
                    <label for="subject" class="mb-2 block text-sm font-medium text-gray-700"> {{-- label onderwerp --}}
                        Onderwerp {{-- labeltekst --}}
                    </label>
                    <input
                        id="subject"
                        type="text"
                        wire:model="subject"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Bijv. Login lukt niet"
                    > {{-- input gekoppeld aan subject --}}
                    @error('subject') {{-- foutmelding voor onderwerp --}}
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p> {{-- fouttekst --}}
                    @enderror
                </div>

                <div> {{-- blok voor beschrijving --}}
                    <label for="description" class="mb-2 block text-sm font-medium text-gray-700"> {{-- label beschrijving --}}
                        Beschrijving {{-- labeltekst --}}
                    </label>
                    <textarea
                        id="description"
                        rows="6"
                        wire:model="description"
                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Beschrijf het probleem zo duidelijk mogelijk..."
                    ></textarea> {{-- textarea gekoppeld aan description --}}
                    @error('description') {{-- foutmelding voor beschrijving --}}
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p> {{-- fouttekst --}}
                    @enderror
                </div>

                <div class="grid gap-6 md:grid-cols-2"> {{-- blok met status en prioriteit --}}

                    <div> {{-- statusblok --}}
                        <label for="status" class="mb-2 block text-sm font-medium text-gray-700"> {{-- label status --}}
                            Status {{-- labeltekst --}}
                        </label>
                        <select
                            id="status"
                            wire:model="status"
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        > {{-- dropdown voor status --}}
                            <option value="open">Open</option> {{-- status open --}}
                            <option value="in_progress">In behandeling</option> {{-- status in behandeling --}}
                            <option value="closed">Gesloten</option> {{-- status gesloten --}}
                        </select>
                        @error('status') {{-- foutmelding voor status --}}
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p> {{-- fouttekst --}}
                        @enderror
                    </div>

                    <div> {{-- prioriteitsblok --}}
                        <label for="priority" class="mb-2 block text-sm font-medium text-gray-700"> {{-- label prioriteit --}}
                            Prioriteit {{-- labeltekst --}}
                        </label>
                        <select
                            id="priority"
                            wire:model="priority"
                            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        > {{-- dropdown voor prioriteit --}}
                            <option value="low">Laag</option> {{-- prioriteit laag --}}
                            <option value="medium">Normaal</option> {{-- prioriteit normaal --}}
                            <option value="high">Hoog</option> {{-- prioriteit hoog --}}
                        </select>
                        @error('priority') {{-- foutmelding voor prioriteit --}}
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p> {{-- fouttekst --}}
                        @enderror
                    </div>

                </div>

                <div class="flex items-center gap-4"> {{-- acties onder formulier --}}
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    > {{-- saveknop --}}
                        Wijzigingen opslaan {{-- knoptekst --}}
                    </button>
                    <span wire:loading wire:target="save" class="text-sm text-gray-500"> {{-- loading feedback --}}
                        Bezig met opslaan... {{-- loading tekst --}}
                    </span>
                </div>

            </form>
        </div>

        <div class="mt-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200"> {{-- metadata blok --}}
            <h2 class="mb-4 text-lg font-semibold text-gray-900"> {{-- titel metadata --}}
                Alleen lezen, metadata {{-- titeltekst --}}
            </h2>
            <div class="grid gap-4 md:grid-cols-2"> {{-- metadata grid --}}
                <div> {{-- created_at blok --}}
                    <span class="block text-sm text-gray-500">Aangemaakt op</span> {{-- label --}}
                    <span class="text-sm font-medium text-gray-800"> {{-- waarde --}}
                        {{ $ticket->created_at->format('d/m/Y H:i') }} {{-- created_at geformatteerd --}}
                    </span>
                </div>
                <div> {{-- updated_at blok --}}
                    <span class="block text-sm text-gray-500">Laatst bijgewerkt op</span> {{-- label --}}
                    <span class="text-sm font-medium text-gray-800"> {{-- waarde --}}
                        {{ $ticket->updated_at->format('d/m/Y H:i') }} {{-- updated_at geformatteerd --}}
                    </span>
                </div>
            </div>
        </div>

        <livewire:ticket-overview-stats :ticket="$ticket" :key="'ticket-overview-stats-' . $ticket->id" /> {{-- nested Livewire component voor live statistieken, luistert naar events van comments en attachments --}}

        <livewire:ticket-comments :ticket="$ticket" :key="'ticket-comments-' . $ticket->id" /> {{-- nested Livewire component voor comments en interne notities --}}

        <livewire:ticket-attachments :ticket="$ticket" :key="'ticket-attachments-' . $ticket->id" /> {{-- nested Livewire component voor bestandsuploads --}}

    </div>
</div>
