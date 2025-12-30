<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotaryResource\Pages;
use App\Filament\Resources\NotaryResource\RelationManagers\DocumentsRelationManager;
use App\Models\Notary;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NotaryResource extends Resource
{
    protected static ?string $model = Notary::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = 'Master Data';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Notaris Rekanan')
                ->description('Kelola data notaris untuk pengikatan agunan dan legalitas')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Notaris')
                        ->required()
                        ->placeholder('Contoh: Budi Santoso, S.H., M.Kn.')
                        ->maxLength(255),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Status Aktif/Rekanan')
                        ->default(true)
                        ->helperText('Hanya notaris aktif yang muncul di pilihan input dokumen'),

                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('Email Kantor')
                            ->email(),
                        Forms\Components\TextInput::make('phone')
                            ->label('Nomor Telepon/WA')
                            ->tel(),
                    ]),

                    Forms\Components\Textarea::make('address')
                        ->label('Alamat Kantor')
                        ->columnSpanFull(),
                ])->columns(2)
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Notaris')
                    ->description(fn(Notary $record): string => $record->phone ?? 'No Phone')
                    ->searchable()
                    ->sortable(),

                // STATISTIK: Dokumen yang sedang diproses (Outstanding)
                Tables\Columns\TextColumn::make('documents_count')
                    ->label('Beban Kerja (Docs)')
                    ->counts('documents')
                    ->badge()
                    ->color(fn($state) => $state > 5 ? 'danger' : 'warning') // Jika > 5 dokumen, kasih warna merah (overload)
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->icon('heroicon-m-envelope')
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar Sejak')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Rekanan'),
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
            DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotaries::route('/'),
            'create' => Pages\CreateNotary::route('/create'),
            'edit' => Pages\EditNotary::route('/{record}/edit'),
        ];
    }
}
