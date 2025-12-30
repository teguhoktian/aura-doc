<?php

namespace App\Filament\Resources\LoanResource\RelationManagers;

use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('document_number')
                    ->required()
                    ->maxLength(255),


            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('document_number')
            ->columns([
                Tables\Columns\TextColumn::make('document_number')
                    ->label('Nomor Dokumen')
                    ->searchable(),

                Tables\Columns\TextColumn::make('document_type.name')
                    ->label('Jenis Dokumen')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'PK' => 'info',
                        'SHM', 'SHT' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'in_vault' => 'success',
                        'borrowed' => 'warning',
                        'released' => 'gray',
                        default => 'primary',
                    }),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // ACTION DOWNLOAD
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(function (Document $record) {
                        $media = $record->getFirstMedia('document_scans');
                        if (!$media) return;

                        // Mengambil path fisik file di disk private
                        return response()->download($media->getPath(), $media->file_name);
                    }),

                // ACTION PREVIEW (Membuka PDF di Browser)
                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->action(function (Document $record) {
                        $media = $record->getFirstMedia('document_scans');
                        if (!$media) return;

                        // Stream file secara langsung ke browser
                        return response()->file($media->getPath(), [
                            'Content-Type' => $media->mime_type,
                            'Content-Disposition' => 'inline; filename="' . $media->file_name . '"',
                        ]);
                    }),

                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
