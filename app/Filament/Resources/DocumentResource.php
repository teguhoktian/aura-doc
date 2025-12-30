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
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)->schema([
                    // KOLOM KIRI: Identitas & Tipe (Utama)
                    Forms\Components\Group::make()->schema([
                        Forms\Components\Section::make('Identitas Dokumen')
                            ->schema([
                                Forms\Components\Select::make('loan_id')
                                    ->label('Nomor Kontrak Kredit')
                                    ->relationship('loan', 'loan_number')
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Forms\Components\Select::make('document_type_id')
                                    ->label('Jenis Dokumen')
                                    ->relationship('document_type', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live(),

                                Forms\Components\TextInput::make('document_number')
                                    ->label('Nomor Dokumen')
                                    ->required(),

                                Forms\Components\DatePicker::make('expiry_date')
                                    ->label('Tanggal Kadaluarsa')
                                    ->required(fn($get) => DocumentType::find($get('document_type_id'))?->has_expiry ?? false)
                                    ->visible(fn($get) => DocumentType::find($get('document_type_id'))?->has_expiry ?? false)
                                    ->native(false),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'in_vault' => 'Tersimpan di Vault',
                                        'at_notary' => 'Di Notaris',
                                        'borrowed' => 'Dipinjam Internal',
                                        'released' => 'Diserahkan (Lunas)',
                                    ])
                                    ->required()
                                    ->live(),
                            ]),
                    ])->columnSpan(2),

                    // KOLOM KANAN: Lokasi & File (Kontrol)
                    Forms\Components\Group::make()->schema([
                        Forms\Components\Section::make('Penyimpanan Fisik')
                            ->schema([
                                Forms\Components\Select::make('storage_id')
                                    ->label('Posisi Box/Rak')
                                    ->relationship('storage', 'name', fn($query) => $query->where('level', 'box'))
                                    ->getOptionLabelFromRecordUsing(fn(Storage $record) => "{$record->name} ({$record->code})")
                                    ->searchable()
                                    ->preload()
                                    ->required(fn($get) => $get('status') === 'in_vault'),
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

                // SECTION BAWAH: Kondisional Notaris (Hanya muncul jika status at_notary)
                Forms\Components\Section::make('Pelacakan Notaris (SLA)')
                    ->description('Informasi pengiriman berkas ke Notaris Rekanan')
                    ->visible(fn($get) => $get('status') === 'at_notary')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Select::make('notary_id')
                                ->label('Nama Notaris')
                                ->relationship('notary', 'name')
                                ->required()
                                ->searchable(),

                            Forms\Components\DatePicker::make('sent_to_notary_at')
                                ->label('Tanggal Kirim')
                                ->default(now())
                                ->required(),

                            Forms\Components\DatePicker::make('expected_return_at')
                                ->label('Estimasi Kembali (SLA)')
                                ->required(),
                        ]),
                    ]),

                // SECTION BAWAH: Metadata
                Forms\Components\Section::make('Metadata Tambahan')
                    ->collapsed()
                    ->schema([
                        Forms\Components\KeyValue::make('legal_metadata')
                            ->label('Detail Teknis')
                            ->keyLabel('Atribut')
                            ->valueLabel('Keterangan'),
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
                    ->description(fn(Document $record): string => $record->loan?->debtor_name ?? ''),

                Tables\Columns\TextColumn::make('document_type.name')
                    ->label('Tipe')
                    ->badge(),

                Tables\Columns\TextColumn::make('document_number')
                    ->label('Nomor')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'in_vault' => 'success',
                        'at_notary' => 'info',
                        'borrowed' => 'warning',
                        'released' => 'gray',
                        default => 'gray',
                    })
                    // Icon khusus jika overdue di Notaris
                    ->icon(
                        fn(Document $record): ?string => ($record->status === 'at_notary' && optional($record->expected_return_at)->isPast())
                            ? 'heroicon-m-clock' : null
                    ),

                Tables\Columns\TextColumn::make('storage.name')
                    ->label('Lokasi')
                    ->description(fn(Document $record): string => $record->storage?->code ?? 'N/A')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('ED')
                    ->date('d/m/Y')
                    ->color(fn($state) => ($state && Carbon::parse($state)->isPast()) ? 'danger' : 'gray')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'in_vault' => 'Di Vault',
                        'at_notary' => 'Di Notaris',
                        'borrowed' => 'Dipinjam',
                    ]),
                Tables\Filters\Filter::make('overdue_notary')
                    ->label('Overdue di Notaris')
                    ->query(fn(Builder $query) => $query->where('status', 'at_notary')->where('expected_return_at', '<', now())),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),

                    // 1. ACTION PINJAM (INTERNAL)
                    Tables\Actions\Action::make('borrow')
                        ->label('Pinjam (Internal)')
                        ->icon('heroicon-o-user')
                        ->color('warning')
                        ->visible(fn($record) => $record->status === 'in_vault')
                        ->form([
                            Forms\Components\TextInput::make('borrower_name')->label('Nama Peminjam')->required(),
                            Forms\Components\DatePicker::make('due_date')->label('Target Kembali')->required(),
                            Forms\Components\Textarea::make('reason')->label('Alasan Peminjaman'),
                        ])
                        ->action(function (Document $record, array $data) {
                            $record->transactions()->create([
                                'user_id' => auth()->id(),
                                'borrower_name' => $data['borrower_name'],
                                'type' => 'borrow',
                                'transaction_date' => now(),
                                'due_date' => $data['due_date'],
                                'reason' => $data['reason'],
                            ]);
                            $record->update(['status' => 'borrowed']);
                        }),

                    // 2. ACTION KEMBALIKAN KE VAULT
                    Tables\Actions\Action::make('return_to_vault')
                        ->label('Kembalikan ke Vault')
                        ->icon('heroicon-o-arrow-down-on-square')
                        ->color('success')
                        ->visible(fn($record) => in_array($record->status, ['borrowed', 'at_notary']))
                        ->requiresConfirmation()
                        ->action(function (Document $record) {
                            // Tutup transaksi terakhir yang masih open
                            $record->transactions()->whereNull('returned_at')->latest()->first()?->update([
                                'returned_at' => now()
                            ]);

                            $record->update(['status' => 'in_vault']);
                        }),

                    // 3. ACTION SERAHKAN (LUNAS/RELEASE)
                    Tables\Actions\Action::make('release')
                        ->label('Serahkan ke Nasabah (Lunas)')
                        ->icon('heroicon-o-check-badge')
                        ->color('gray')
                        ->visible(fn($record) => $record->status === 'in_vault')
                        ->form([
                            Forms\Components\TextInput::make('borrower_name')->label('Nama Penerima (Nasabah/Ahli Waris)')->required(),
                            Forms\Components\DatePicker::make('transaction_date')->label('Tanggal Penyerahan')->default(now())->required(),
                        ])
                        ->action(function (Document $record, array $data) {
                            $record->transactions()->create([
                                'user_id' => auth()->id(),
                                'borrower_name' => $data['borrower_name'],
                                'type' => 'release',
                                'transaction_date' => $data['transaction_date'],
                            ]);
                            $record->update(['status' => 'released']);
                        }),
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
        ];
    }
}
