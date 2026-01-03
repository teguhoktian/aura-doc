<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages;
use App\Models\Branch;
use App\Models\Loan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Master Data'; // Mengelompokkan menu

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Kantor Jaringan')
                ->description('Kelola detail kode kantor dan hubungan KC/KCP')
                ->schema([
                    Forms\Components\TextInput::make('branch_code')
                        ->label('Kode Cabang')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->placeholder('Contoh: 0031')
                        ->maxLength(10),

                    Forms\Components\TextInput::make('name')
                        ->label('Nama Kantor')
                        ->required()
                        ->placeholder('Contoh: Cabang Sumber'),

                    Forms\Components\Select::make('type')
                        ->label('Tipe Kantor')
                        ->options([
                            'KC' => 'Kantor Cabang',
                            'KCP' => 'Kantor Cabang Pembantu',
                            'KK' => 'Kantor Kas',
                        ])
                        ->required()
                        ->reactive(),

                    Forms\Components\Select::make('parent_id')
                        ->label('Kantor Cabang Induk')
                        ->relationship(
                            name: 'parent',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn(Builder $query) =>
                            $query->where('type', 'KC')
                        )
                        ->visible(fn($get) => in_array($get('type'), ['KCP', 'KK']))
                        ->required(fn($get) => in_array($get('type'), ['KCP', 'KK']))
                        ->searchable()
                        ->preload()
                        ->reactive()

                ])->columns(2)
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('branch_code')
                    ->label('Kode')
                    ->fontFamily('mono')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name') // Menggunakan field 'name' dari Branch itu sendiri
                    ->label('Kantor Jaringan')
                    ->description(
                        // Ganti 'Loan $record' menjadi 'Branch $record' atau hilangkan tipenya
                        fn(\App\Models\Branch $record): string =>
                        $record->type === 'KC'
                            ? 'Kantor Cabang Induk'
                            : "Induk: " . ($record->parent?->name ?? '-')
                    )
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'KC' => 'danger',
                        'KCP' => 'warning',
                        'KK' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Induk (KC)')
                    ->placeholder('-') // Jika KC, induknya kosong
                    ->sortable(),

                // Menampilkan jumlah Loan yang terdaftar di kantor ini
                Tables\Columns\TextColumn::make('loans_count')
                    ->label('Total Loan')
                    ->counts('loans')
                    ->badge()
                    ->color('success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'KC' => 'Kantor Cabang',
                        'KCP' => 'Kantor Cabang Pembantu',
                        'KK' => 'Kantor Kas',
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
        ];
    }
}
