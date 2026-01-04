<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use App\Models\Document;
use App\Services\DocumentWorkflowService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;

class BorrowDocument extends Page implements HasForms
{
    use InteractsWithRecord;
    use InteractsWithForms;

    protected static string $resource = DocumentResource::class;

    protected static string $view =
    'filament.resources.document-resource.pages.borrow-document';

    // ⛔ HAPUS VIEW, BIARKAN FILAMENT HANDLE
    protected static ?string $title = 'Pinjam Dokumen';

    public ?array $data = [];

    public function mount($record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_unless(
            $this->record->status === 'in_vault',
            403,
            'Dokumen tidak bisa dipinjam karena status tidak valid.'
        );

        // isi form kalau perlu
        $this->form->fill();
    }

    protected function resolveRecord($key): Document
    {
        return Document::query()->findOrFail($key);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Section::make('Informasi Dokumen')
                    ->icon('heroicon-o-information-circle')
                    ->description('Ringkasan status dan posisi dokumen saat ini.')
                    ->schema([

                        Forms\Components\Placeholder::make('loan_info')
                            ->label('Nomor Kredit & Nama Debitur')
                            ->content(fn() => $this->record->loan
                                ? "{$this->record->loan->loan_number} — {$this->record->loan->debtor_name}"
                                : '-'),

                        Forms\Components\Placeholder::make('storage_info')
                            ->label('Lokasi Vault Saat Ini')
                            ->content(fn() => $this->record->storage
                                ? "{$this->record->storage->name} ({$this->record->storage->code})"
                                : 'Tidak berada di Vault'),
                    ])
                    ->columns(3),

                // =============================
                // FORM UTAMA (tidak diubah)
                // =============================
                Forms\Components\Section::make('Informasi Peminjaman')
                    ->icon('heroicon-o-user')
                    ->description('Isi detail staf / unit yang meminjam dokumen ini.')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('borrower_name')
                                    ->label('Nama Peminjam')
                                    ->placeholder('Contoh: Budi Santosa / Legal Unit')
                                    ->required(),

                                Forms\Components\DatePicker::make('due_date')
                                    ->label('Tanggal Harus Kembali')
                                    ->native(false)
                                    ->closeOnDateSelection()
                                    ->minDate(now())
                                    ->required(),

                                Forms\Components\Textarea::make('reason')
                                    ->label('Keperluan Peminjaman')
                                    ->placeholder('Contoh: Audit internal, Perpanjangan, dll')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])
                    ]),

                Forms\Components\FileUpload::make('receipt')
                    ->label('Upload Berita Acara')->disk('private')
                    ->directory('temp')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(5120)
                    ->columnSpanFull()
                    ->required(),
            ])
            ->columns(2)
            ->model($this->record)
            ->statePath('data');
    }



    public function submit()
    {
        $data = $this->form->getState();

        app(DocumentWorkflowService::class)
            ->apply($this->record, 'borrow', $data);

        Notification::make()
            ->title('Dokumen berhasil dipinjam')
            ->success()
            ->send();

        return redirect(DocumentResource::getUrl('index'));
    }
}
