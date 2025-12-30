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
    protected static ?string $navigationIcon = 'heroicon-o-banknotes'; // Ikon lebih relevan
    protected static ?string $navigationGroup = 'Manajemen Kredit';
    protected static ?string $recordTitleAttribute = 'debtor_name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)->schema([
                    // KOLOM UTAMA (KIRI)
                    Forms\Components\Group::make([
                        Forms\Components\Section::make('Data Fasilitas Kredit')
                            ->description('Detail utama kontrak dan nasabah')
                            ->schema([
                                Forms\Components\Select::make('branch_id')
                                    ->label('Kantor Cabang/KCP')
                                    ->relationship('branch', 'name', fn($query) => $query->orderBy('branch_code'))
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->branch_code} - {$record->name}")
                                    ->searchable(['branch_code', 'name'])
                                    ->preload()
                                    ->required(),

                                Forms\Components\Select::make('loan_type_id')
                                    ->label('Jenis Kredit')
                                    ->relationship('loan_type', 'description')
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->description}")
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Forms\Components\TextInput::make('loan_number')
                                    ->label('Nomor Kontrak')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->extraInputAttributes(['oninput' => 'this.value = this.value.toUpperCase()']) // Auto Uppercase
                                    ->placeholder('PK/001/XII/2023'),

                                Forms\Components\TextInput::make('debtor_name')
                                    ->label('Nama Lengkap Nasabah')
                                    ->required()
                                    ->maxLength(255),
                            ])->columns(2),

                        Forms\Components\Section::make('Informasi Penyelesaian (Settlement)')
                            ->description('Input data ini saat kredit lunas atau hapus buku')
                            ->visible(fn(Forms\Get $get) => in_array($get('status'), ['closed', 'write_off']))
                            ->collapsed(fn($record) => $record?->status === 'active') // Collapse jika sudah lunas
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\DatePicker::make('settled_at')
                                        ->label('Tanggal Pelunasan/WO')
                                        ->required()
                                        ->native(false),
                                    Forms\Components\TextInput::make('write_off_basis_number')
                                        ->label('No. SK Landasan WO')
                                        ->required(fn(Forms\Get $get) => $get('status') === 'write_off')
                                        ->visible(fn(Forms\Get $get) => $get('status') === 'write_off'),
                                ]),

                                Forms\Components\Grid::make(4)->schema([
                                    Forms\Components\TextInput::make('settlement_principal')->label('Pokok')->numeric()->prefix('Rp')->required(),
                                    Forms\Components\TextInput::make('settlement_interest')->label('Bunga')->numeric()->prefix('Rp')->required(),
                                    Forms\Components\TextInput::make('settlement_penalty_principal')->label('Denda Pokok')->numeric()->prefix('Rp'),
                                    Forms\Components\TextInput::make('settlement_penalty_interest')->label('Denda Bunga')->numeric()->prefix('Rp'),
                                ]),
                            ]),
                    ])->columnSpan(2),

                    // KOLOM KANAN (SIDEBAR FORM)
                    Forms\Components\Group::make([
                        Forms\Components\Section::make('Status & Plafond')
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->options(Loan::getStatuses())
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->default('active'),

                                Forms\Components\TextInput::make('plafond')
                                    ->label('Nilai Plafond')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required()
                                    ->minValue(0),

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
                    ->label('Nasabah')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Loan $record) => "Cabang: {$record->branch->name}"),

                Tables\Columns\TextColumn::make('plafond')
                    ->label('Plafond')
                    ->money('IDR')
                    ->alignment(Alignment::Right),

                Tables\Columns\TextColumn::make('documents_count')
                    ->label('Dokumen')
                    ->counts('documents')
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'info' : 'gray'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => Loan::getStatuses()[$state] ?? $state)
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'closed' => 'info',
                        'write_off' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Kantor Cabang')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->options(Loan::getStatuses()),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('view_documents')
                        ->label('Arsip Dokumen')
                        ->icon('heroicon-o-folder-open')
                        ->color('info')
                        ->url(fn(Loan $record): string => static::getUrl('view', ['record' => $record])),
                ]),
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
            'view' => Pages\ViewLoan::route('/{record}'),
            'edit' => Pages\EditLoan::route('/{record}/edit'),
        ];
    }
}
