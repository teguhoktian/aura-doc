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
                            ->options([
                                'active' => 'Aktif',
                                'closed' => 'Lunas',
                                'liquidated' => 'Lelang/Penyelesaian',
                            ])
                            ->required()
                            ->default('active')
                            ->native(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('loan_number')
                    ->label('No. Kontrak')
                    ->searchable()
                    ->sortable()
                    ->copyable(), // Memudahkan copy-paste nomor kontrak

                Tables\Columns\TextColumn::make('debtor_name')
                    ->label('Nama Nasabah')
                    ->searchable(),

                Tables\Columns\TextColumn::make('plafond')
                    ->label('Plafond')
                    ->money('IDR') // Format Rupiah otomatis
                    ->sortable(),

                Tables\Columns\TextColumn::make('documents_count')
                    ->label('Jml Dokumen')
                    ->counts('documents') // Mengambil jumlah relasi secara otomatis
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'closed' => 'gray',
                        'liquidated' => 'danger',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Aktif',
                        'closed' => 'Lunas',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
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
            'edit' => Pages\EditLoan::route('/{record}/edit'),
        ];
    }
}
