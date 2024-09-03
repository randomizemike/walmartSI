<?php

namespace App\Filament\Resources\ShippingInstructionsResource\Pages;

use App\Filament\Resources\ShippingInstructionsResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageShippingInstructions extends ManageRecords
{
    protected static string $resource = ShippingInstructionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
