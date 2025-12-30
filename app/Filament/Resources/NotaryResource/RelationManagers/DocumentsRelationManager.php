<?php

namespace App\Filament\Resources\NotaryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Berkas di Notaris';

    public function form(Form $form): Form
    {
        return $form->schema([
            // Biasanya di sini read-only atau edit terbatas
            Forms\Components\TextInput::make('document_number')
                ->required()
                ->disabled(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('document_number')
            ->columns([
                Tables\Columns\TextColumn::make('loan.loan_number')
                    ->label('No. Kredit')
                    ->description(fn($record) => $record->loan?->debtor_name)
                    ->searchable(),

                Tables\Columns\TextColumn::make('document_type.name')
                    ->label('Jenis Dokumen'),

                Tables\Columns\TextColumn::make('sent_to_notary_at')
                    ->label('Tgl Kirim')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('expected_return_at')
                    ->label('Target Kembali')
                    ->date('d/m/Y')
                    ->color(fn($state) => ($state && \Carbon\Carbon::parse($state)->isPast()) ? 'danger' : 'gray')
                    ->weight(fn($state) => ($state && \Carbon\Carbon::parse($state)->isPast()) ? 'bold' : 'normal'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                Tables\Filters\Filter::make('overdue')
                    ->label('Melewati Target (Overdue)')
                    ->query(fn(Builder $query) => $query->where('expected_return_at', '<', now())),
            ])
            ->headerActions([
                // Biasanya tidak menambah dokumen dari sini, tapi dari DocumentResource
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Tombol cepat untuk mencatat dokumen sudah kembali ke Bank
                Tables\Actions\Action::make('return_to_vault')
                    ->label('Kembali ke Bank')
                    ->icon('heroicon-o-arrow-down-on-square')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Pengembalian Berkas')
                    ->modalDescription('Apakah berkas ini sudah diterima kembali di Brankas (Vault)?')
                    ->form([
                        Forms\Components\DatePicker::make('returned_at')
                            ->label('Tanggal Diterima Kembali')
                            ->default(now())
                            ->required(),
                        Forms\Components\Textarea::make('reason')
                            ->label('Catatan Kondisi Berkas')
                            ->placeholder('Contoh: Berkas diterima lengkap dengan sampul baru')
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        // 1. Buat catatan di tabel Transaction (menggunakan model Anda)
                        \App\Models\Transaction::create([
                            'document_id' => $record->id,
                            'user_id' => auth()->id(), // Mencatat user yang sedang login
                            'borrower_name' => $this->getOwnerRecord()->name, // Nama Notaris sebagai "peminjam/pembawa" sebelumnya
                            'type' => 'notary_return', // Sesuaikan dengan enum/string type Anda
                            'transaction_date' => now(),
                            'returned_at' => $data['returned_at'],
                            'reason' => $data['reason'] ?? 'Dikembalikan oleh Notaris: ' . $this->getOwnerRecord()->name,
                        ]);

                        // 2. Update status pada model Document
                        $record->update([
                            'status' => 'in_vault',
                            'notary_id' => null, // Melepas kaitan dari notaris
                            'expected_return_at' => null, // Reset target kembali
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Transaksi Berhasil')
                            ->body('Dokumen telah dikembalikan ke Brankas dan riwayat telah dicatat.')
                            ->success()
                            ->send();
                    })
            ]);
    }
}
