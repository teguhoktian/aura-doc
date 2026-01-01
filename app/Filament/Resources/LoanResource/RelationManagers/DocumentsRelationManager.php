<?php

namespace App\Filament\Resources\LoanResource\RelationManagers;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Storage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';
    protected static ?string $title = 'Berkas Jaminan';

    public function form(Form $form): Form
    {
        // Gunakan skema yang sama persis dengan DocumentResource
        return $form
            ->schema([
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\Group::make()->schema([
                        Forms\Components\Section::make('Identitas Dokumen')
                            ->schema([
                                // Loan ID otomatis dari parent, tidak perlu input manual lagi
                                Forms\Components\Hidden::make('loan_id')
                                    ->default(fn() => $this->getOwnerRecord()->id),

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
                                    ->options(Document::getStatuses())
                                    ->default('in_vault')
                                    ->required()
                                    ->live(),
                            ]),
                    ])->columnSpan(2),

                    Forms\Components\Group::make()->schema([
                        Forms\Components\Section::make('Penyimpanan Fisik')
                            ->schema([
                                Forms\Components\Select::make('storage_id')
                                    ->label('Posisi Box/Rak')
                                    ->relationship('storage', 'name', fn($query) => $query->where('level', 'box'))
                                    ->getOptionLabelFromRecordUsing(fn(Storage $record) => "{$record->name} ({$record->code})")
                                    ->searchable()
                                    ->required(fn($get) => $get('status') === 'in_vault'),
                            ]),

                        Forms\Components\Section::make('Digital Scan')
                            ->schema([
                                Forms\Components\SpatieMediaLibraryFileUpload::make('file')
                                    ->collection('document_scans')
                                    ->label('Upload PDF')
                                    ->acceptedFileTypes(['application/pdf']),
                            ]),
                    ])->columnSpan(1),
                ]),
            ]);
    }

    public function table(Table $table): Table
    {
        // Gunakan kolom-kolom yang ada di DocumentResource Anda agar tampilan konsisten
        return $table
            ->recordTitleAttribute('document_number')
            ->columns([
                Tables\Columns\TextColumn::make('document_type.name')->label('Jenis Berkas'),
                Tables\Columns\TextColumn::make('document_number')->label('Nomor Dokumen')->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'in_vault' => 'success',
                        'at_notary' => 'info',
                        'borrowed' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('storage.name')->label('Lokasi'),
            ])
            ->headerActions([
                // Ini tombol yang akan memunculkan Modal Form di atas
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Berkas Baru')
                    ->modalWidth('6xl'), // Lebar modal agar Grid 3 kolom terlihat rapi
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()->modalWidth('6xl'),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
