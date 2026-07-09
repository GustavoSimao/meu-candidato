<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages\CreateExpense;
use App\Filament\Resources\ExpenseResource\Pages\EditExpense;
use App\Filament\Resources\ExpenseResource\Pages\ListExpenses;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use MeuCandidato\Transparency\Models\Expense;
use UnitEnum;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'Transparência';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Section::make('Dados da Despesa')->schema([
                Forms\Components\Select::make('politician_id')
                    ->label('Político')
                    ->relationship('politician', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('type')
                    ->label('Tipo')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label('Descrição')
                    ->rows(2),
                Forms\Components\TextInput::make('value')
                    ->label('Valor')
                    ->numeric()
                    ->required()
                    ->prefix('R$')
                    ->decimalPlaces(2),
            ])->columns(2),

            Forms\Components\Section::make('Dados do Documento')->schema([
                Forms\Components\TextInput::make('supplier_cnpj_cpf')
                    ->label('CNPJ/CPF Fornecedor')
                    ->maxLength(20),
                Forms\Components\TextInput::make('document_number')
                    ->label('Número do documento')
                    ->maxLength(255),
                Forms\Components\DatePicker::make('document_date')
                    ->label('Data do documento'),
                Forms\Components\TextInput::make('year')
                    ->label('Ano')
                    ->numeric(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('politician.name')
                    ->label('Político')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->limit(40),
                Tables\Columns\TextColumn::make('value')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),
                Tables\Columns\TextColumn::make('document_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('year')
                    ->label('Ano')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year')
                    ->label('Ano')
                    ->options(fn () => Expense::distinct()->pluck('year', 'year')->filter()->sort()->desc()->toArray()),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(fn () => Expense::distinct()->pluck('type', 'type')->filter()->sort()->toArray()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExpenses::route('/'),
            'create' => CreateExpense::route('/create'),
            'edit' => EditExpense::route('/{record}/edit'),
        ];
    }
}
