<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BillResource\RelationManagers\BillsRelationManager;
use App\Filament\Resources\MandateResource\RelationManagers\MandatesRelationManager;
use App\Filament\Resources\PoliticianResource\Pages\CreatePolitician;
use App\Filament\Resources\PoliticianResource\Pages\EditPolitician;
use App\Filament\Resources\PoliticianResource\Pages\ListPoliticians;
use App\Filament\Resources\PoliticianResource\Pages\ViewPolitician;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use MeuCandidato\Candidate\Models\Politician;
use UnitEnum;

class PoliticianResource extends Resource
{
    protected static ?string $model = Politician::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = 'Cadastros';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Section::make('Dados Pessoais')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('party_id')
                    ->label('Partido')
                    ->relationship('party', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('position')
                    ->label('Cargo')
                    ->maxLength(255),
                Forms\Components\DatePicker::make('birth_date')
                    ->label('Data de nascimento'),
                Forms\Components\TextInput::make('education')
                    ->label('Escolaridade')
                    ->maxLength(255),
                Forms\Components\TextInput::make('declared_profession')
                    ->label('Profissão declarada')
                    ->maxLength(255),
            ])->columns(2),

            Forms\Components\Section::make('Dados Externos')->schema([
                Forms\Components\TextInput::make('external_id')
                    ->label('ID Externo (API)')
                    ->numeric(),
                Forms\Components\TextInput::make('photo_url')
                    ->label('URL da foto')
                    ->url()
                    ->maxLength(500),
                Forms\Components\TextInput::make('cpf')
                    ->label('CPF')
                    ->maxLength(14),
            ])->columns(3),

            Forms\Components\Section::make('Outros')->schema([
                Forms\Components\Textarea::make('defends')
                    ->label('O que defende')
                    ->rows(3),
                Forms\Components\TextInput::make('trendings')
                    ->label('Tendências')
                    ->maxLength(255),
                Forms\Components\TextInput::make('active_processes')
                    ->label('Processos ativos')
                    ->numeric(),
                Forms\Components\TextInput::make('government_plan_url')
                    ->label('URL do plano de governo')
                    ->url()
                    ->maxLength(500),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('party.acronym')
                    ->label('Partido')
                    ->sortable(),
                Tables\Columns\TextColumn::make('position')
                    ->label('Cargo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('education')
                    ->label('Escolaridade')
                    ->limit(30),
                Tables\Columns\TextColumn::make('mandates_count')
                    ->label('Mandatos')
                    ->counts('mandates')
                    ->sortable(),
                Tables\Columns\TextColumn::make('bills_count')
                    ->label('Proposições')
                    ->counts('bills')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('party')
                    ->relationship('party', 'acronym')
                    ->label('Partido')
                    ->preload(),
                Tables\Filters\SelectFilter::make('position')
                    ->label('Cargo')
                    ->options([
                        'Deputado Federal' => 'Deputado Federal',
                        'Senador' => 'Senador',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            'mandates' => MandatesRelationManager::class,
            'bills' => BillsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPoliticians::route('/'),
            'create' => CreatePolitician::route('/create'),
            'edit' => EditPolitician::route('/{record}/edit'),
            'view' => ViewPolitician::route('/{record}'),
        ];
    }
}
