<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StorageResource\Pages;
use App\Models\Storage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StorageResource extends Resource
{
    protected static ?string $model = Storage::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Konfigurasi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Lokasi Penyimpanan')
                    ->description('Tentukan hierarki lokasi penyimpanan mulai dari Gudang hingga nomor Box.')
                    ->schema([
                        Forms\Components\Select::make('level')
                            ->label('Tipe/Level Lokasi')
                            ->options([
                                'warehouse' => 'Gudang (Level 1)',
                                'room' => 'Ruangan (Level 2)',
                                'rack' => 'Rak (Level 3)',
                                'box' => 'Box (Level 4)',
                            ])
                            ->required()
                            ->native(false)
                            ->reactive() // Penting agar parent_id bisa menyesuaikan
                            ->afterStateUpdated(fn(callable $set) => $set('parent_id', null)),

                        Forms\Components\Select::make('parent_id')
                            ->label('Induk Lokasi')
                            ->relationship('parent', 'name', function (Builder $query, Forms\Get $get) {
                                $currentLevel = $get('level');

                                if ($currentLevel === 'room') return $query->where('level', 'warehouse');
                                if ($currentLevel === 'rack') return $query->where('level', 'room');
                                if ($currentLevel === 'box') return $query->where('level', 'rack');

                                // Jika level adalah warehouse (Top Level), jangan biarkan pilih parent
                                // Berikan query yang menghasilkan nol hasil tapi valid secara syntax
                                return $query->whereRaw('1 = 0');
                            })
                            ->searchable()
                            ->preload()
                            // Pastikan dinonaktifkan jika level adalah warehouse
                            ->disabled(fn(Forms\Get $get) => $get('level') === 'warehouse' || !$get('level')),

                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lokasi')
                            ->required()
                            ->placeholder('Contoh: Rak Agunan A-01'),

                        Forms\Components\TextInput::make('code')
                            ->label('Kode Unik / Barcode')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('Contoh: RAK-A01-WH01')
                            ->helperText('Gunakan kode yang akan dicetak pada label fisik.'),

                        Forms\Components\Textarea::make('description')
                            ->label('Keterangan Tambahan')
                            ->placeholder('Contoh: Terletak di pojok kanan gudang utama')
                            ->columnSpanFull(),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_path')
                    ->label('Nama & Jalur Lokasi')
                    ->searchable(['name', 'code'])
                    ->description(fn(Storage $record): string => $record->code),

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
                    ->label('Induk')
                    ->placeholder('ROOT (Top Level)')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('documents_count')
                    ->label('Isi Berkas')
                    ->counts('documents')
                    ->badge()
                    ->color('info')
                    // Hanya tampilkan hitungan jika levelnya 'box' (lokasi berkas fisik biasanya di box)
                    ->visible(fn($record) => $record && $record->level === 'box'),
            ])
            ->defaultSort('level', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->options([
                        'warehouse' => 'Gudang',
                        'room' => 'Ruangan',
                        'rack' => 'Rak',
                        'box' => 'Box',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->before(function (Storage $record, Tables\Actions\DeleteAction $action) {
                            // Validasi: Jangan hapus jika ada dokumen di dalamnya
                            if ($record->documents()->exists()) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Gagal Hapus')
                                    ->body('Lokasi ini masih berisi dokumen fisik!')
                                    ->danger()
                                    ->send();
                                $action->cancel();
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Relation Manager untuk melihat sub-lokasi (misal: isi dari satu Gudang)
            // RelationManagers\ChildrenRelationManager::class,
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
