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
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LoanTypeResource extends Resource
{
    protected static ?string $model = LoanType::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Master Data Jenis Kredit')
                    ->description('Kelola kode produk dan klasifikasi divisi')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Kode Produk')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('Contoh: A02'),

                        Forms\Components\TextInput::make('description')
                            ->label('Nama Produk / Keterangan')
                            ->required()
                            ->placeholder('Contoh: KMK UMUM'),

                        Forms\Components\Select::make('division')
                            ->label('Divisi Pengelola')
                            ->options([
                                'Divisi Komersial' => 'Divisi Komersial',
                                'Divisi Konsumer' => 'Divisi Konsumer',
                                'Divisi Kredit Ritel' => 'Divisi Kredit Ritel',
                                'Divisi Mikro' => 'Divisi Mikro',
                                'Divisi KPR' => 'Divisi KPR',
                            ])
                            ->required()
                            ->searchable()
                            // Solusi untuk error "creatable":
                            ->createOptionForm([
                                Forms\Components\TextInput::make('division')
                                    ->label('Nama Divisi Baru')
                                    ->required(),
                            ])
                            // Logic untuk menyimpan divisi baru ke dalam list opsi (jika bukan relasi tabel lain)
                            ->createOptionUsing(function (array $data): string {
                                return $data['division'];
                            }),
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
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('division')
                    ->label('Divisi')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('loans_count')
                    ->label('Total Akun')
                    ->counts('loans') // Menunjukkan berapa banyak nasabah yang menggunakan produk ini
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('division')
                    ->label('Filter Divisi')
                    ->options(fn() => \App\Models\LoanType::pluck('division', 'division')->toArray()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
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
