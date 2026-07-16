<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BadgeDefinitionResource\Pages\CreateBadgeDefinition;
use App\Filament\Resources\BadgeDefinitionResource\Pages\EditBadgeDefinition;
use App\Filament\Resources\BadgeDefinitionResource\Pages\ListBadgeDefinitions;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use MeuCandidato\Candidate\Models\BadgeDefinition;
use UnitEnum;

class BadgeDefinitionResource extends Resource
{
    protected static ?string $model = BadgeDefinition::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static string|UnitEnum|null $navigationGroup = 'Cadastros';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Section::make('Dados do Distintivo')->schema([
                Forms\Components\TextInput::make('badge_type')
                    ->label('Tipo')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('label')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('description')
                    ->label('Descrição')
                    ->maxLength(500),
                Forms\Components\ColorPicker::make('color')
                    ->label('Cor')
                    ->required()
                    ->default('#6366f1'),
            ])->columns(2),

            Forms\Components\Section::make('Regras')->schema([
                Forms\Components\KeyValue::make('rules')
                    ->label('Regras de atribuição')
                    ->keyLabel('Parâmetro')
                    ->valueLabel('Valor')
                    ->reorderable(),
            ]),

            Forms\Components\Toggle::make('is_active')
                ->label('Ativo')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('badge_type')
                    ->label('Tipo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('label')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->limit(50),
                Tables\Columns\TextColumn::make('color')
                    ->label('Cor')
                    ->html()
                    ->formatStateUsing(fn ($state) => '<span class="inline-block w-4 h-4 rounded-full" style="background-color: '.$state.'"></span>'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('politicians_count')
                    ->counts('politicians')
                    ->label('Políticos')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Ativo'),
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
            'index' => ListBadgeDefinitions::route('/'),
            'create' => CreateBadgeDefinition::route('/create'),
            'edit' => EditBadgeDefinition::route('/{record}/edit'),
        ];
    }
}
