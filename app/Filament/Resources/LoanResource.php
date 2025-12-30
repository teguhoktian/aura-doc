<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanResource\Pages;
use App\Filament\Resources\LoanResource\RelationManagers;
use App\Models\Loan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Support\RawHtmlString;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LoanResource extends Resource
{
    protected static ?string $model = Loan::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Debitur')
                    ->description('Informasi utama fasilitas kredit')
                    ->schema([
                        Forms\Components\Select::make('branch_id')
                            ->label('Kantor Cabang/KCP')
                            ->relationship(
                                name: 'branch',
                                titleAttribute: 'name',
                                // Senior Tip: Tambahkan query agar pencarian lebih akurat
                                modifyQueryUsing: fn(Builder $query) => $query->orderBy('branch_code', 'asc')
                            )
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->branch_code} - {$record->name}")
                            ->searchable(['branch_code', 'name']) // User bisa cari "0031" atau "Sumber"
                            ->preload() // Gunakan preload hanya jika total cabang < 100. Jika ribuan, hapus preload().
                            ->required()
                            ->loadingMessage('Mencari kantor jaringan...'),
                        Forms\Components\Select::make('loan_type_id')
                            ->label('Jenis Kredit (Loan Type)')
                            ->relationship('loan_type', 'description')
                            ->searchable()
                            ->preload()
                            ->required()
                            // Menggabungkan informasi agar user mudah memilih
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->description} ({$record->division})")
                            // Memastikan pencarian juga bisa dilakukan berdasarkan kode atau divisi
                            ->getSearchResultsUsing(
                                fn(string $search) => \App\Models\LoanType::where('description', 'like', "%{$search}%")
                                    ->orWhere('code', 'like', "%{$search}%")
                                    ->orWhere('division', 'like', "%{$search}%")
                                    ->limit(50)
                                    ->pluck('description', 'id')
                            ),
                        Forms\Components\TextInput::make('loan_number')
                            ->label('Nomor Kontrak')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('Contoh: PK/001/XII/2023'),

                        Forms\Components\TextInput::make('debtor_name')
                            ->label('Nama Lengkap Nasabah')
                            ->required(),

                        Forms\Components\TextInput::make('plafond')
                            ->label('Plafond Kredit')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),

                        Forms\Components\DatePicker::make('disbursement_date')
                            ->label('Tanggal Pencairan')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\Select::make('status')
                            ->options(Loan::getStatuses()) // Mengambil dari helper di Model
                            ->required()
                            ->native(false)
                            ->default('active'),


                    ])->columns(2),

                // Section Baru: Informasi Pelunasan / Hapus Buku
                Forms\Components\Section::make('Informasi Penyelesaian (Settlement)')
                    ->description('Data ini wajib diisi jika status Kredit Lunas atau Hapus Buku')
                    // Section ini hanya muncul jika status 'closed' atau 'write_off'
                    ->visible(fn(Forms\Get $get) => in_array($get('status'), ['closed', 'write_off']))
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\DatePicker::make('settled_at')
                                ->label('Tanggal Pelunasan/WO')
                                ->required()
                                ->native(false),

                            Forms\Components\TextInput::make('write_off_basis_number')
                                ->label('Nomor SK/Memo Landasan WO')
                                // Hanya wajib jika status adalah Write Off
                                ->required(fn(Forms\Get $get) => $get('status') === 'write_off')
                                ->placeholder('Contoh: SK-DIR/001/X/2023'),
                        ]),

                        Forms\Components\Grid::make(4)->schema([
                            Forms\Components\TextInput::make('settlement_principal')
                                ->label('Pokok')
                                ->numeric()
                                ->prefix('Rp')
                                ->required(),
                            Forms\Components\TextInput::make('settlement_interest')
                                ->label('Bunga')
                                ->numeric()
                                ->prefix('Rp')
                                ->required(),
                            Forms\Components\TextInput::make('settlement_penalty_principal')
                                ->label('Denda Pokok')
                                ->numeric()
                                ->prefix('Rp')
                                ->default(0),
                            Forms\Components\TextInput::make('settlement_penalty_interest')
                                ->label('Denda Bunga')
                                ->numeric()
                                ->prefix('Rp')
                                ->default(0),
                        ]),

                        // Upload File Landasan Hapus Buku
                        Forms\Components\SpatieMediaLibraryFileUpload::make('basis_file')
                            ->label('Upload Dokumen Landasan (PDF)')
                            ->collection('settlement_documents')
                            ->acceptedFileTypes(['application/pdf'])
                            ->visible(fn(Forms\Get $get) => $get('status') === 'write_off'),
                    ])
                    ->columns(1)
                    ->collapsible(),


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Kolom Nomor Kontrak & Jenis Kredit digabung agar compact
                Tables\Columns\TextColumn::make('loan_number')
                    ->label('No. Kontrak')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->description(fn(Loan $record): string => $record->loan_type->code . ' - ' . $record->loan_type->description),

                Tables\Columns\TextColumn::make('debtor_name')
                    ->label('Nasabah')
                    ->searchable()
                    ->sortable()
                    // Menambahkan info Kantor Jaringan di bawah nama nasabah
                    ->description(fn(Loan $record): string => $record->branch ? "Kantor: {$record->branch->name}" : 'Cabang tidak terikat'),

                Tables\Columns\TextColumn::make('plafond')
                    ->label('Plafond')
                    ->money('IDR')
                    ->sortable()
                    ->alignment('right'),

                // Progress dokumen (Jumlah diunggah vs Total dokumen di sistem)
                Tables\Columns\TextColumn::make('documents_count')
                    ->label('Dokumen')
                    ->counts('documents')
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'info' : 'gray')
                    ->suffix(' file'),

                Tables\Columns\TextColumn::make('disbursement_date')
                    ->label('Pencairan')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                // Status dengan label yang sudah disempurnakan
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => Loan::getStatuses()[$state] ?? $state)
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'closed' => 'info',
                        'write_off' => 'danger',
                        default => 'gray',
                    })
                    ->description(
                        fn(Loan $record): ?string =>
                        $record->status !== 'active' && $record->settled_at
                            ? "Lunas: " . $record->settled_at->format('d/m/Y')
                            : null
                    )
                    ->searchable(),
            ])
            ->filters([
                // Filter Status
                Tables\Filters\SelectFilter::make('status')
                    ->options(Loan::getStatuses()),

                // Filter Berdasarkan Kantor Cabang
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Kantor Jaringan')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),

                // Filter Berdasarkan Divisi (Melalui Relasi LoanType)
                Tables\Filters\SelectFilter::make('division')
                    ->label('Divisi')
                    ->options([
                        'Divisi Komersial' => 'Komersial',
                        'Divisi Konsumer' => 'Konsumer',
                        'Divisi Mikro' => 'Mikro',
                        'Divisi Kredit Ritel' => 'Ritel',
                    ])
                    ->query(
                        fn(Builder $query, array $data) =>
                        $query->when(
                            $data['value'],
                            fn($q) =>
                            $q->whereHas('loan_type', fn($type) => $type->where('division', $data['value']))
                        )
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    // Shortcut untuk melihat dokumen langsung dari baris tabel
                    Tables\Actions\Action::make('view_documents')
                        ->label('Lihat Dokumen')
                        ->icon('heroicon-o-document-magnifying-glass')
                        ->url(fn(Loan $record): string => LoanResource::getUrl('view', ['record' => $record]) . '#relation-manager-documents'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    // Export data masal (jika sudah setup exporter)
                    Tables\Actions\ExportBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada data pinjaman')
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoans::route('/'),
            'create' => Pages\CreateLoan::route('/create'),
            'view' => Pages\ViewLoan::route('/{record}'), // TAMBAHKAN INI
            'edit' => Pages\EditLoan::route('/{record}/edit'),
        ];
    }
}
