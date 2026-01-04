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

class SendToNotaryDocument extends Page implements HasForms
{
    use InteractsWithRecord;
    use InteractsWithForms;

    protected static string $resource = DocumentResource::class;
    protected static string $view = 'filament.resources.document-resource.pages.send-to-notary-document';

    public ?array $data = [];

    public function mount($record)
    {
        $this->record = Document::findOrFail($record);

        abort_unless(
            $this->record->status === 'in_vault',
            403,
            'Dokumen hanya bisa dikirim dari Vault.'
        );

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Section::make('Informasi Dokumen')
                    ->icon('heroicon-o-information-circle')
                    ->description('Pastikan dokumen yang akan dikirim ke Notaris sudah sesuai.')
                    ->schema([

                        Forms\Components\Placeholder::make('loan_info')
                            ->label('No Kredit / Debitur')
                            ->content(
                                fn() =>
                                $this->record->loan
                                    ? "{$this->record->loan->loan_number} — {$this->record->loan->debtor_name}"
                                    : '-'
                            ),

                        Forms\Components\Placeholder::make('document_type')
                            ->label('Jenis Dokumen')
                            ->content(
                                fn() =>
                                $this->record->document_type
                                    ? $this->record->document_type->name
                                    : '-'
                            ),

                        Forms\Components\Placeholder::make('document_number')
                            ->label('Nomor Dokumen')
                            ->content(fn() => $this->record->document_number ?? '-'),

                        Forms\Components\Placeholder::make('storage_info')
                            ->label('Lokasi Vault Saat Ini')
                            ->content(
                                fn() =>
                                $this->record->storage
                                    ? "{$this->record->storage->name} ({$this->record->storage->code})"
                                    : 'Tidak berada di Vault'
                            ),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pengiriman ke Notaris')
                    ->schema([
                        Forms\Components\Select::make('notary_id')
                            ->relationship('notary', 'name')
                            ->label('Pilih Notaris')
                            ->searchable()
                            ->required(),

                        Forms\Components\DatePicker::make('sent_to_notary_at')
                            ->label('Tanggal Kirim')
                            ->default(now())
                            ->native(false)
                            ->required(),

                        Forms\Components\DatePicker::make('expected_return_at')
                            ->label('Estimasi Kembali (SLA)')
                            ->native(false)
                            ->required(),

                        Forms\Components\Textarea::make('reason')
                            ->label('Keperluan / Keterangan'),

                        Forms\Components\SpatieMediaLibraryFileUpload::make('receipt')
                            ->label('Upload BAST')
                            ->collection('notary_receipts')
                            ->required()
                            ->openable()
                            ->downloadable()
                    ])
            ])
            ->model($this->record)
            ->statePath('data');
    }


    public function submit()
    {
        $data = $this->form->getState();

        app(DocumentWorkflowService::class)
            ->apply($this->record, 'notary_send', $data);

        Notification::make()
            ->title('Dokumen berhasil dikirim ke Notaris')
            ->success()
            ->send();

        return redirect(DocumentResource::getUrl('index'));
    }
}
