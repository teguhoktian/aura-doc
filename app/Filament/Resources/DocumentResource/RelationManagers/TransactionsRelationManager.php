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
            ->defaultSort('transaction_date', 'desc') // Urutkan dari yang terbaru
            ->columns([
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Tanggal Keluar')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Aktivitas')
                    ->badge()
                    // Warna berubah Hijau jika sudah kembali, selain itu mengikuti tipe aslinize
                    ->color(fn($record): string => $record->returned_at ? 'success' : match ($record->type) {
                        'borrow' => 'warning',
                        'notary_send' => 'info',
                        'release' => 'gray',
                        default => 'gray',
                    })
                    // Teks berubah jadi "CLOSED" jika sudah kembali
                    ->formatStateUsing(
                        fn($state, $record) =>
                        $record->returned_at
                            ? strtoupper(str_replace('_', ' ', $state)) . ' (CLOSED)'
                            : strtoupper(str_replace('_', ' ', $state))
                    ),

                Tables\Columns\TextColumn::make('borrower_name')
                    ->label('Pihak Terkait')
                    ->description(fn($record) => $record->reason), // Menampilkan catatan alasan di bawah nama

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Estimasi Kembali')
                    ->date('d/m/Y')
                    ->color(
                        fn($record) =>
                        !$record->returned_at && $record->due_date?->isPast() ? 'danger' : 'gray'
                    ),

                Tables\Columns\TextColumn::make('returned_at')
                    ->label('Realisasi Kembali')
                    ->date('d/m/Y')
                    ->placeholder('Masih dipegang')
                    ->weight(fn($state) => $state ? 'normal' : 'bold')
                    ->color(fn($state) => $state ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Admin Bank'),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Tanda Terima')
                    // Logika: Tampilkan ikon hanya jika string mengandung info file
                    ->formatStateUsing(fn($state) => str_contains($state, 'document-receipts/') ? 'Lihat BAST' : '-')
                    ->url(function ($record) {
                        // Cari apakah ada path file di dalam string reason
                        // Kita menggunakan regex untuk mengambil path yang mengandung 'document-receipts/'
                        if ($record->reason && preg_match('/document-receipts\/[^\s]+/', $record->reason, $matches)) {
                            return \Illuminate\Support\Facades\Storage::url($matches[0]);
                        }
                        return null;
                    })
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->icon(fn($state) => str_contains($state, 'document-receipts/') ? 'heroicon-m-paper-clip' : null),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
