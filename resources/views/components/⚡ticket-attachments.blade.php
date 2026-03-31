<?php

use App\Models\Ticket;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new
class extends Component
{
    use WithFileUploads;

    public Ticket $ticket;

    public $file = null;

    public function save(): void
    {
        $validated = $this->validate(
            [
                'file' => 'required|file|max:5120|mimes:jpg,jpeg,png,pdf,txt,log,doc,docx',
            ],
            [
                'file.required' => 'Kies een bestand om te uploaden.',
                'file.file' => 'De gekozen upload is ongeldig.',
                'file.max' => 'Het bestand mag maximaal 5 MB groot zijn.',
                'file.mimes' => 'Alleen jpg, jpeg, png, pdf, txt, log, doc en docx zijn toegelaten.',
            ]
        );

        $uploadedFile = $validated['file'];

        $path = $uploadedFile->store('ticket-attachments', 'public');

        $attachment = $this->ticket->attachments()->create([
            'original_name' => $uploadedFile->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $uploadedFile->getMimeType(),
            'file_size' => $uploadedFile->getSize(),
        ]);

        $this->ticket->logActivity(
            'attachment_created',
            'Bestand geüpload',
            "Het bestand '{$attachment->original_name}' werd toegevoegd aan dit ticket."
        ); // NIEUW: na een succesvolle upload registreren we ook het exacte bestand in de activity log

        $this->reset('file');

        $this->dispatch('attachment-created', attachmentId: $attachment->id, ticketId: $this->ticket->id);

        session()->flash('attachments_success', 'Het bestand werd succesvol geüpload.');
    }

    public function delete(int $attachmentId): void
    {
        $attachment = $this->ticket->attachments()->find($attachmentId);

        if (! $attachment) {
            return;
        }

        $originalName = $attachment->original_name; // NIEUW: de originele bestandsnaam bewaren we eerst, zodat we die nog kunnen gebruiken nadat het record verwijderd is

        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        $this->ticket->logActivity(
            'attachment_deleted',
            'Bestand verwijderd',
            "Het bestand '{$originalName}' werd verwijderd van dit ticket."
        ); // NIEUW: ook bestandverwijderingen registreren we expliciet in de historiek van het ticket

        $this->dispatch('attachment-deleted', attachmentId: $attachmentId, ticketId: $this->ticket->id);

        session()->flash('attachments_success', 'Het bestand werd succesvol verwijderd.');
    }

    #[Computed]
    public function attachments()
    {
        return $this->ticket->attachments()
            ->latest()
            ->get();
    }
};
?>

<div class="mt-6 space-y-6">

    @if (session()->has('attachments_success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('attachments_success') }}
        </div>
    @endif

    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        <h2 class="mb-4 text-lg font-semibold text-gray-900">
            Bestanden en bijlagen
        </h2>
        <form wire:submit="save" class="space-y-4">

            <div>
                <label for="file" class="mb-2 block text-sm font-medium text-gray-700">
                    Bestand kiezen
                </label>
                <input
                    id="file"
                    type="file"
                    wire:model="file"
                    class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm shadow-sm file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                >
                @error('file')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div wire:loading wire:target="file" class="text-sm text-gray-500">
                Bestand wordt voorbereid...
            </div>

            @if ($file)
                <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    Klaar om te uploaden:
                    {{ $file->getClientOriginalName() }}
                </div>
            @endif

            <div class="flex items-center gap-4">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save,file"
                    class="inline-flex items-center rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Bestand uploaden
                </button>
                <span wire:loading wire:target="save" class="text-sm text-gray-500">
                    Bezig met uploaden...
                </span>
            </div>

        </form>
    </div>

    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">
                Geüploade bestanden
            </h2>
            <span class="text-sm text-gray-500">
                {{ $this->attachments->count() }} item(s)
            </span>
        </div>

        <div class="space-y-4">
            @forelse ($this->attachments as $attachment)
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-3">
                                @if ($attachment->isImage())
                                    <span class="inline-flex rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-700">
                                        Afbeelding
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                                        Bestand
                                    </span>
                                @endif
                                <span class="text-xs text-gray-500">
                                    {{ $attachment->created_at->format('d/m/Y H:i') }}
                                </span>
                            </div>
                            <div class="mt-3 break-all text-sm font-semibold text-gray-900">
                                {{ $attachment->original_name }}
                            </div>
                            <div class="mt-1 text-sm text-gray-500">
                                {{ $attachment->mime_type ?? 'Onbekend type' }} • {{ $attachment->formattedFileSize() }}
                            </div>
                            <div class="mt-3">
                                <a
                                    href="{{ $attachment->url() }}"
                                    target="_blank"
                                    class="inline-flex items-center rounded-lg border border-blue-300 bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700 transition hover:bg-blue-100"
                                >
                                    Open bestand
                                </a>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                wire:click="delete({{ $attachment->id }})"
                                wire:confirm="Weet je zeker dat je dit bestand wilt verwijderen?"
                                wire:loading.attr="disabled"
                                wire:target="delete({{ $attachment->id }})"
                                class="inline-flex items-center rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                Verwijderen
                            </button>
                        </div>
                    </div>

                    @if ($attachment->isImage())
                        <div class="mt-4">
                            <img
                                src="{{ $attachment->url() }}"
                                alt="{{ $attachment->original_name }}"
                                class="max-h-64 rounded-xl border border-gray-200 object-contain"
                            >
                        </div>
                    @endif
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500">
                    Er zijn nog geen bestanden geüpload voor dit ticket.
                </div>
            @endforelse
        </div>
    </div>

</div>
