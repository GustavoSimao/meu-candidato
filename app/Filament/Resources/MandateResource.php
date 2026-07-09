<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MandateResource\Pages\CreateMandate;
use App\Filament\Resources\MandateResource\Pages\EditMandate;
use App\Filament\Resources\MandateResource\Pages\ListMandates;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use MeuCandidato\Mandate\Models\Mandate;
use UnitEnum;

class MandateResource extends Resource
{
    protected static ?string $model = Mandate::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static string|UnitEnum|null $navigationGroup = 'Cadastros';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Section::make('Dados do Mandato')->schema([
                Forms\Components\Select::make('politician_id')
                    ->label('Político')
                    ->relationship('politician', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('position')
                    ->label('Cargo')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('started_at')
                    ->label('Início')
                    ->required(),
                Forms\Components\DatePicker::make('ended_at')
                    ->label('Fim'),
                Forms\Components\TextInput::make('salary')
                    ->label('Salário')
                    ->numeric()
                    ->prefix('R$')
                    ->decimalPlaces(2),
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
                Tables\Columns\TextColumn::make('position')
                    ->label('Cargo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Início')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ended_at')
                    ->label('Fim')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('Em exercício'),
                Tables\Columns\TextColumn::make('salary')
                    ->label('Salário')
                    ->money('BRL')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('position')
                    ->label('Cargo')
                    ->options([
                        'Deputado Federal' => 'Deputado Federal',
                        'Senador' => 'Senador',
                    ]),
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
            'index' => ListMandates::route('/'),
            'create' => CreateMandate::route('/create'),
            'edit' => EditMandate::route('/{record}/edit'),
        ];
    }
}
