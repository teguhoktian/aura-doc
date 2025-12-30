<?php

namespace App\Filament\Resources\DocumentReleaseResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Document;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)->schema([
                    // Grup 1: Identitas Utama
                    Forms\Components\Section::make('Identitas Berkas')
                        ->schema([
                            Forms\Components\Placeholder::make('loan_info')
                                ->label('Nasabah / Kontrak')
                                ->content(fn($record) => $record->loan->loan_number . ' - ' . $record->loan->debtor_name),

                            Forms\Components\Placeholder::make('doc_type')
                                ->label('Jenis Dokumen')
                                ->content(fn($record) => $record->document_type->name),

                            Forms\Components\Placeholder::make('doc_number')
                                ->label('Nomor Dokumen')
                                ->content(fn($record) => $record->document_number),

                            Forms\Components\Placeholder::make('status')
                                ->label('Status Terakhir')
                                ->content(fn($record) => strtoupper($record->status)),
                        ])->columns(2),

                    // Grup 2: Detail Legal & Metadata
                    Forms\Components\Section::make('Informasi Tambahan')
                        ->schema([
                            Forms\Components\Placeholder::make('expiry_date')
                                ->label('Masa Berlaku')
                                ->content(fn($record) => $record->expiry_date?->format('d/m/Y') ?? 'N/A'),

                            Forms\Components\KeyValue::make('legal_metadata')
                                ->label('Metadata Berkas')
                                ->columnSpanFull(),
                        ]),

                    // Grup 3: Preview File (Jika Ada)
                    Forms\Components\Section::make('Digital Copy')
                        ->schema([
                            Forms\Components\SpatieMediaLibraryFileUpload::make('file')
                                ->collection('document_scans')
                                ->label('File PDF Scan')
                                ->disabled() // Tetap disabled agar tidak bisa diubah/hapus
                                ->dehydrated(false)
                                ->downloadable() // <--- TAMBAHKAN INI agar tombol download muncul
                                ->openable()     // <--- TAMBAHKAN INI agar bisa dibuka di tab baru
                                ->columnSpanFull(),
                        ]),
                ])
            ]);
    }

    public static function getModelLabel(): string
    {
        return 'Dokumen Terlampir';
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('document_number')
            ->columns([
                Tables\Columns\TextColumn::make('document_type.name')
                    ->label('Jenis Dokumen')
                    ->description(fn($record) => 'No: ' . $record->document_number),

                Tables\Columns\TextColumn::make('loan.debtor_name')
                    ->label('Nasabah'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color('gray'),
            ])
            ->headerActions([])
            ->actions([
                // Gunakan ViewAction yang merujuk ke schema form() di atas
                Tables\Actions\ViewAction::make()
                    ->label('Detail Dokumen')
                    ->modalHeading('Detail Informasi Berkas yang Diserahkan')
                    ->modalWidth('4xl'),
            ]);
    }
}
