<?php

namespace App\Filament\Resources\MandateResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;

class MandatesRelationManager extends RelationManager
{
    protected static string $relationship = 'mandates';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
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
        ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->recordTitleAttribute('position')
            ->columns([
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
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
