<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BillResource\Pages\CreateBill;
use App\Filament\Resources\BillResource\Pages\EditBill;
use App\Filament\Resources\BillResource\Pages\ListBills;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use MeuCandidato\Legislative\Models\Bill;
use UnitEnum;

class BillResource extends Resource
{
    protected static ?string $model = Bill::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'Legislativo';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Section::make('Dados da Proposição')->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Título')
                    ->required()
                    ->maxLength(500),
                Forms\Components\Textarea::make('description')
                    ->label('Descrição')
                    ->rows(3),
                Forms\Components\Select::make('author_id')
                    ->label('Autor')
                    ->relationship('author', 'name')
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('status')
                    ->label('Status')
                    ->maxLength(255),
                Forms\Components\TextInput::make('year')
                    ->label('Ano')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('external_id')
                    ->label('ID Externo (API)')
                    ->numeric(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('author.name')
                    ->label('Autor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('year')
                    ->label('Ano')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year')
                    ->label('Ano')
                    ->options(fn () => Bill::distinct()->pluck('year', 'year')->filter()->sort()->desc()->toArray()),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(fn () => Bill::distinct()->pluck('status', 'status')->filter()->sort()->toArray()),
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
            'index' => ListBills::route('/'),
            'create' => CreateBill::route('/create'),
            'edit' => EditBill::route('/{record}/edit'),
        ];
    }
}
