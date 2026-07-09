<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VotingSessionResource\Pages\EditVotingSession;
use App\Filament\Resources\VotingSessionResource\Pages\ListVotingSessions;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use MeuCandidato\Legislative\Models\VotingSession;
use UnitEnum;

class VotingSessionResource extends Resource
{
    protected static ?string $model = VotingSession::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static string|UnitEnum|null $navigationGroup = 'Legislativo';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Section::make('Sessão de Votação')->schema([
                Forms\Components\TextInput::make('external_id')
                    ->label('ID Externo')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('bill_id')
                    ->label('Proposição')
                    ->relationship('bill', 'title')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Forms\Components\DatePicker::make('date')
                    ->label('Data')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('Descrição')
                    ->rows(3),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('external_id')
                    ->label('ID Externo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('bill.title')
                    ->label('Proposição')
                    ->limit(50)
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->limit(60),
                Tables\Columns\TextColumn::make('votes_count')
                    ->counts('votes')
                    ->label('Votos')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('De'),
                        Forms\Components\DatePicker::make('date_until')
                            ->label('Até'),
                    ])
                    ->query(function ($query, array $data): void {
                        $query->when($data['date_from'], fn ($q, $date) => $q->where('date', '>=', $date));
                        $query->when($data['date_until'], fn ($q, $date) => $q->where('date', '<=', $date));
                    }),
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
            'index' => ListVotingSessions::route('/'),
            'edit' => EditVotingSession::route('/{record}/edit'),
        ];
    }
}
