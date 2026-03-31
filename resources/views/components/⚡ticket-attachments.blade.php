<?php // start van de single-file component

use App\Models\Ticket;

// nodig om het ticket als property te ontvangen
use Livewire\Attributes\Computed;

// nodig voor de computed attachments lijst
use Livewire\Component;

// basis Livewire component
use Livewire\WithFileUploads;

// nodig voor Livewire uploads
use Illuminate\Support\Facades\Storage;

// nodig om bestanden te verwijderen

new
class extends Component {
    use WithFileUploads;

    // activeer bestandsuploads in deze component

    public Ticket $ticket; // het ticket dat vanuit de parent wordt doorgegeven

    public $file = null; // tijdelijk geüpload bestand in component-state

    public function save(): void // sla een nieuw bestand op
    {
        $validated = $this->validate( // valideer de upload
            [
                'file' =>
                    'required|file|max:5120|mimes:jpg,jpeg,png,pdf,txt,log,doc,docx', // max 5 MB en alleen toegelaten types
            ],
            [
                'file.required' => 'Kies een bestand om te uploaden.', // foutmelding voor ontbrekend bestand
                'file.file' => 'De gekozen upload is ongeldig.', // foutmelding als het geen geldig bestand is
                'file.max' => 'Het bestand mag maximaal 5 MB groot zijn.', // foutmelding voor te groot bestand
                'file.mimes' => 'Alleen jpg, jpeg, png, pdf, txt, log, doc en docx zijn toegelaten.', // foutmelding voor ongeldig bestandstype
            ]
        );
        $uploadedFile = $validated['file']; // haal het gevalideerde bestand op
        $path = $uploadedFile->store('ticket-attachments', 'public'); // sla het bestand op in storage/app/public/ticket-attachments
        $this->ticket->attachments()->create([ // maak via de relatie een nieuw attachmentrecord aan
            'original_name' => $uploadedFile->getClientOriginalName(), //bewaar originele bestandsnaam
            'file_path' => $path, // bewaar opslagpad
            'mime_type' => $uploadedFile->getMimeType(), // bewaar mime type
            'file_size' => $uploadedFile->getSize(), // bewaar bestandsgrootte
        ]);
        $this->reset('file'); // reset het tijdelijke uploadveld

        session()->flash('attachments_success', 'Het bestand werd succesvol geüpload.'); // toon succesfeedback binnen attachmentscomponent
    }

    public function delete(int $attachmentId): void // verwijder een bestaand bestand
    {
        $attachment = $this->ticket->attachments()->find($attachmentId); //zoek het attachment binnen dit ticket

        if (!$attachment) { // stop als het bestand niet bestaat of niet bij dit ticket hoort
            return; // defensieve controle
        }

        if (Storage::disk('public')->exists($attachment->file_path)) { // check of het fysieke bestand echt bestaat
            Storage::disk('public')->delete($attachment->file_path); // verwijder het fysieke bestand van disk
        }
        $attachment->delete(); // verwijder het database record

        session()->flash('attachments_success', 'Het bestand werd succesvolverwijderd.'); // toon succesfeedback binnen attachmentscomponent
    }

    #[Computed] // maak van deze methode een computed property
    public function attachments() // lijst van attachments voor dit ticket
    {
        return $this->ticket->attachments() // start vanuit de relatie van het ticket
        ->latest() // nieuwste bestanden eerst tonen
        ->get(); // haal alle attachments op
    }
}
?>


<div class="mt-6 space-y-6"> {{-- wrapper rond het volledige attachments blok--}}
    @if (session()->has('attachments_success'))
        {{-- toon succesfeedback na save of delete--}}
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"> {{-- succesmelding--}}
            {{ session('attachments_success') }} {{-- flash message tonen--}}
        </div>
    @endif
    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200"> {{-- kaart met uploadformulier--}}
        <h2 class="mb-4 text-lg font-semibold text-gray-900"> {{-- titel van het uploadblok--}}
            Bestanden en bijlagen {{-- titeltekst--}}
        </h2>
        <form wire:submit="save" class="space-y-4"> {{-- formulier dat save() uitvoert zonder reload--}}
            <div> {{-- blok voor bestandskeuze--}}
                <label for="file" class="mb-2 block text-sm font-medium text gray-700"> {{-- label voor uploadveld--}}
                    Bestand kiezen {{-- labeltekst--}}
                </label>
                <input
                    id="file"
                    type="file"
                    wire:model="file"
                    class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm shadow-sm file:mr-4 file:rounded-md file:border-0
                        file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold
                        file:text-blue-700 hover:file:bg-blue-100"
                > {{-- file input gekoppeld aan file property--}}
                @error('file') {{-- foutmelding voor upload--}}
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p> {{-- fouttekst--}}
                @enderror
            </div>
            <div wire:loading wire:target="file" class="text-sm text-gray-500"> {{-- feedback tijdens tijdelijk uploaden naar Livewire--}}
                Bestand wordt voorbereid... {{-- loading tekst--}}
            </div>
            @if ($file)
                {{-- toon preview-info zodra gebruiker een bestand gekozen heeft--}}
                <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800"> {{-- info box--}}
                    Klaar om te uploaden:
                    {{ $file->getClientOriginalName() }} {{-- toon gekozen bestandsnaam--}}
                </div>
            @endif
            <div class="flex items-center gap-4"> {{-- acties onder uploadformulier--}}
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save,file"
                    class="inline-flex items-center rounded-lg bg-blue-600
                        px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                > {{-- uploadknop--}}
                    Bestand uploaden {{-- knoptekst--}}
                </button>
                <span wire:loading wire:target="save" class="text-sm text-gray-500"> {{-- loading feedback tijdens save--}}
