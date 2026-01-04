<x-filament::page>
    {{ $this->form }}

    <x-filament::button wire:click="submit" class="mt-4">
        Proses Peminjaman
    </x-filament::button>
</x-filament::page>