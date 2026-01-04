<?php

namespace App\Filament\Resources\DocumentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected $listeners = [
        'refreshTransactions' => '$refresh',
    ];

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
            ->defaultSort('transaction_date', 'desc')
            ->columns([

                // Tanggal Transaksi
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Tanggal Keluar')
                    ->date('d/m/Y')
                    ->sortable(),

                // Tipe Aktivitas + Badge Warna
                Tables\Columns\TextColumn::make('type')
                    ->label('Aktivitas')
                    ->badge()
                    ->color(fn($record): string => $record->returned_at ? 'success' : match ($record->type) {
                        'borrow' => 'warning',
                        'notary_send' => 'info',
                        'release' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(
                        fn($state, $record) =>
                        $record->returned_at
                            ? strtoupper(str_replace('_', ' ', $state)) . ' (CLOSED)'
                            : strtoupper(str_replace('_', ' ', $state))
                    ),

                // Pihak Terkait + Alasan
                Tables\Columns\TextColumn::make('borrower_name')
                    ->label('Pihak Terkait')
                    ->description(fn($record) => $record->reason ?? '-'),

                // Estimasi Kembali
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Estimasi Kembali')
                    ->date('d/m/Y')
                    ->color(
                        fn($record) =>
                        !$record->returned_at && $record->due_date?->isPast() ? 'danger' : 'gray'
                    )
                    ->placeholder('-'),

                // Realisasi Kembali
                Tables\Columns\TextColumn::make('returned_at')
                    ->label('Realisasi Kembali')
                    ->date('d/m/Y')
                    ->placeholder('Masih dipegang')
                    ->weight(fn($state) => $state ? 'normal' : 'bold')
                    ->color(fn($state) => $state ? 'success' : 'warning'),

                // Admin Bank
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Admin Bank'),

                // Tanda Terima / File
                Tables\Columns\TextColumn::make('receipt')
                    ->label('Tanda Terima')
                    ->getStateUsing(
                        fn($record) =>
                        $record->hasMedia('receipt')
                            ? 'Download BAST'
                            : '-'
                    )
                    ->url(
                        fn($record) =>
                        $record->getFirstMediaUrl('receipt')
                    )
                    ->openUrlInNewTab()
                    ->icon(
                        fn($record) =>
                        $record->hasMedia('receipt') ? 'heroicon-o-arrow-down-on-square' : null
                    )
                    ->color('primary')

            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
