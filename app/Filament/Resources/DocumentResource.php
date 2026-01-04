<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Filament\Resources\DocumentResource\RelationManagers;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Storage;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Services\DocumentWorkflowService;
use Filament\Notifications\Notification;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Manajemen Berkas';
    protected static ?string $recordTitleAttribute = 'document_number';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)->schema([
                    // KOLOM KIRI: Identitas & Tipe
                    Forms\Components\Group::make()->schema([
                        Forms\Components\Section::make('Identitas Dokumen')
                            ->schema([
                                Forms\Components\Select::make('loan_id')
                                    ->label('Nasabah / No. Kredit')
                                    ->relationship(
                                        name: 'loan',
                                        titleAttribute: 'loan_number',
                                        modifyQueryUsing: fn(Builder $query) => $query->with(['loan_type', 'branch'])
                                    )
                                    ->getOptionLabelFromRecordUsing(
                                        fn($record) =>
                                        "{$record->loan_number} - {$record->debtor_name} " .
                                            "({$record->loan_type?->code} / {$record->loan_type?->division})"
                                    )
                                    ->searchable(['loan_number', 'debtor_name'])
                                    ->required()
                                    ->live() // WAJIB: Agar tombol muncul seketika setelah nasabah dipilih
                                    ->preload()
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('view_loan_details')
                                            ->icon('heroicon-m-eye')
                                            ->tooltip('Lihat Detail Kredit')
                                            ->color('info')
                                            ->hidden(fn($get) => ! $get('loan_id'))
                                            ->modalHeading('Detail Fasilitas Kredit')
                                            ->modalSubmitAction(false)
                                            ->modalCancelActionLabel('Tutup')
                                            // Gunakan fillForm untuk menarik data
                                            ->fillForm(function ($get) {
                                                $loan = \App\Models\Loan::with(['loan_type', 'branch'])->find($get('loan_id'));

                                                if (!$loan) return [];

                                                return [
                                                    'loan_number_display' => $loan->loan_number,
                                                    'debtor_name_display' => $loan->debtor_name,
                                                    'plafond_display' => "Rp " . number_format($loan->plafond, 0, ',', '.'),
                                                    'status_display' => strtoupper($loan->status),
                                                    'branch_display' => $loan->branch?->name,
                                                    'type_display' => $loan->loan_type?->description,
                                                ];
                                            })
                                            ->form([
                                                Forms\Components\Grid::make(2)->schema([
                                                    Forms\Components\TextInput::make('loan_number_display')
                                                        ->label('Nomor Kontrak')
                                                        ->disabled(),
                                                    Forms\Components\TextInput::make('debtor_name_display')
                                                        ->label('Nama Nasabah')
                                                        ->disabled(),
                                                    Forms\Components\TextInput::make('plafond_display')
                                                        ->label('Plafond')
                                                        ->disabled(),
                                                    Forms\Components\TextInput::make('status_display')
                                                        ->label('Status')
                                                        ->disabled(),
                                                    Forms\Components\TextInput::make('branch_display')
                                                        ->label('Kantor Cabang')
                                                        ->disabled(),
                                                    Forms\Components\TextInput::make('type_display')
                                                        ->label('Jenis Kredit')
                                                        ->disabled(),
                                                ])
                                            ])
                                    ),
                                Forms\Components\Select::make('document_type_id')
                                    ->label('Jenis Dokumen')
                                    ->relationship('document_type', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live(),

                                Forms\Components\TextInput::make('document_number')
                                    ->label('Nomor Dokumen')
                                    ->required()
                                    ->rule(function ($get, $record) {
                                        return \Illuminate\Validation\Rule::unique('documents', 'document_number')
                                            ->where(
                                                fn($query) =>
                                                $query
                                                    ->where('loan_id', $get('loan_id'))
                                                    ->where('document_type_id', $get('document_type_id'))
                                            )
                                            ->ignore($record?->id);
                                    })
                                    ->validationMessages([
                                        'required' => 'Nomor dokumen wajib diisi.',
                                        'unique' => 'Nomor dokumen ini sudah ada untuk kredit & jenis dokumen tersebut.',
                                    ]),
                                Forms\Components\DatePicker::make('expiry_date')
                                    ->label('Tanggal Kadaluarsa')
                                    // Hanya wajib jika Tipe Dokumen memang memiliki fitur expiry
                                    ->required(fn($get) => DocumentType::find($get('document_type_id'))?->has_expiry ?? false)
                                    ->visible(fn($get) => DocumentType::find($get('document_type_id'))?->has_expiry ?? false)
                                    ->native(false),

                                Forms\Components\Select::make('status')
                                    ->label('Status Dokumen')
                                    ->options(Document::getLimitedStatuses([
                                        'in_vault',
                                        'at_notary',
                                    ]))
                                    ->default('in_vault')
                                    ->required()
                                    ->live()
                                    ->disabled(fn($context) => $context === 'edit')
                                    ->dehydrated()
                                    // Jika status diubah, bersihkan data notaris/storage yang tidak relevan
                                    ->afterStateUpdated(function ($state, callable $set, $record, callable $get, $livewire) {
                                        // 1. Logika pembersihan data jika bukan ke notaris
                                        if ($state !== 'at_notary') {
                                            $set('notary_id', null);
                                            $set('sent_to_notary_at', null);
                                            $set('expected_return_at', null);
                                        }
                                    }),
                            ]),
                    ])->columnSpan(2),

                    // KOLOM KANAN: Kontrol & File
                    Forms\Components\Group::make()->schema([
                        Forms\Components\Section::make('Penyimpanan Fisik')
                            ->disabled(fn($get) => $get('status') !== 'in_vault')
                            ->schema([
                                Forms\Components\Select::make('storage_id')
                                    ->label('Posisi Box/Rak')
                                    ->relationship('storage', 'name', fn($query) => $query->where('level', 'box'))
                                    ->getOptionLabelFromRecordUsing(fn(Storage $record) => "{$record->name} ({$record->code})")
                                    ->searchable()
                                    ->preload()
                                    ->disabled(fn($record) => $record && $record->status !== 'in_vault')
                                    // Hanya wajib jika statusnya 'in_vault'
                                    ->required(fn($get) => $get('status') === 'in_vault')
                                    ->placeholder('Pilih Box di Vault'),
                            ]),

                        Forms\Components\Section::make('Digital Scan')
                            ->schema([
                                Forms\Components\SpatieMediaLibraryFileUpload::make('file')
                                    ->collection('document_scans')
                                    ->label('Upload PDF')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->maxSize(10240)
                                    ->downloadable()
                                    ->openable(),
                            ]),
                    ])->columnSpan(1),
                ]),

                // SECTION BAWAH: Kondisional Notaris
                Forms\Components\Section::make('Pelacakan Notaris (SLA)')
                    ->description('Hanya diisi jika dokumen sedang diproses di Notaris Rekanan.')
                    ->visible(fn($get) => $get('status') === 'at_notary')
                    ->schema([

                        Forms\Components\Grid::make([
                            'default' => 1,
                            'lg' => 2, // Menggunakan responsif grid
                        ])->schema([
                            // Grup Kiri: Detail Pengiriman
                            Forms\Components\Group::make([
                                Forms\Components\Select::make('notary_id')
                                    ->label('Nama Notaris')
                                    ->relationship('notary', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(
                                        fn($get, $context) =>
                                        $get('status') === 'at_notary' && $context === 'create'
                                    )
                                    ->disabled(fn($context) => $context === 'edit')
                                    ->columnSpanFull(),

                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\DatePicker::make('sent_to_notary_at')
                                        ->label('Tanggal Kirim')
                                        ->default(now())
                                        ->native(false)
                                        ->required(
                                            fn($get, $context) =>
                                            $get('status') === 'at_notary' && $context === 'create'
                                        )
                                        ->disabled(fn($context) => $context === 'edit'),

                                    Forms\Components\DatePicker::make('expected_return_at')
                                        ->label('Estimasi Kembali (SLA)')
                                        ->native(false)
                                        ->prefix('SLA') // Menambah prefix untuk kejelasan
                                        ->required(
                                            fn($get, $context) =>
                                            $get('status') === 'at_notary' && $context === 'create'
                                        )
                                        ->disabled(fn($context) => $context === 'edit'),
                                ]),
                            ])->columnSpan(2),

                            // Grup Kanan: Upload BAST (Hanya saat Create)
                            Forms\Components\Group::make([
                                Forms\Components\SpatieMediaLibraryFileUpload::make('initial_notary_receipt')
                                    ->label('Tanda Terima (BAST)')
                                    ->disk('private')
                                    ->directory('notary-receipts')
                                    ->imageEditor() // Fitur tambahan jika ingin crop/rotate scan
                                    ->openable()
                                    ->downloadable()
                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                    ->maxSize(5120) // Limit 5MB
                                    ->helperText('Format PDF atau Gambar. Maksimal 5MB.')
                                    ->required(fn($get, $context) => $get('status') === 'at_notary' && $context === 'create')
                                    ->helperText('Wajib lampirkan scan tanda terima.')
                                    ->disabled(fn($context) => $context === 'edit')
                                    // Membuat box upload memenuhi tinggi kolom sebelah kiri agar simetris
                                    ->extraAttributes(['class' => 'h-full']),
                            ])->columnSpan(1),

                            // Tambahan Info Jika di mode Edit agar user tidak bingung kenapa disabled
                            Forms\Components\Placeholder::make('edit_info')
                                ->label('')
                                ->content('Informasi Notaris hanya dapat diubah melalui tombol Mutasi/Action.')
                                ->visible(fn($context) => $context === 'edit')
                                ->columnSpanFull(),
                        ]),
                    ]),

                Forms\Components\Section::make('Metadata Tambahan')
                    ->collapsed(false)
                    ->schema([
                        Forms\Components\KeyValue::make('legal_metadata')
                            ->disabled(fn($operation) => $operation === 'edit')
                            ->label('Detail Spesifik Dokumen')
                            ->keyLabel('Atribut (Contoh: Luas Tanah)')
                            ->valueLabel('Nilai (Contoh: 150m2)'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('loan.loan_number')
                    ->label('No. Kredit')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Document $record): string => $record->loan?->debtor_name ?? 'Nasabah Tidak Ditemukan'),

                Tables\Columns\TextColumn::make('document_type.name')
                    ->label('Jenis Berkas')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('document_number')
                    ->label('Nomor Dokumen')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'in_vault' => 'success',
                        'at_notary' => 'info',
                        'borrowed' => 'warning',
                        'released' => 'gray',
                        default => 'gray',
                    })
                    ->icon(
                        fn(Document $record): ?string => ($record->status === 'at_notary' && optional($record->expected_return_at)->isPast())
                            ? 'heroicon-m-clock' : null
                    ),

                Tables\Columns\TextColumn::make('storage.name')
                    ->label('Lokasi Fisik')
                    ->description(fn(Document $record): string => $record->storage?->code ?? '-')
                    ->placeholder('Tidak di Vault')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Kadaluarsa')
                    ->date('d/m/Y')
                    ->color(fn($state) => ($state && Carbon::parse($state)->isPast()) ? 'danger' : 'gray')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(Document::getStatuses()),

                Tables\Filters\TernaryFilter::make('has_file')
                    ->label('Memiliki Scan PDF')
                    ->queries(
                        true: fn(Builder $query) => $query->whereHas('media', fn($q) => $q->where('collection_name', 'document_scans')),
                        false: fn(Builder $query) => $query->whereDoesntHave('media', fn($q) => $q->where('collection_name', 'document_scans')),
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),

                    // Custom Action: Pinjam Dokumen
                    Tables\Actions\Action::make('borrow')
                        ->label('Pinjam Internal')
                        ->icon('heroicon-o-user')
                        ->color('warning')
                        ->visible(fn($record) => $record->status === 'in_vault')
                        ->url(fn($record) => DocumentResource::getUrl('borrow', ['record' => $record])),

                    // Custom Action: Kembalikan Dokumen
                    Tables\Actions\Action::make('return_borrow')
                        ->label('Kembalikan Dokumen')
                        ->icon('heroicon-o-archive-box-arrow-down')
                        ->color('success')
                        ->visible(fn($record) => $record->status === 'borrowed')
                        ->url(fn($record) => DocumentResource::getUrl('return-borrow', ['record' => $record])),

                ])
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),

            'borrow' => Pages\BorrowDocument::route('/{record}/borrow'),
            'return-borrow' => Pages\ReturnBorrowDocument::route('/{record}/return-borrow'),
        ];
    }
}
