<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentTypeResource\Pages;
use App\Models\DocumentType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DocumentTypeResource extends Resource
{
    protected static ?string $model = DocumentType::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = 'Master Data'; // Mengelompokkan menu agar rapi

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Master Jenis Dokumen')
                ->description('Kelola definisi dokumen untuk seluruh sistem')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Dokumen')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->placeholder('Contoh: SK CPNS atau Sertifikat SHM'),

                    // Di dalam form() DocumentTypeResource
                    Forms\Components\Select::make('category_id')
                        ->label('Kategori')
                        ->relationship('category', 'name') // Pastikan relasi 'category' ada di model DocumentType
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->required(),
                        ])
                        ->required(),

                    Forms\Components\Toggle::make('has_expiry')
                        ->label('Memiliki Masa Berlaku?')
                        ->helperText('Aktifkan jika dokumen ini memerlukan input tanggal kadaluarsa')
                        ->default(false),

                    Forms\Components\Toggle::make('is_mandatory')
                        ->label('Wajib Ada?')
                        ->helperText('Dokumen ini akan menjadi checklist wajib untuk kredit')
                        ->default(false),
                ])->columns(2)
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Dokumen')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_mandatory')
                    ->label('Wajib')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('has_expiry')
                    ->label('Berlaku')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'Legalitas' => 'Dokumen Legalitas',
                        'Agunan' => 'Dokumen Agunan',
                        'Kepegawaian ASN' => 'Dokumen Kepegawaian ASN',
                        'Pengikatan' => 'Dokumen Pengikatan',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Bisa ditambahkan RelationManager untuk melihat 'loans' yang menggunakan dokumen ini
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocumentTypes::route('/'),
            'create' => Pages\CreateDocumentType::route('/create'),
            'edit' => Pages\EditDocumentType::route('/{record}/edit'),
        ];
    }
}
