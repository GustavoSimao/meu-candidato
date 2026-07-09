<?php

namespace App\Filament\Resources\PoliticianResource\Pages;

use App\Filament\Resources\PoliticianResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPolitician extends EditRecord
{
    protected static string $resource = PoliticianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
