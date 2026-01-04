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

class ReturnFromNotaryDocument extends Page implements HasForms
{
    use InteractsWithRecord;
    use InteractsWithForms;

    protected static string $resource = DocumentResource::class;
    protected static string $view = 'filament.resources.document-resource.pages.return-from-notary-document';

    public ?array $data = [];

    public function mount($record)
    {
        $this->record = Document::findOrFail($record);

        abort_unless(
            $this->record->status === 'at_notary',
            403,
            'Dokumen tidak berada di Notaris.'
        );

        $this->form->fill();
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

                                return collect([
                                    'Jenis: ' . ($doc->document_type?->name ?? '-'),
                                    'Nomor: ' . ($doc->document_number ?? '-'),
                                ])->implode(' | ');
                            }),

                        Forms\Components\Placeholder::make('notary')
                            ->label('Notaris')
                            ->content(
                                fn() =>
                                $this->record->notary?->name ?? '-'
                            ),

                        Forms\Components\Placeholder::make('sla')
                            ->label('Target SLA')
                            ->content(
                                fn() =>
                                optional($this->record->expected_return_at)
                                    ? $this->record->expected_return_at->format('d-m-Y')
                                    : '-'
                            ),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pengembalian dari Notaris')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->schema([

                        Forms\Components\DatePicker::make('returned_at')
                            ->label('Tanggal Diterima Kembali')
                            ->native(false)
                            ->required(),

                        Forms\Components\Select::make('storage_id')
                            ->label('Simpan ke Box')
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
            ->apply($this->record, 'notary_return', $data);

        Notification::make()
            ->title('Dokumen kembali dari Notaris')
            ->success()
            ->send();

        return redirect(DocumentResource::getUrl('index'));
    }
}
