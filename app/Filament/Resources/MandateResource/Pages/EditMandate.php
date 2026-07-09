<?php

namespace App\Filament\Resources\MandateResource\Pages;

use App\Filament\Resources\MandateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMandate extends EditRecord
{
    protected static string $resource = MandateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
