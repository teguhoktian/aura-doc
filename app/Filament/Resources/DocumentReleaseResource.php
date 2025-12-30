<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentReleaseResource\Pages;
use App\Filament\Resources\DocumentReleaseResource\RelationManagers;
use App\Models\Document;
use App\Models\DocumentRelease;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DocumentReleaseResource extends Resource
{
    protected static ?string $model = DocumentRelease::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Manajemen Berkas';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\Group::make([
                    Forms\Components\Section::make('Data Penerima')
                        ->schema([
                            Forms\Components\TextInput::make('ba_number')
                                ->label('No. Berita Acara')
                                ->default(fn() => 'BA/REL/' . now()->format('Ymd/His'))
                                ->required()
                                ->unique(ignoreRecord: true),
                            Forms\Components\DatePicker::make('release_date')
                                ->label('Tanggal Penyerahan')
                                ->default(now())
                                ->required(),
                            Forms\Components\TextInput::make('receiver_name')
                                ->label('Nama Penerima (Sesuai KTP)')
                                ->required(),
                            Forms\Components\TextInput::make('receiver_id_number')
                                ->label('NIK KTP Penerima'),
                            Forms\Components\Textarea::make('notes')
                                ->label('Keterangan / Alasan Penyerahan')
                                ->placeholder('Contoh: Pelunasan Kredit atau Pinjam Sementara untuk Perpanjangan')
                                ->columnSpanFull(),
                        ])->columns(2),

                    Forms\Components\Section::make('Pilih Dokumen')
                        ->description('Cari dokumen berdasarkan nomor kontrak atau nama nasabah')
                        ->hidden(fn($operation) => $operation === 'view')
                        ->schema([
                            Forms\Components\Select::make('document_ids') // Gunakan state temporary
                                ->label('Daftar Dokumen')
                                ->multiple()
                                ->options(function () {
                                    return Document::where('status', 'in_vault')
                                        ->with(['loan', 'document_type'])
                                        ->get()
                                        ->mapWithKeys(fn($doc) => [
                                            $doc->id => "{$doc->loan->loan_number} | {$doc->document_type->name} - No: {$doc->document_number} ({$doc->loan->debtor_name})"
                                        ]);
                                })
                                ->searchable()
                                ->required()
                                ->dehydrated(false), // Jangan simpan kolom ini ke tabel releases
                        ]),
                ])->columnSpan(2),

                Forms\Components\Group::make([
                    Forms\Components\Section::make('Bukti Fisik')
                        ->schema([
                            Forms\Components\SpatieMediaLibraryFileUpload::make('ba_file')
                                ->label('Scan Berita Acara')
                                ->collection('ba_signed')
                                ->acceptedFileTypes(['application/pdf'])
                                ->preserveFilenames(), // Menjaga nama file asli

                            Forms\Components\SpatieMediaLibraryFileUpload::make('receiver_photo')
                                ->label('Foto Penerima')
                                ->collection('release_photos')
                                ->image()
                                // Kita hapus ->capture() karena menyebabkan error
                                // Browser akan otomatis menawarkan opsi "Camera" pada perangkat mobile saat tombol upload diklik
                                ->imageEditor()
                                ->imageResizeMode('cover')
                                ->imageCropAspectRatio('1:1'),
                        ]),
                ])->columnSpan(1),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ba_number')->label('No. BA')->searchable(),
                Tables\Columns\TextColumn::make('release_date')->label('Tanggal')->date(),
                Tables\Columns\TextColumn::make('receiver_name')->label('Penerima')->searchable(),
                Tables\Columns\TextColumn::make('documents_count')
                    ->label('Jml Dokumen')
                    ->counts('documents')
                    ->badge(),
                Tables\Columns\TextColumn::make('user.name')->label('Petugas'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
            RelationManagers\DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocumentReleases::route('/'),
            'create' => Pages\CreateDocumentRelease::route('/create'),
            'view' => Pages\ViewDocumentRelease::route('/{record}'), // File ini yang tadi dicari
            'edit' => Pages\EditDocumentRelease::route('/{record}/edit'),
        ];
    }
}
