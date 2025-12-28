<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StorageResource\Pages;
use App\Filament\Resources\StorageResource\RelationManagers;
use App\Models\Storage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StorageResource extends Resource
{
    protected static ?string $model = Storage::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()->schema([
                    Forms\Components\Select::make('level')
                        ->options([
                            'warehouse' => 'Gudang',
                            'room' => 'Ruangan',
                            'rack' => 'Rak',
                            'box' => 'Box',
                        ])
                        ->required()
                        ->reactive(),

                    Forms\Components\Select::make('parent_id')
                        ->label('Induk Lokasi')
                        ->relationship('parent', 'name')
                        ->searchable()
                        ->placeholder('Pilih lokasi induk jika ada'),

                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->placeholder('Contoh: Rak Utama A1'),

                    Forms\Components\TextInput::make('code')
                        ->label('Barcode/Unique Code')
                        ->required()
                        ->unique(ignoreRecord: true),
                ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Lokasi')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Storage $record): string => $record->description ?? 'Tidak ada deskripsi'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Barcode/Unit')
                    ->searchable()
                    ->fontFamily('mono') // Agar terlihat seperti kode barcode
                    ->copyable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('level')
                    ->label('Level')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'warehouse' => 'info',
                        'room' => 'warning',
                        'rack' => 'success',
                        'box' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Induk Lokasi')
                    ->placeholder('Top Level') // Jika tidak punya parent
                    ->sortable(),

                // Menghitung jumlah dokumen yang ada di lokasi ini (hanya untuk level box)
                Tables\Columns\TextColumn::make('documents_count')
                    ->label('Isi Dokumen')
                    ->counts('documents')
                    ->badge()
                    ->visible(fn($record) => $record === null || $record->level === 'box'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->label('Update Terakhir')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filter berdasarkan Level (Gudang/Rak/Box)
                Tables\Filters\SelectFilter::make('level')
                    ->options([
                        'warehouse' => 'Gudang',
                        'room' => 'Ruangan',
                        'rack' => 'Rak',
                        'box' => 'Box',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStorages::route('/'),
            'create' => Pages\CreateStorage::route('/create'),
            'edit' => Pages\EditStorage::route('/{record}/edit'),
        ];
    }
}
