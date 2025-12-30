<?php

namespace App\Filament\Resources\DocumentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('borrower_name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('borrower_name')
            ->columns([
                Tables\Columns\TextColumn::make('transaction_date')->label('Tanggal')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Aktivitas')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'borrow' => 'warning',
                        'return' => 'success',
                        'release' => 'gray',
                        'notary_send' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('borrower_name')->label('Pihak Terkait'),
                Tables\Columns\TextColumn::make('due_date')->label('Estimasi Kembali')->date('d/m/Y'),
                Tables\Columns\TextColumn::make('returned_at')
                    ->label('Realisasi Kembali')
                    ->date('d/m/Y')
                    ->placeholder('Masih dipegang'),
                Tables\Columns\TextColumn::make('user.name')->label('Admin Bank'),
            ])
            ->headerActions([]) // Kosongkan agar user tidak bisa menambah manual dari sini
            ->actions([])
            ->bulkActions([]);
    }
}
