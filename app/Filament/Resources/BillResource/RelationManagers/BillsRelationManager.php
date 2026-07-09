<?php

namespace App\Filament\Resources\BillResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;

class BillsRelationManager extends RelationManager
{
    protected static string $relationship = 'bills';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('title')
                ->label('Título')
                ->required()
                ->maxLength(500),
            Forms\Components\Textarea::make('description')
                ->label('Descrição')
                ->rows(3),
            Forms\Components\TextInput::make('status')
                ->label('Status')
                ->maxLength(255),
            Forms\Components\TextInput::make('year')
                ->label('Ano')
                ->numeric()
                ->required(),
            Forms\Components\TextInput::make('external_id')
                ->label('ID Externo')
                ->numeric(),
        ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                Tables\Columns\TextColumn::make('year')
                    ->label('Ano')
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
