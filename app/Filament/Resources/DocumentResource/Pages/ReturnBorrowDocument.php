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
use Illuminate\Database\Eloquent\Builder;

class ReturnBorrowDocument extends Page implements HasForms
{
    use InteractsWithRecord;
    use InteractsWithForms;

    protected static string $resource = DocumentResource::class;
    protected static string $view = 'filament.resources.document-resource.pages.return-borrow-document';

    public ?array $data = [];

    public function mount($record)
    {
        $this->record = $this->resolveRecord($record);

        abort_unless(
            $this->record->status === 'borrowed',
            403,
            'Dokumen tidak dalam kondisi dipinjam.'
        );

        $this->form->fill();
    }

    protected function resolveRecord($key): Document
    {
        return Document::findOrFail($key);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Section::make('Informasi Dokumen')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Placeholder::make('loan_info')
                            ->label('Nomor Kredit & Debitur')
                            ->content(
                                fn() =>
                                $this->record->loan
                                    ? "{$this->record->loan->loan_number} — {$this->record->loan->debtor_name}"
                                    : '-'
                            ),

                        Forms\Components\Placeholder::make('document_info')
                            ->label('Informasi Dokumen')
                            ->content(function () {
                                $doc = $this->record;

                                if (! $doc) {
                                    return '-';
                                }

                                return collect([
                                    'Jenis: ' . ($doc->document_type?->name ?? '-'),
                                    'Nomor: ' . ($doc->document_number ?? '-'),
                                    $doc->expiry_date
                                        ? 'Kadaluarsa: ' . $doc->expiry_date->format('d-m-Y')
                                        : null,
                                ])->filter()->implode(' | ');
                            }),

                        Forms\Components\Placeholder::make('current_status')
                            ->label('Status Saat Ini')
                            ->content(
                                fn() =>
                                strtoupper($this->record->status)
                            ),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Informasi Peminjaman')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Placeholder::make('borrower')
                            ->label('Nama Peminjam')
                            ->content(
                                fn() =>
                                $this->record->lastBorrowTransaction?->borrower_name ?? '-'
                            ),

                        Forms\Components\Placeholder::make('due')
                            ->label('Harus Kembali')
                            ->content(
                                fn() =>
                                optional($this->record->lastBorrowTransaction?->due_date)
                                    ?->format('d-m-Y') ?? '-'
                            ),

                        Forms\Components\Placeholder::make('reason')
                            ->label('Keperluan')
                            ->content(
                                fn() =>
                                $this->record->lastBorrowTransaction?->reason ?? '-'
                            ),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Pengembalian Dokumen')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->schema([

                        Forms\Components\DatePicker::make('returned_at')
                            ->label('Tanggal Pengembalian')
                            ->native(false)
                            ->required(),

                        Forms\Components\Select::make('storage_id')
                            ->label('Simpan ke Box')
                            ->searchable()
                            ->relationship(
                                name: 'storage',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn(Builder $query) =>
                                $query->where('level', 'box')
                            )
                            ->required(),

                        Forms\Components\FileUpload::make('receipt')
                            ->label('Upload Berita Acara')->disk('private')
                            ->directory('temp')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(5120)
                            ->columnSpanFull()
                            ->required(),
                    ])
                    ->columns(2),
            ])
            ->model($this->record)
            ->statePath('data');
    }


    public function submit()
    {
        $data = $this->form->getState();

        app(DocumentWorkflowService::class)
            ->apply($this->record, 'return', $data);

        Notification::make()
            ->title('Dokumen berhasil dikembalikan ke Vault')
            ->success()
            ->send();

        return redirect(DocumentResource::getUrl('index'));
    }
}
