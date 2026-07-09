<?php

namespace App\Filament\Resources\PoliticianResource\Pages;

use App\Filament\Resources\PoliticianResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPolitician extends ViewRecord
{
    protected static string $resource = PoliticianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
