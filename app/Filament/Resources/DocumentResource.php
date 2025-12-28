<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Filament\Resources\DocumentResource\RelationManagers;
use App\Models\Document;
use App\Models\Storage;
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
                Forms\Components\Section::make('Kaitan Kredit')
                    ->description('Pilih nomor kontrak kredit yang berhubungan')
                    ->schema([
                        Forms\Components\Select::make('loan_id')
                            ->relationship('loan', 'loan_number')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),

                Forms\Components\Section::make('Informasi Dokumen')
                    ->schema([
                        Forms\Components\Select::make('document_type_id')
                            ->label('Jenis Dokumen')
                            ->relationship('document_type', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live() // Membuat form merespon perubahan secara real-time
                            ->afterStateUpdated(fn($state, callable $set) => $set('expiry_date', null)),

                        Forms\Components\TextInput::make('document_number')
                            ->label('Nomor Dokumen')
                            ->required(),

                        // Field ini muncul secara dinamis
                        Forms\Components\DatePicker::make('expiry_date')
                            ->label('Tanggal Kadaluarsa')
                            ->placeholder('Pilih tanggal jika ada')
                            ->required(
                                fn($get): bool =>
                                // Wajib diisi jika tipe dokumen terpilih memiliki has_expiry = true
                                \App\Models\DocumentType::find($get('document_type_id'))?->has_expiry ?? false
                            )
                            ->visible(
                                fn($get): bool =>
                                // Hanya muncul jika has_expiry = true
                                \App\Models\DocumentType::find($get('document_type_id'))?->has_expiry ?? false
                            )
                            ->native(false),
                    ])->columns(2),

                Forms\Components\Section::make('Soft Copy (PDF)')
                    ->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make('file')
                            ->collection('document_scans')
                            ->label('Upload Scan Dokumen')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(10240) // 10MB
                            ->downloadable()
                            ->openable(),
                    ]),

                Forms\Components\Section::make('Metadata Tambahan (Opsional)')
                    ->description('Gunakan untuk detail spesifik seperti nama Notaris atau Luas Tanah')
                    ->schema([
                        Forms\Components\KeyValue::make('legal_metadata')
                            ->label('Detail Metadata')
                            ->keyLabel('Nama Properti')
                            ->valueLabel('Nilai')
                            ->reorderable(),
                    ]),

                // Tambahkan Section baru di dalam fungsi form() pada DocumentResource
                Forms\Components\Section::make('Lokasi Fisik')
                    ->description('Tentukan di mana dokumen asli disimpan')
                    ->schema([
                        Forms\Components\Select::make('storage_id')
                            ->label('Pilih Box / Rak')
                            ->relationship('storage', 'name')
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn(Storage $record) => "{$record->name} ({$record->code})"),
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('document_type.name')
                    ->label('Jenis Dokumen')
                    ->badge(),
                Tables\Columns\TextColumn::make('document_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Masa Berlaku')
                    ->date('d/m/Y')
                    ->sortable()
                    // Senior Touch: Warnai merah jika sudah lewat tanggal sekarang
                    ->color(
                        fn($state): string => ($state && \Carbon\Carbon::parse($state)->isPast()) ? 'danger' : 'gray'
                    )
                    ->icon(
                        fn($state): ?string => ($state && \Carbon\Carbon::parse($state)->isPast()) ? 'heroicon-m-exclamation-circle' : null
                    ),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'in_vault' => 'success',
                        'borrowed' => 'warning',
                        'released' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('storage.name')
                    ->label('Lokasi Fisik')
                    ->description(fn(Document $record): string => $record->storage?->code ?? 'Belum ditentukan')
                    ->badge()
                    ->color('warning')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(Document $record): string => match ($record->status) {
                        'in_vault' => 'success',
                        'borrowed' => 'warning',
                        'released' => 'gray',
                    })
                    // Menambahkan icon peringatan jika overdue
                    ->icon(
                        fn(Document $record): string|null =>
                        $record->status === 'borrowed' &&
                            $record->transactions()->whereNull('returned_at')->where('due_date', '<', now())->exists()
                            ? 'heroicon-m-exclamation-triangle'
                            : null
                    ),

                Tables\Columns\TextColumn::make('settled_at')
                    ->label('Tgl Pelunasan')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filter dokumen yang sudah kadaluarsa
                Tables\Filters\Filter::make('expired')
                    ->label('Sudah Kadaluarsa')
                    ->query(fn(Builder $query) => $query->where('expiry_date', '<', now())),

                // Filter dokumen wajib
                Tables\Filters\Filter::make('is_mandatory')
                    ->label('Dokumen Wajib')
                    ->query(fn(Builder $query) => $query->whereHas('document_type', fn($q) => $q->where('is_mandatory', true))),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // Action PINJAM
                Tables\Actions\Action::make('borrow')
                    ->label('Pinjam')
                    ->icon('heroicon-o-book-open')
                    ->color('danger')
                    ->visible(fn(Document $record) => $record->status === 'in_vault')
                    ->form([
                        Forms\Components\TextInput::make('borrower_name')
                            ->label('Nama Peminjam')
                            ->required(),
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Tanggal Kembali')
                            ->required(),
                        Forms\Components\Textarea::make('reason')
                            ->label('Keperluan Pinjam')
                            ->required(),
                    ])
                    ->action(function (Document $record, array $data) {
                        // Buat log transaksi
                        $record->transactions()->create([
                            'user_id' => auth()->id(),
                            'borrower_name' => $data['borrower_name'],
                            'type' => 'borrow',
                            'transaction_date' => now(),
                            'due_date' => $data['due_date'],
                            'reason' => $data['reason'],
                        ]);

                        // Ubah status dokumen
                        $record->update(['status' => 'borrowed']);

                        \Filament\Notifications\Notification::make()
                            ->title('Peminjaman Berhasil')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),

                // Action KEMBALI
                Tables\Actions\Action::make('return')
                    ->label('Kembalikan')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn(Document $record) => $record->status === 'borrowed')
                    ->action(function (Document $record) {
                        // Update transaksi terakhir
                        $record->transactions()
                            ->where('type', 'borrow')
                            ->whereNull('returned_at')
                            ->latest()
                            ->first()
                            ?->update(['returned_at' => now()]);

                        // Ubah status dokumen
                        $record->update(['status' => 'in_vault']);
                    })
                    ->requiresConfirmation(),
            ]);;
    }

    public static function getRelations(): array
    {
        return [
            //
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