Bezig met uploaden... {{-- loading tekst--}}
</span>
            </div>
        </form>
    </div>
    <div
        class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200"> {{-- kaart met lijst van bestaande bestanden--}}
        <div class="mb-4 flex items-center justify-between"> {{-- bovenste rij van de lijst--}}
            <h2 class="text-lg font-semibold text-gray-900"> {{-- titel van de lijst--}}
                Geüploade bestanden {{-- titeltekst--}}
            </h2>
            <span class="text-sm text-gray-500"> {{-- teller rechts--}}
                {{ $this->attachments->count() }} item(s) {{-- aantal attachments--}}
</span>
        </div>
        <div class="space-y-4"> {{-- lijst met attachments--}}
            @forelse ($this->attachments as $attachment)
                {{-- loop over alle attachments--}}
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4"> {{-- kaart van één attachment--}}
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between"> {{-- bovenste structuur van het item--}}
                        <div class="min-w-0 flex-1"> {{-- linkerdeel met bestandsinfo--}}
                            <div class="flex flex-wrap items-center gap-3">
                                {{-- badges en datum--}}
                                bestand een afbeelding is--}}
                                @if ($attachment->isImage()){{-- als het bestand een afbeelding is --}}
                                   <span class="inline-flex rounded-full bg
                                   purple-100 px-3 py-1 text-xs font-semibold text-purple-700"> {{-- badge voor afbeelding--}}
                                    Afbeelding {{-- badge tekst--}}
                                    </span>
                                @else
                                    {{-- voor niet-afbeeldingen--}}
                                    <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700"> {{-- neutrale badge--}}
Bestand {{-- badge tekst--}}
</span>
                                @endif
                                <span class="text-xs text-gray-500"> {{-- created_at --}}
                                    {{ $attachment->created_at->format('d/m/Y H:i') }} {{-- datum van upload--}}
</span>
                            </div>
                            141
                            <div class="mt-3 break-all text-sm font-semibold text-gray-900"> {{-- originele bestandsnaam--}}
                                {{ $attachment->original_name }} {{-- toon originele naam--}}
                            </div>
                            <div class="mt-1 text-sm text-gray-500"> {{-- extra bestandsinfo --}}
                                {{ $attachment->mime_type ?? 'Onbekend
                                type' }} • {{ $attachment->formattedFileSize() }} {{-- mime type en leesbare bestandsgrootte--}}
                            </div>
                            <div class="mt-3"> {{-- link naar bestand--}}
                                <a
                                    href="{{ $attachment->url() }}"
                                    target="_blank"
                                    class="inline-flex items-center rounded-lg border border-blue-300 bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700 transition hover:bg-blue-100"
                                > {{-- knop/link om bestand te openen--}}
                                    Open bestand {{-- linktekst--}}
                                </a>
                            </div>
                        </div>
                        <div class="flex items-center gap-3"> {{-- rechterdeel met actieknoppen --}}
                            <button
                                type="button"
                                wire:click="delete({{ $attachment->id }})"
                                wire:confirm="Weet je zeker dat je dit bestand wilt verwijderen?"
                                wire:loading.attr="disabled"
                                wire:target="delete({{ $attachment->id }})"
                                class="inline-flex items-center rounded-lg
                                    bg-red-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition
                                    hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                            > {{-- deleteknop--}}
                                Verwijderen {{-- knoptekst--}}
                            </button>
                        </div>
                    </div>
                    @if ($attachment->isImage())
                        {{-- toon preview als het een afbeelding is--}}
                        <div class="mt-4"> {{-- wrapper rond preview--}}
                            <img
                                src="{{ $attachment->url() }}"
                                alt="{{ $attachment->original_name }}"
                                class="max-h-64 rounded-xl border border-gray-200 object-contain"
                            > {{-- afbeeldingspreview--}}
                        </div>
                    @endif
                    142
                </div>
            @empty
                {{-- als er nog geen bestanden zijn--}}
                <div class="rounded-xl border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500"> {{-- empty state--}}
                    Er zijn nog geen bestanden geüpload voor dit ticket. {{-- lege melding --}}
                </div>
            @endforelse
        </div>
    </div>
</div>
