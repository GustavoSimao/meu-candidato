<?php

namespace App\Filament\Resources\BadgeDefinitionResource\Pages;

use App\Filament\Resources\BadgeDefinitionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBadgeDefinitions extends ListRecords
{
    protected static string $resource = BadgeDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
