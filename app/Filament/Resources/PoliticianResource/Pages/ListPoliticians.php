<?php

namespace App\Filament\Resources\PoliticianResource\Pages;

use App\Filament\Resources\PoliticianResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPoliticians extends ListRecords
{
    protected static string $resource = PoliticianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
