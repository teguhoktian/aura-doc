<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanResource\Pages;
use App\Filament\Resources\LoanResource\RelationManagers;
use App\Models\Loan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\Alignment;

class LoanResource extends Resource
{
    protected static ?string $model = Loan::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Manajemen Kredit';
    protected static ?string $recordTitleAttribute = 'debtor_name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)->schema([
                    // KOLOM KIRI: Identitas Utama
                    Forms\Components\Group::make([
                        Forms\Components\Section::make('Informasi Debitur')
                            ->schema([
                                Forms\Components\Select::make('branch_id')
                                    ->label('Kantor Cabang/KCP')
                                    ->relationship('branch', 'name')
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->branch_code} - {$record->name}")
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Forms\Components\Select::make('loan_type_id')
                                    ->label('Jenis Kredit')
                                    ->relationship('loan_type', 'description')
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->description}")
                                    ->searchable()
                                    ->required(),

                                Forms\Components\TextInput::make('loan_number')
                                    ->label('Nomor Kontrak (PK)')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Contoh: PK001-2025'),

                                Forms\Components\TextInput::make('debtor_name')
                                    ->label('Nama Lengkap Nasabah')
                                    ->required()
                                    ->maxLength(255),
                            ])->columns(2),

                        // Section Kondisional Pelunasan
                        Forms\Components\Section::make('Detail Penyelesaian')
                            ->visible(fn(Forms\Get $get) => in_array($get('status'), ['closed', 'write_off']))
                            ->schema([
                                Forms\Components\DatePicker::make('settled_at')
                                    ->label('Tanggal Lunas/WO')
                                    ->required(),
                                Forms\Components\TextInput::make('write_off_basis_number')
                                    ->label('No. SK Hapus Buku')
                                    ->required(fn(Forms\Get $get) => $get('status') === 'write_off'),
                            ])->columns(2),
                    ])->columnSpan(2),

                    // KOLOM KANAN: Status & Parameter
                    Forms\Components\Group::make([
                        Forms\Components\Section::make('Status Kredit')
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->options(Loan::getStatuses())
                                    ->required()
                                    ->live()
                                    ->native(false),

                                Forms\Components\TextInput::make('plafond')
                                    ->label('Plafond Nominal')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required(),

                                Forms\Components\DatePicker::make('disbursement_date')
                                    ->label('Tgl Pencairan')
                                    ->required()
                                    ->native(false),
                            ]),
                    ])->columnSpan(1),
                ]),
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
                    ->copyable()
                    ->description(fn(Loan $record) => $record->loan_type->description),

                Tables\Columns\TextColumn::make('debtor_name')
                    ->label('Nama Nasabah')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Loan $record) => "Cabang: " . ($record->branch->name ?? '-')),

                Tables\Columns\TextColumn::make('plafond')
                    ->label('Plafond')
                    ->money('IDR')
                    ->alignment(Alignment::Right)
                    ->sortable(),

                Tables\Columns\TextColumn::make('documents_count')
                    ->label('Berkas')
                    ->counts('documents')
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'info' : 'gray')
                    ->suffix(' file'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'closed' => 'info',
                        'write_off' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state) => Loan::getStatuses()[$state] ?? $state),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(Loan::getStatuses()),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Kantor Cabang')
                    ->relationship('branch', 'name')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Sangat Penting: Menghubungkan daftar dokumen ke halaman Nasabah
            RelationManagers\DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoans::route('/'),
            'create' => Pages\CreateLoan::route('/create'),
            'view' => Pages\ViewLoan::route('/{record}'),
            'edit' => Pages\EditLoan::route('/{record}/edit'),
        ];
    }
}
