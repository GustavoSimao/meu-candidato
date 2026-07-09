<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoteResource\Pages\ListVotes;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use MeuCandidato\Legislative\Models\Vote;
use UnitEnum;

class VoteResource extends Resource
{
    protected static ?string $model = Vote::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-hand-thumb-up';

    protected static string|UnitEnum|null $navigationGroup = 'Legislativo';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Section::make('Voto')->schema([
                Forms\Components\Select::make('voting_session_id')
                    ->label('Sessão')
                    ->relationship('votingSession', 'external_id')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('politician_id')
                    ->label('Político')
                    ->relationship('politician', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('vote')
                    ->label('Voto')
                    ->options([
                        'Sim' => 'Sim',
                        'Não' => 'Não',
                        'Abstenção' => 'Abstenção',
                        'Obstrução' => 'Obstrução',
                        'Art. 17' => 'Art. 17',
                    ])
                    ->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('votingSession.external_id')
                    ->label('Sessão')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('votingSession.date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('politician.name')
                    ->label('Político')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vote')
                    ->label('Voto')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Sim' => 'success',
                        'Não' => 'danger',
                        'Abstenção' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('vote')
                    ->label('Voto')
                    ->options([
                        'Sim' => 'Sim',
                        'Não' => 'Não',
                        'Abstenção' => 'Abstenção',
                        'Obstrução' => 'Obstrução',
                        'Art. 17' => 'Art. 17',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => ListVotes::route('/'),
        ];
    }
}
