<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanTypeResource\Pages;
use App\Filament\Resources\LoanTypeResource\RelationManagers;
use App\Models\LoanType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LoanTypeResource extends Resource
{
    protected static ?string $model = LoanType::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    // Kelompokkan di menu "Master Data" agar sidebar rapi
    protected static ?string $navigationGroup = 'Konfigurasi';

    protected static ?string $recordTitleAttribute = 'description';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Master Data Jenis Kredit')
                    ->description('Kelola kode produk dan klasifikasi divisi pengelola kredit.')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Kode Produk')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(10)
                            ->placeholder('Contoh: A02')
                            // Otomatis ubah jadi uppercase agar seragam
                            ->extraInputAttributes(['oninput' => 'this.value = this.value.toUpperCase()']),

                        Forms\Components\TextInput::make('description')
                            ->label('Nama Produk / Keterangan')
                            ->required()
                            ->placeholder('Contoh: KMK UMUM'),

                        Forms\Components\Select::make('division')
                            ->label('Divisi Pengelola')
                            ->options(function () {
                                // Mengambil divisi yang sudah ada di database + opsi default
                                $existing = LoanType::distinct()->pluck('division', 'division')->toArray();
                                $defaults = [
                                    'Divisi Komersial' => 'Divisi Komersial',
                                    'Divisi Konsumer' => 'Divisi Konsumer',
                                    'Divisi Kredit Ritel' => 'Divisi Kredit Ritel',
                                    'Divisi Mikro' => 'Divisi Mikro',
                                    'Divisi KPR' => 'Divisi KPR',
                                ];
                                return array_merge($defaults, $existing);
                            })
                            ->required()
                            ->searchable()
                            // Fitur "Tags" lebih cocok untuk kolom string alih-alih createOptionForm
                            ->createOptionUsing(fn(string $data): string => $data),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Nama Produk')
                    ->description(fn(LoanType $record): string => "Klasifikasi: {$record->division}")
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('division')
                    ->label('Divisi')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Divisi Mikro' => 'success',
                        'Divisi Komersial' => 'warning',
                        'Divisi KPR' => 'danger',
                        default => 'info',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('loans_count')
                    ->label('Total Akun')
                    ->counts('loans')
                    ->badge()
                    ->color('gray'),
            ])
            ->defaultSort('code') // Urutkan berdasarkan kode secara default
            ->filters([
                Tables\Filters\SelectFilter::make('division')
                    ->label('Filter Divisi')
                    // Ambil opsi filter langsung dari data yang unik di database
                    ->options(fn() => LoanType::distinct()->pluck('division', 'division')->toArray()),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
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
            // Pastikan Relation Manager ini sudah Anda buat
            RelationManagers\LoansRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoanTypes::route('/'),
            'create' => Pages\CreateLoanType::route('/create'),
            'edit' => Pages\EditLoanType::route('/{record}/edit'),
        ];
    }
}
